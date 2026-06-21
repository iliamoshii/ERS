<?php
require_once dirname(__DIR__) . '/backend/bootstrap.php';

use Core\Auth;
use Core\CSRF;
use Core\Response as R;
use Middleware\AuthMiddleware;
use Models\User;

AuthMiddleware::requireLogin();

$user      = Auth::user();
$userModel = new User();
$fullUser  = $userModel->findById($user['id']);

$e         = fn($v) => R::e($v);
$pageTitle = 'پروفایل من';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>پروفایل | سامانه رزرو استادیوم</title>
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

        <div class="row g-4">

            <!-- ── Profile Info Card ── -->
            <div class="col-lg-4">
                <div class="panel-card text-center">
                    <div class="panel-card-body">

                        <!-- Avatar -->
                        <div class="profile-avatar-wrap">
                            <?php if ($fullUser['avatar']): ?>
                                <img src="../<?= $e($fullUser['avatar']) ?>"
                                     alt="آواتار" class="profile-avatar" id="avatarPreview">
                            <?php else: ?>
                                <div class="profile-avatar-placeholder" id="avatarPreview">
                                    <?= mb_substr($fullUser['full_name'], 0, 1) ?>
                                </div>
                            <?php endif; ?>

                            <label class="avatar-upload-btn" for="avatarInput" title="تغییر تصویر">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="avatarInput" class="d-none"
                                   accept="image/jpeg,image/png,image/webp">
                        </div>

                        <h5 class="mt-3 mb-1"><?= $e($fullUser['full_name']) ?></h5>
                        <p class="text-muted mb-3">@<?= $e($fullUser['username']) ?></p>

                        <div class="profile-meta">
                            <div class="meta-item">
                                <i class="fas fa-phone"></i>
                                <span><?= $e($fullUser['phone']) ?></span>
                            </div>
                            <?php if ($fullUser['email']): ?>
                                <div class="meta-item">
                                    <i class="fas fa-envelope"></i>
                                    <span><?= $e($fullUser['email']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="meta-item">
                                <i class="fas fa-calendar-plus"></i>
                                <span>عضو از <?= date('Y/m/d', strtotime($fullUser['created_at'])) ?></span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ── Edit Forms ── -->
            <div class="col-lg-8">

                <!-- Edit Profile -->
                <div class="panel-card mb-4">
                    <div class="panel-card-header">
                        <h6><i class="fas fa-user-edit ms-2"></i> ویرایش اطلاعات</h6>
                    </div>
                    <div class="panel-card-body">
                        <form id="profileForm" novalidate>
                            <input type="hidden" name="csrf_token"
                                   value="<?= CSRF::token() ?>">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label-panel">نام و نام خانوادگی</label>
                                    <input type="text" name="full_name"
                                           class="form-control-panel"
                                           value="<?= $e($fullUser['full_name']) ?>"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-panel">نام کاربری</label>
                                    <input type="text" name="username"
                                           class="form-control-panel"
                                           value="<?= $e($fullUser['username']) ?>"
                                           readonly disabled>
                                    <small class="text-muted">نام کاربری قابل تغییر نیست.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-panel">شماره موبایل</label>
                                    <input type="tel" name="phone"
                                           class="form-control-panel"
                                           value="<?= $e($fullUser['phone']) ?>"
                                           required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-panel">ایمیل (اختیاری)</label>
                                    <input type="email" name="email"
                                           class="form-control-panel"
                                           value="<?= $e($fullUser['email'] ?? '') ?>">
                                </div>
                            </div>

                            <button type="submit" class="theme-btn btn-sm mt-3" id="profileBtn">
                                <i class="fas fa-save ms-2"></i> ذخیره تغییرات
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="panel-card">
                    <div class="panel-card-header">
                        <h6><i class="fas fa-key ms-2"></i> تغییر رمز عبور</h6>
                    </div>
                    <div class="panel-card-body">
                        <form id="passwordForm" novalidate>
                            <input type="hidden" name="csrf_token"
                                   value="<?= CSRF::token() ?>">
                            <input type="hidden" name="action" value="change_password">

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label-panel">رمز فعلی</label>
                                    <input type="password" name="current_password"
                                           class="form-control-panel"
                                           placeholder="••••••••" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-panel">رمز جدید</label>
                                    <input type="password" name="new_password"
                                           class="form-control-panel"
                                           placeholder="••••••••" required minlength="8">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-panel">تکرار رمز جدید</label>
                                    <input type="password" name="new_password_confirm"
                                           class="form-control-panel"
                                           placeholder="••••••••" required>
                                </div>
                            </div>

                            <button type="submit" class="theme-btn btn-sm mt-3" id="passwordBtn">
                                <i class="fas fa-lock ms-2"></i> تغییر رمز عبور
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

    // ── Avatar preview ──────────────────────────────────────────
    document.getElementById('avatarInput').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        if (file.size > 5 * 1024 * 1024) {
            Popup.error('حجم فایل', 'حداکثر اندازه تصویر ۵ مگابایت است.');
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            const preview = document.getElementById('avatarPreview');
            if (preview.tagName === 'IMG') {
                preview.src = e.target.result;
            } else {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = 'آواتار';
                img.className = 'profile-avatar';
                img.id = 'avatarPreview';
                preview.replaceWith(img);
            }
        };
        reader.readAsDataURL(file);
        uploadAvatar(file);
    });

    async function uploadAvatar(file) {
        const fd = new FormData();
        fd.append('action',     'upload_avatar');
        fd.append('csrf_token', CSRF_TOKEN);
        fd.append('avatar',     file);
        try {
            const res  = await fetch(API, { method: 'POST', credentials: 'same-origin', body: fd });
            const data = await res.json();
            data.success
                ? Popup.success('آواتار', 'تصویر پروفایل به‌روز شد.')
                : Popup.error('خطا', data.message);
        } catch {
            Popup.error('خطای ارتباطی', 'لطفاً دوباره تلاش کنید.');
        }
    }

    // ── Edit profile ────────────────────────────────────────────
    document.getElementById('profileForm').addEventListener('submit', async e => {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.set('csrf_token', CSRF_TOKEN);
        try {
            const res  = await fetch(API, { method: 'POST', credentials: 'same-origin', body: fd });
            const data = await res.json();
            data.success
                ? Popup.success('پروفایل', data.message)
                : Popup.error('خطا', data.message);
        } catch {
            Popup.error('خطای ارتباطی', 'لطفاً دوباره تلاش کنید.');
        }
    });

    // ── Change password ─────────────────────────────────────────
    document.getElementById('passwordForm').addEventListener('submit', async e => {
        e.preventDefault();
        const fd  = new FormData(e.target);
        const np  = fd.get('new_password');
        const npc = fd.get('new_password_confirm');
        if (np !== npc) { Popup.error('عدم تطابق', 'رمز جدید و تکرار آن یکسان نیستند.'); return; }
        fd.set('csrf_token', CSRF_TOKEN);
        try {
            const res  = await fetch(API, { method: 'POST', credentials: 'same-origin', body: fd });
            const data = await res.json();
            if (data.success) {
                Popup.success('رمز عبور', data.message);
                e.target.reset();
            } else {
                Popup.error('خطا', data.message);
            }
        } catch {
            Popup.error('خطای ارتباطی', 'لطفاً دوباره تلاش کنید.');
        }
    });
</script>
</body>
</html>