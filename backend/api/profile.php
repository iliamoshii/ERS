<?php
/**
 * API Endpoint — User Profile Management
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Models\User; // فرض بر این است که مدل کاربر در این مسیر قرار دارد

header('Content-Type: application/json; charset=utf-8');

// ۱. بررسی لاگین بودن کاربر
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'شما مجاز به انجام این عملیات نیستید.']);
    exit;
}

// ۲. بررسی توکن امنیتی CSRF
if (!CSRF::check($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر است. صفحه را رفرش کنید.']);
    exit;
}

$action = $_POST['action'] ?? '';
$user = Auth::user();

// ── عملیات آپلود آواتار ─────────────────────────────────────
if ($action === 'update_avatar') {

    // بررسی اینکه آیا فایلی ارسال شده و خطایی ندارد
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'فایلی انتخاب نشده یا آپلود با خطا مواجه شد.']);
        exit;
    }

    $file = $_FILES['avatar'];
    $maxSize = 2 * 1024 * 1024; // حداکثر حجم: 2 مگابایت

    // بررسی حجم
    if ($file['size'] > $maxSize) {
        echo json_encode(['success' => false, 'message' => 'حجم تصویر نباید بیشتر از 2 مگابایت باشد.']);
        exit;
    }

    // بررسی فرمت (MIME type)
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes, true)) {
        echo json_encode(['success' => false, 'message' => 'فرمت فایل مجاز نیست. فقط JPG, PNG و WEBP.']);
        exit;
    }

    // ساخت پوشه در صورت عدم وجود
    $uploadDir = dirname(__DIR__, 2) . '/uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // تولید نام یونیک برای فایل
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = 'user_' . $user['id'] . '_' . time() . '.' . $extension;
    $destination = $uploadDir . $newFileName;

    // مسیر نسبی برای ذخیره در دیتابیس
    $dbPath = 'uploads/avatars/' . $newFileName;

    // انتقال فایل به پوشه نهایی
    if (move_uploaded_file($file['tmp_name'], $destination)) {

        // آپدیت تصویر در دیتابیس (استفاده از کوئری مستقیم اگر متد خاصی در مدل User ندارید)
        $db = \Config\Database::getInstance();
        $stmt = $db->prepare('UPDATE users SET avatar = ? WHERE id = ?');
        $updated = $stmt->execute([$dbPath, $user['id']]);

        if ($updated) {
            // آپدیت سشن فعلی کاربر تا آواتار جدید فوراً اعمال شود
            $_SESSION['user']['avatar'] = $dbPath;

            echo json_encode([
                'success' => true,
                'message' => 'تصویر پروفایل با موفقیت تغییر کرد.',
                'avatar_url' => $dbPath // بازگشت آدرس برای آپدیت فرانت‌اند
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'خطا در ثبت تصویر در پایگاه داده.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در ذخیره‌سازی فایل روی سرور.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'عملیات درخواستی نامعتبر است.']);
exit;