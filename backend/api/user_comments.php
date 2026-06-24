<?php
/**
 * API Endpoint — User Comments Actions (Update / Delete)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Middleware\AuthMiddleware;
use Models\Comment;

header('Content-Type: application/json; charset=utf-8');

// ۱. امنیت: فقط کاربران لاگین شده دسترسی دارند
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'شما مجاز به انجام این عملیات نیستید.']);
    exit;
}

// ۲. امنیت: بررسی توکن CSRF برای جلوگیری از حملات
if (!CSRF::check($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر است. لطفاً صفحه را مجدداً بارگذاری کنید.']);
    exit;
}

$user = Auth::user();
$action = $_POST['action'] ?? '';
$commentModel = new Comment();

// عملیات ویرایش نظر
if ($action === 'update') {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $body = trim((string)($_POST['body'] ?? ''));
    $rating = isset($_POST['rating']) && $_POST['rating'] !== '' ? (int)$_POST['rating'] : null;

    if ($commentId <= 0 || $body === '') {
        echo json_encode(['success' => false, 'message' => 'اطلاعات ارسالی ناقص است.']);
        exit;
    }

    // متد لایه مدل شما: update(int $id, int $userId, string $body, ?int $rating)
    // ارسال $user['id'] تضمین می‌کند کاربر فقط نظر خودش را ویرایش کند.
    $success = $commentModel->update($commentId, $user['id'], $body, $rating);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'نظر با موفقیت به‌روزرسانی شد.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در ویرایش نظر یا عدم دسترسی.']);
    }
    exit;
}

// عملیات حذف نظر
if ($action === 'delete') {
    $commentId = (int)($_POST['comment_id'] ?? 0);

    if ($commentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'شناسه نظر نامعتبر است.']);
        exit;
    }

    // متد لایه مدل شما: delete(int $id, ?int $userId = null)
    // پاس دادن شناسه کاربر امنیت حذف را در کوئری SQL حفظ می‌کند
    $success = $commentModel->delete($commentId, $user['id']);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'نظر شما با موفقیت حذف شد.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در حذف نظر یا عدم دسترسی.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'درخواست نامعتبر است.']);
exit;