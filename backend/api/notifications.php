<?php
/**
 * API: Notification Actions
 * POST /backend/api/notifications.php
 * Actions: mark_read | mark_all_read
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Middleware\AuthMiddleware;
use Models\Notification;

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('روش درخواست پشتیبانی نمی‌شود.', [], 405);
}

AuthMiddleware::requireLogin(true);
CSRF::verifyRequest();

$user       = Auth::user();
$notifModel = new Notification();
$action     = trim($_POST['action'] ?? '');

match ($action) {
    'mark_read'     => markRead($user, $notifModel),
    'mark_all_read' => markAllRead($user, $notifModel),
    default         => Response::error('اکشن نامعتبر.', [], 400),
};

// ── Mark one notification as read ───────────────────────────────
function markRead(array $user, Notification $notifModel): never
{
    $id = (int) ($_POST['notification_id'] ?? 0);

    if ($id <= 0) {
        Response::error('شناسه اعلان نامعتبر است.');
    }

    $notifModel->markRead($id, $user['id']);
    Response::success('اعلان خوانده شد.');
}

// ── Mark all of this user's notifications as read ───────────────
function markAllRead(array $user, Notification $notifModel): never
{
    $notifModel->markAllRead($user['id']);
    Response::success('همه اعلان‌ها خوانده شدند.');
}
