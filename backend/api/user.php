<?php
declare(strict_types=1);

// ۱. تنظیم هدر برای پاسخ خروجی استاندارد JSON
header('Content-Type: application/json; charset=utf-8');

// جلوگیری از نمایش خطاهای متفرقه و تضمین خروجی پاک JSON
ini_set('display_errors', '0');
error_reporting(0);

// ۲. بارگذاری بستر اصلی پروژه
if (file_exists(dirname(__DIR__) . '/bootstrap.php')) {
    require_once dirname(__DIR__) . '/bootstrap.php';
} else {
    echo json_encode(['success' => false, 'message' => 'فایل پایه‌ای bootstrap.php یافت نشد.']);
    exit;
}

use Core\Auth;
use Core\CSRF;
use Models\User;

// ۳. بررسی وضعیت ورود کاربر
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز. لطفاً مجدداً وارد سیستم شوید.']);
    exit;
}

// ۴. بررسی متد درخواست
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است.']);
    exit;
}

// ۵. بررسی توکن امنیتی CSRF با متد اصلاح‌شده رسمی پروژه شما (verify)
$csrfToken = $_POST['csrf_token'] ?? '';
if (!CSRF::verify($csrfToken)) {
    echo json_encode(['success' => false, 'message' => 'اعتبار‌سنجی توکن امنیتی (CSRF) با شکست مواجه شد. لطفاً صفحه را مجدداً بارگذاری کنید.']);
    exit;
}

$action = $_POST['action'] ?? '';
$currentUser = Auth::user();
$userId = (int)$currentUser['id'];

// ساخت نمونه از مدل کاربری پروژه شما
$userModel = new User();

// ═══════════════════════════════════════════════════════════════
// اکشن: آپلود آواتار (upload_avatar)
// ═══════════════════════════════════════════════════════════════
if ($action === 'upload_avatar') {

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $errCode = $_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE;
        $msg = 'هیچ فایلی دریافت نشد یا خطایی در آپلود رخ داده است.';
        if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            $msg = 'حجم تصویر ارسالی بیشتر از حد مجاز سرور است.';
        }
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }

    $file = $_FILES['avatar'];

    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'حجم فایل نمی‌تواند بیشتر از ۵ مگابایت باشد.']);
        exit;
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes, true)) {
        echo json_encode(['success' => false, 'message' => 'فرمت تصویر نامعتبر است. فقط JPG، PNG و WebP مجاز هستند.']);
        exit;
    }

    // تعریف مسیر پوشه ذخیره‌سازی آواتارها در روت اصلی
    $uploadDir = dirname(__DIR__, 2) . '/uploads/avatars/';

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            echo json_encode(['success' => false, 'message' => 'خطای سرور: عدم امکان ساخت پوشه ذخیره‌سازی آواتار.']);
            exit;
        }
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = 'avatar_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $destinationPath = $uploadDir . $newFileName;

    // مسیر نسبی برای ذخیره در دیتابیس
    $dbRelativePath = 'uploads/avatars/' . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
        try {
            // استفاده از متد آپدیت موجود در مدل شما
            if ($userModel->update($userId, ['avatar' => $dbRelativePath])) {

                // همگام‌سازی سشن کاربر با مشخصات جدید
                $_SESSION['user']['avatar'] = $dbRelativePath;

                echo json_encode([
                    'success' => true,
                    'message' => 'تصویر پروفایل شما با موفقیت به‌روزرسانی شد.',
                    'avatar_url' => $dbRelativePath
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'تصویر منتقل شد اما ثبت اطلاعات با شکست مواجه شد.']);
            }
        } catch (\Throwable $dbEx) {
            echo json_encode(['success' => false, 'message' => 'خطای دیتابیس: مطمئن شوید ستون avatar را به جدول users اضافه کرده‌اید.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'خطا در جابجایی تصویر در سرور.']);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════
// اکشن: ویرایش اطلاعات پروفایل (update_profile)
// ═══════════════════════════════════════════════════════════════
if ($action === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');

    if (empty($fullName) || empty($phone) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'تکمیل تمامی فیلدها الزامی است.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'فرمت آدرس ایمیل معتبر نیست.']);
        exit;
    }

    try {
        $existingEmail = $userModel->findByEmail($email);
        if ($existingEmail && (int)$existingEmail['id'] !== $userId) {
            echo json_encode(['success' => false, 'message' => 'این آدرس ایمیل قبلاً توسط کاربر دیگری ثبت شده است.']);
            exit;
        }

        $existingPhone = $userModel->findByPhone($phone);
        if ($existingPhone && (int)$existingPhone['id'] !== $userId) {
            echo json_encode(['success' => false, 'message' => 'این شماره موبایل قبلاً توسط کاربر دیگری ثبت شده است.']);
            exit;
        }

        if ($userModel->update($userId, ['full_name' => $fullName, 'phone' => $phone, 'email' => $email])) {
            $_SESSION['user']['full_name'] = $fullName;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone'] = $phone;
            echo json_encode(['success' => true, 'message' => 'اطلاعات کاربری شما با موفقیت ذخیره شد.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'هیچ تغییری اعمال نشد.']);
        }
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'خطا در ارتباط با دیتابیس در زمان ویرایش مشخصات.']);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════
// اکشن: تغییر رمز عبور (change_password)
// ═══════════════════════════════════════════════════════════════
if ($action === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['new_password_confirm'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo json_encode(['success' => false, 'message' => 'لطفاً تمامی فیلدهای رمز عبور را تکمیل کنید.']);
        exit;
    }

    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'رمز عبور جدید با تکرار آن مطابقت ندارد.']);
        exit;
    }

    if (mb_strlen($newPassword) < 8) {
        echo json_encode(['success' => false, 'message' => 'کلمه عبور جدید باید حداقل ۸ کاراکتر باشد.']);
        exit;
    }

    $userRow = $userModel->findById($userId);

    if (!$userRow || !password_verify($currentPassword, $userRow['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'رمز عبور فعلی وارد شده اشتباه است.']);
        exit;
    }

    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

    if ($userModel->update($userId, ['password_hash' => $newPasswordHash])) {
        echo json_encode(['success' => true, 'message' => 'رمز عبور شما با موفقیت تغییر یافت.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'عملیات تغییر رمز عبور با خطا مواجه شد.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'عملیات نامعتبر است.']);
exit;