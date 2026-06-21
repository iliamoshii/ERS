<?php
/**
 * API: Admin Reservation Actions
 * POST /backend/api/admin_reservations.php
 * Actions: approve | reject
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Core\ActivityLogger;
use Middleware\AuthMiddleware;
use Models\Reservation;
use Models\Notification;

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('روش درخواست پشتیبانی نمی‌شود.', [], 405);
}

AuthMiddleware::requireAdmin(true);
CSRF::verifyRequest();

$admin    = Auth::user();
$resModel = new Reservation();
$notif    = new Notification();
$action   = trim($_POST['action'] ?? '');

match ($action) {
    'approve' => setStatus($admin, $resModel, $notif, 'approved'),
    'reject'  => setStatus($admin, $resModel, $notif, 'rejected'),
    default   => Response::error('اکشن نامعتبر.', [], 400),
};

function setStatus(array $admin, Reservation $resModel, Notification $notif, string $status): never
{
    $id = (int) ($_POST['reservation_id'] ?? 0);
    if ($id <= 0) {
        Response::error('رزرو نامعتبر است.');
    }

    $reservation = $resModel->findById($id);
    if (!$reservation) {
        Response::error('رزرو یافت نشد.', [], 404);
    }

    if (!in_array($reservation['status'], ['pending', 'approved'], true)) {
        Response::error('وضعیت این رزرو قابل تغییر نیست.');
    }

    $ok = $resModel->updateStatus($id, $status, $admin['id']);
    if (!$ok) {
        Response::error('خطا در بروزرسانی وضعیت رزرو.', [], 500);
    }

    $label = $status === 'approved' ? 'تأیید' : 'رد';
    $notif->create(
        (int) $reservation['user_id'],
        'reservation_' . $status,
        "رزرو شما {$label} شد",
        "رزرو شما برای «{$reservation['location_title']}» در تاریخ {$reservation['reservation_date']} {$label} شد.",
        '/user/reservations.php'
    );

    ActivityLogger::log(
        'reservation_' . $status,
        "رزرو شماره {$id} توسط مدیر {$label} شد",
        'reservation',
        $id,
        null,
        $admin['id']
    );

    Response::success("رزرو با موفقیت {$label} شد.");
}