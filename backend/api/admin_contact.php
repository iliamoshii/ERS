<?php
/**
 * API: Admin Contact Message Actions
 * POST /backend/api/admin_contact.php
 * Actions: mark_read | mark_unread | delete
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Core\ActivityLogger;
use Middleware\AuthMiddleware;
use Models\ContactMessage;

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('روش درخواست پشتیبانی نمی‌شود.', [], 405);
}

AuthMiddleware::requireAdmin(true);
CSRF::verifyRequest();

$admin  = Auth::user();
$model  = new ContactMessage();
$action = trim($_POST['action'] ?? '');

match ($action) {
    'mark_read'   => markRead($admin, $model, true),
    'mark_unread' => markRead($admin, $model, false),
    'delete'      => deleteMessage($admin, $model),
    default       => Response::error('اکشن نامعتبر است.', [], 400),
};

// ── Mark read / unread ──────────────────────────────────────────
function markRead(array $admin, ContactMessage $model, bool $read): never
{
    $id = (int) ($_POST['message_id'] ?? 0);
    if ($id <= 0) {
        Response::error('شناسه پیام نامعتبر است.');
    }

    if (!$model->findById($id)) {
        Response::error('پیام یافت نشد.', [], 404);
    }

    $ok    = $read ? $model->markRead($id) : $model->markUnread($id);
    $label = $read ? 'خوانده‌شده' : 'خوانده‌نشده';

    if (!$ok) {
        Response::error("تغییر وضعیت به {$label} انجام نشد.");
    }

    ActivityLogger::log(
        $read ? 'contact_marked_read' : 'contact_marked_unread',
        "پیام تماس شماره {$id} به {$label} تغییر یافت",
        'contact_message',
        $id,
        null,
        $admin['id']
    );

    Response::success("پیام به {$label} تغییر یافت.");
}

// ── Delete ──────────────────────────────────────────────────────
function deleteMessage(array $admin, ContactMessage $model): never
{
    $id = (int) ($_POST['message_id'] ?? 0);
    if ($id <= 0) {
        Response::error('شناسه پیام نامعتبر است.');
    }

    if (!$model->findById($id)) {
        Response::error('پیام یافت نشد.', [], 404);
    }

    $ok = $model->delete($id);
    if (!$ok) {
        Response::error('حذف پیام انجام نشد.');
    }

    ActivityLogger::log(
        'contact_deleted',
        "پیام تماس شماره {$id} توسط مدیر حذف شد",
        'contact_message',
        $id,
        null,
        $admin['id']
    );

    Response::success('پیام با موفقیت حذف شد.');
}
