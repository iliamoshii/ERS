<?php
/**
 * API: Admin Comment Actions
 * POST /backend/api/admin_comments.php
 * Actions: approve | reject | delete
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Core\ActivityLogger;
use Middleware\AuthMiddleware;
use Models\Comment;

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('روش درخواست پشتیبانی نمی‌شود.', [], 405);
}

AuthMiddleware::requireAdmin(true);
CSRF::verifyRequest();

$admin   = Auth::user();
$model   = new Comment();
$action  = trim($_POST['action'] ?? '');

match ($action) {
    'approve' => updateStatus($admin, $model, 'approved'),
    'reject'  => updateStatus($admin, $model, 'rejected'),
    'delete'  => deleteComment($admin, $model),
    default   => Response::error('اکشن نامعتبر است.', [], 400),
};

// ── Approve / Reject ────────────────────────────────────────────
function updateStatus(array $admin, Comment $model, string $newStatus): never
{
    $id = (int) ($_POST['comment_id'] ?? 0);
    if ($id <= 0) {
        Response::error('شناسه نظر نامعتبر است.');
    }

    $comment = $model->findById($id);
    if (!$comment) {
        Response::error('نظر یافت نشد.', [], 404);
    }

    $ok = $model->updateStatus($id, $newStatus);
    if (!$ok) {
        Response::error('تغییر وضعیت انجام نشد.');
    }

    $label = $newStatus === 'approved' ? 'تأیید' : 'رد';
    ActivityLogger::log(
        "comment_{$newStatus}",
        "نظر شماره {$id} توسط مدیر {$label} شد",
        'comment',
        $id,
        null,
        $admin['id']
    );

    Response::success("نظر با موفقیت {$label} شد.");
}

// ── Delete ──────────────────────────────────────────────────────
function deleteComment(array $admin, Comment $model): never
{
    $id = (int) ($_POST['comment_id'] ?? 0);
    if ($id <= 0) {
        Response::error('شناسه نظر نامعتبر است.');
    }

    $comment = $model->findById($id);
    if (!$comment) {
        Response::error('نظر یافت نشد.', [], 404);
    }

    $ok = $model->delete($id);           // null userId → admin hard-delete
    if (!$ok) {
        Response::error('حذف نظر انجام نشد.');
    }

    ActivityLogger::log(
        'comment_deleted',
        "نظر شماره {$id} توسط مدیر حذف شد",
        'comment',
        $id,
        null,
        $admin['id']
    );

    Response::success('نظر با موفقیت حذف شد.');
}
