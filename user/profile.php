<?php
/**
 * User Panel — Profile Management
 */

require_once dirname(__DIR__) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response;
use Middleware\AuthMiddleware;

// دسترسی فقط برای کاربران لاگین شده
AuthMiddleware::requireLogin();

$user = Auth::user();
$e = fn($v) => Response::e($v);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>پروفایل کاربری | سامانه رزرو استادیوم</title>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/FontAwesome.Pro.7.1.0/css/all.css">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/popup.css">
    <link rel="stylesheet" href="../assets/css/user-panel.css">
</head>
<body class="user-panel-page">

<?php include __DIR__ . '/partials/sidebar.php'; ?>

<div class="panel-main" id="panelMain">

    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="panel-content">

        <div class="page-header">
            <h1>تنظیمات پروفایل</h1>
            <div class="breadcrumb-nav">
                <a href="dashboard.php">داشبورد</a>
                <i class="fas fa-chevron-left"></i>
                <span>پروفایل</span>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="panel-card">
                    <div class="panel-card-header">
                        <h6><i class="fas fa-user-edit ms-2"></i> ویرایش مشخصات</h6>
                    </div>
                    <div class="panel-card-body">

                        <div class="profile-avatar-wrap text-center mb-4" style="position: relative; display: inline-block; width: 100%;">
                            <div class="profile-avatar mx-auto" style="width: 110px; height: 110px; border-radius: 50%; position: relative; background: #1a1f3c; display: flex; align-items: center; justify-content: center; border: 2px solid #39FF14;">
                                <?php if (!empty($user['avatar'])): ?>
                                    <img src="../<?= $e($user['avatar']) ?>" alt="Avatar" class="w-100 h-100 object-fit-cover" style="border-radius: 50%;">
                                <?php else: ?>
                                    <div class="profile-avatar-placeholder" style="font-size: 32px; color: #39FF14; font-weight: bold;">
                                        <?= mb_substr($user['full_name'], 0, 1) ?>
                                    </div>
                                <?php endif; ?>

                                <label for="avatarInput" class="avatar-upload-btn" title="تغییر تصویر" style="cursor: pointer; position: absolute; bottom: 0; right: calc(50% - 55px); z-index: 5; background: #00F0FF; color: #0a0e27; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid #1a1f3c; margin: 0;">
                                    <i class="fas fa-camera" style="font-size: 14px;"></i>
                                </label>
                            </div>
                            <input type="file" id="avatarInput" accept="image/png, image/jpeg, image/webp" class="d-none">
                            <p class="mt-2 text-muted" style="font-size: 11px; margin-bottom: 0; background-color: #9eeaf9;">فرمت‌های مجاز: JPG, PNG, WebP (حداکثر ۵ مگابایت)</p>
                        </div>

                        <form id="profileForm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label-panel"><i class="fas fa-user"></i> نام و نام خانوادگی</label>
                                    <input type="text" name="full_name" class="form-control-panel" value="<?= $e($user['full_name']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-panel"><i class="fas fa-envelope"></i> ایمیل</label>
                                    <input type="email" name="email" class="form-control-panel" value="<?= $e($user['email']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-panel"><i class="fas fa-phone"></i> شماره موبایل</label>
                                    <input type="text" name="phone" class="form-control-panel" value="<?= $e($user['phone'] ?? '') ?>" required>
                                </div>
                                <div class="col-12 mt-4 text-end">
                                    <button type="submit" class="theme-btn btn-neon primary w-100" id="profileSubmitBtn">
                                        ذخیره مشخصات
                                    </button>
                                </div>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="panel-card">
                    <div class="panel-card-header">
                        <h6><i class="fas fa-lock ms-2"></i> تغییر رمز عبور</h6>
                    </div>
                    <div class="panel-card-body">
                        <form id="passwordForm">
                            <div class="field-group mb-3 text-right">
                                <label class="form-label-panel">رمز عبور فعلی</label>
                                <input type="password" name="current_password" class="form-control-panel" required>
                            </div>
                            <div class="field-group mb-3 text-right">
                                <label class="form-label-panel">رمز عبور جدید</label>
                                <input type="password" name="new_password" id="new_password" class="form-control-panel" required minlength="8">
                            </div>
                            <div class="field-group mb-4 text-right">
                                <label class="form-label-panel">تکرار رمز عبور جدید</label>
                                <input type="password" name="new_password_confirm" id="new_password_confirm" class="form-control-panel" required minlength="8">
                            </div>
                            <button type="submit" class="theme-btn btn-neon ghost w-100" id="passwordSubmitBtn">
                                تغییر رمز عبور
                            </button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../assets/js/jquery-3.6.0.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/popup.js"></script>
<script src="../assets/js/user-panel.js"></script>

<script>
    const CSRF_TOKEN = '<?= CSRF::token() ?>';
    const API = '../backend/api/user.php';

    // ── ۱. آپلود هوشمند و ضد کرش آواتار ──────────────────────────
    const avatarInput = document.getElementById('avatarInput');
    if (avatarInput) {
        avatarInput.addEventListener('change', async e => {
            if (!e.target.files.length) return;

            const fd = new FormData();
            fd.append('avatar', e.target.files[0]);
            fd.append('action', 'upload_avatar');
            fd.append('csrf_token', CSRF_TOKEN);

            try {
                const res = await fetch(API, { method: 'POST', credentials: 'same-origin', body: fd });

                if (!res.ok) {
                    const errorText = await res.text();
                    console.error("خطای ساختاری سرور:", errorText);
                    window.Popup?.error('خطای سرور', `کد خطا: ${res.status}. پاسخ خام در کنسول ذخیره شد.`);
                    return;
                }

                const rawText = await res.text();
                let data;
                try {
                    data = JSON.parse(rawText);
                } catch(jsonErr) {
                    console.error("پاسخ سرور JSON معتبر نبود:", rawText);
                    window.Popup?.error('خطای ساختار بک‌اند', 'سرور خروجی متنی غیراستاندارد ارسال کرد. کنسول مرورگر را بررسی کنید.');
                    return;
                }

                if (data.success) {
                    window.Popup?.success('تصویر پروفایل', data.message);
                    if (data.avatar_url) {
                        const newSrc = '../' + data.avatar_url + '?t=' + new Date().getTime();
                        document.querySelectorAll('.sidebar-avatar img, .profile-avatar img, .topbar-avatar img').forEach(img => {
                            img.src = newSrc;
                        });
                        const placeholder = document.querySelector('.profile-avatar-placeholder');
                        if (placeholder) {
                            location.reload();
                        }
                    } else {
                        setTimeout(() => location.reload(), 1000);
                    }
                } else {
                    window.Popup?.error('خطا', data.message || 'عملیات آپلود با شکست مواجه شد.');
                }
            } catch (err) {
                console.error("Network Fetch Error:", err);
                window.Popup?.error('خطای ارتباطی', 'مشکلی در پردازش یا ارسال داده‌ها به سرور پیش آمد.');
            } finally {
                e.target.value = '';
            }
        });
    }

    // ── ۲. ویرایش مشخصات پروفایل ──────────────────────────────────
    document.getElementById('profileForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('profileSubmitBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال ذخیره...';

        const fd = new FormData(e.target);
        fd.append('action', 'update_profile');
        fd.append('csrf_token', CSRF_TOKEN);

        try {
            const res = await fetch(API, { method: 'POST', credentials: 'same-origin', body: fd });
            const rawText = await res.text();

            let data;
            try { data = JSON.parse(rawText); } catch(e) {
                console.error("Raw response:", rawText);
                window.Popup?.error('خطای سرور', 'پاسخ سرور از نوع JSON معتبر نیست.');
                return;
            }

            if (data.success) {
                window.Popup?.success('موفقیت‌آمیز', data.message);
                const newName = fd.get('full_name');
                document.querySelectorAll('.sidebar-user-name, .topbar-username').forEach(el => el.textContent = newName);
            } else {
                window.Popup?.error('خطا', data.message);
            }
        } catch (err) {
            window.Popup?.error('خطای ارتباطی', 'ارتباط با سرور برقرار نشد.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });

    // ── ۳. تغییر رمز عبور ─────────────────────────────────────────
    document.getElementById('passwordForm').addEventListener('submit', async (e) => {
        e.preventDefault();

        const np = document.getElementById('new_password').value;
        const npc = document.getElementById('new_password_confirm').value;

        if (np !== npc) {
            window.Popup?.error('عدم تطابق', 'رمز عبور جدید با تکرار آن یکسان نیست.');
            return;
        }

        const btn = document.getElementById('passwordSubmitBtn');
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال تغییر...';

        const fd = new FormData(e.target);
        fd.append('action', 'change_password');
        fd.append('csrf_token', CSRF_TOKEN);

        try {
            const res = await fetch(API, { method: 'POST', credentials: 'same-origin', body: fd });
            const rawText = await res.text();

            let data;
            try { data = JSON.parse(rawText); } catch(e) {
                console.error("Raw response:", rawText);
                window.Popup?.error('خطای سرور', 'پاسخ سرور نامعتبر است.');
                return;
            }

            if (data.success) {
                window.Popup?.success('بروزرسانی رمز عبور', data.message);
                e.target.reset();
            } else {
                window.Popup?.error('خطا', data.message);
            }
        } catch (err) {
            window.Popup?.error('خطای ارتباطی', 'ارتباط با سرور برقرار نشد.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    });
</script>
</body>
</html>