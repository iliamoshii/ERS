<?php
/**
 * API: Admin User Management
 * POST /backend/api/admin_users.php
 * Actions: update_role | update_status
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Core\ActivityLogger;
use Middleware\AuthMiddleware;
use Models\User;

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('روش درخواست پشتیبانی نمی‌شود.', [], 405);
}

AuthMiddleware::requireAdmin(true);
CSRF::verifyRequest();

$admin     = Auth::user();
$userModel = new User();
$action    = trim($_POST['action'] ?? '');

match ($action) {
    'update_role'   => updateRole($admin, $userModel),
    'update_status' => updateStatus($admin, $userModel),
    default         => Response::error('اکشن نامعتبر.', [], 400),
};

// ── Promote / demote a user ─────────────────────────────────────
function updateRole(array $admin, User $userModel): never
{
    $id     = (int) ($_POST['user_id'] ?? 0);
    $roleId = (int) ($_POST['role_id'] ?? 0);

    if ($id <= 0 || !in_array($roleId, [1, 2], true)) {
        Response::error('درخواست نامعتبر است.');
    }

    if ($id === $admin['id']) {
        Response::error('نمی‌توانید نقش حساب خودتان را تغییر دهید.');
    }

    $target = $userModel->findById($id);
    if (!$target) {
        Response::error('کاربر یافت نشد.', [], 404);
    }

    $userModel->updateRole($id, $roleId);

    $roleLabel = $roleId === 1 ? 'مدیر' : 'کاربر عادی';
    ActivityLogger::log(
        'user_role_changed',
        "نقش کاربر «{$target['full_name']}» به {$roleLabel} تغییر یافت",
        'user',
        $id,
        null,
        $admin['id']
    );

    Response::success("نقش کاربر به {$roleLabel} تغییر یافت.");
}

// ── Activate / suspend a user ───────────────────────────────────
function updateStatus(array $admin, User $userModel): never
{
    $id     = (int) ($_POST['user_id'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    if ($id <= 0 || !in_array($status, ['active', 'suspended'], true)) {
        Response::error('درخواست نامعتبر است.');
    }

    if ($id === $admin['id']) {
        Response::error('نمی‌توانید وضعیت حساب خودتان را تغییر دهید.');
    }

    $target = $userModel->findById($id);
    if (!$target) {
        Response::error('کاربر یافت نشد.', [], 404);
    }

    $userModel->update($id, ['status' => $status]);

    $label = $status === 'active' ? 'فعال' : 'مسدود';
    ActivityLogger::log(
        'user_status_changed',
        "وضعیت کاربر «{$target['full_name']}» به {$label} تغییر یافت",
        'user',
        $id,
        null,
        $admin['id']
    );

    Response::success("کاربر {$label} شد.");
}