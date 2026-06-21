<?php
/**
 * API: Reservation Actions
 * POST /backend/api/reservations.php
 * Actions: cancel_reservation
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Core\ActivityLogger;
use Middleware\AuthMiddleware;
use Models\Reservation;

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('روش درخواست پشتیبانی نمی‌شود.', [], 405);
}

AuthMiddleware::requireLogin(true);
CSRF::verifyRequest();

$user     = Auth::user();
$resModel = new Reservation();
$action   = trim($_POST['action'] ?? '');

match ($action) {
    'cancel_reservation' => cancelReservation($user, $resModel),
    default              => Response::error('اکشن نامعتبر.', [], 400),
};

// ── Cancel Reservation ──────────────────────────────────────────
function cancelReservation(array $user, Reservation $resModel): never
{
    $id     = (int) ($_POST['reservation_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($id <= 0) {
        Response::error('رزرو نامعتبر است.');
    }

    // Verify the reservation exists and belongs to this user before touching it
    $existing = $resModel->findById($id);
    if (!$existing || (int) $existing['user_id'] !== $user['id']) {
        Response::error('این رزرو یافت نشد یا متعلق به شما نیست.', [], 404);
    }

    $ok = $resModel->cancel($id, $user['id'], $reason);

    if (!$ok) {
        Response::error('این رزرو قابل لغو نیست (ممکن است قبلاً لغو یا تأیید نهایی شده باشد).');
    }

    ActivityLogger::log(
        'reservation_cancelled',
        "لغو رزرو شماره {$id} توسط کاربر",
        'reservation',
        $id,
        null,
        $user['id']
    );

    Response::success('رزرو با موفقیت لغو شد.');
}