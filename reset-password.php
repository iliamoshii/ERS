<?php
/**
 * Reset Password Page
 * Reached via the link from forgot_password.php?action=request
 */

declare(strict_types=1);

require_once __DIR__ . '/backend/bootstrap.php';

use Core\CSRF;
use Core\Response;
use Models\PasswordReset;

$e = fn($v) => Response::e($v);

$token   = trim($_GET['token'] ?? '');
$isValid = false;

if ($token !== '') {
    $resetModel = new PasswordReset();
    $isValid    = (bool) $resetModel->findValidByToken($token);
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <title>بازیابی رمز عبور | سامانه رزرو استادیوم</title>

    <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="assets/css/FontAwesome.Pro.7.1.0/css/all.css">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/form.css">
    <link rel="stylesheet" href="assets/css/popup.css">
</head>

<body class="auth-page">

<div class="auth-bg-wrapper">
    <div class="auth-bg-gradient"></div>
    <div class="auth-grid-overlay"></div>
    <div id="bg-icons-container"></div>
</div>

<div class="page-wrapper">
    <div class="back-to-home">
        <a href="Form.html" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            <span>بازگشت به صفحه ورود</span>
        </a>
    </div>

    <section class="auth-section">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-xl-5 col-lg-6 col-md-8 col-sm-11">
                    <div class="auth-container" data-aos="fade-up" data-aos-duration="800">

                        <div class="auth-logo">
                            <img src="assets/images/logo.svg" alt="لوگو سامانه رزرو" class="logo-img">
                            <h3 class="auth-title">بازیابی رمز عبور</h3>
                            <p class="auth-subtitle">
                                <?= $isValid ? 'رمز عبور جدید خود را وارد کنید' : 'این لینک نامعتبر است' ?>
                            </p>
                        </div>

                        <div class="auth-content">
                            <?php if ($isValid): ?>
                                <div class="auth-form-wrapper active">
                                    <form class="auth-form" id="resetForm" novalidate>
                                        <input type="hidden" name="token" value="<?= $e($token) ?>">

                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-lock"></i>
                                                رمز عبور جدید
                                            </label>
                                            <div class="input-wrapper">
                                                <input type="password" name="password" id="newPassword"
                                                       class="form-control-neon" placeholder="••••••••"
                                                       minlength="8" autocomplete="new-password" required>
                                                <span class="input-icon toggle-password" data-target="newPassword">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                            <div class="password-strength">
                                                <div class="strength-bar"><div class="strength-progress"></div></div>
                                                <span class="strength-text">قدرت رمز عبور</span>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-lock"></i>
                                                تکرار رمز عبور جدید
                                            </label>
                                            <div class="input-wrapper">
                                                <input type="password" name="password_confirm" id="newPasswordConfirm"
                                                       class="form-control-neon" placeholder="••••••••"
                                                       autocomplete="new-password" required>
                                                <span class="input-icon toggle-password" data-target="newPasswordConfirm">
                                                    <i class="fas fa-eye"></i>
                                                </span>
                                            </div>
                                        </div>

                                        <button type="submit" class="theme-btn submit-btn" id="resetBtn">
                                            <span class="btn-text">تغییر رمز عبور</span>
                                            <span class="btn-icon"><i class="fas fa-check"></i></span>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="auth-form-wrapper active" style="text-align:center; padding: 20px 0;">
                                    <i class="fas fa-exclamation-triangle" style="font-size:42px; color:#ff3b30; margin-bottom:16px;"></i>
                                    <p style="color:rgba(255,255,255,0.7); margin-bottom:24px;">
                                        این لینک بازیابی رمز عبور منقضی شده یا قبلاً استفاده شده است.
                                    </p>
                                    <a href="Form.html" class="theme-btn submit-btn" style="display:inline-flex;">
                                        <span class="btn-text">بازگشت به صفحه ورود</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner">
        <div class="spinner-ring"></div>
        <div class="spinner-text">در حال پردازش...</div>
    </div>
</div>

<script src="assets/js/jquery-3.6.0.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script src="assets/js/popup.js"></script>
<script>
AOS.init({ duration: 800, once: true });

document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.getAttribute('data-target'));
        const icon  = btn.querySelector('i');
        const isPw  = input.type === 'password';
        input.type  = isPw ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !isPw);
        icon.classList.toggle('fa-eye-slash', isPw);
    });
});

const pwInput = document.getElementById('newPassword');
if (pwInput) {
    pwInput.addEventListener('input', function () {
        const pw = this.value;
        const bar  = document.querySelector('.strength-progress');
        const text = document.querySelector('.strength-text');
        let score = 0;
        if (pw.length >= 6) score++;
        if (pw.length >= 8) score++;
        if (/[a-z]/.test(pw) && /[A-Z]/.test(pw)) score++;
        if (/\d/.test(pw)) score++;
        if (/[^a-zA-Z0-9]/.test(pw)) score++;

        bar.classList.remove('weak', 'medium', 'strong');
        if (!pw) { bar.style.width = '0'; text.textContent = 'قدرت رمز عبور'; }
        else if (score <= 2) { bar.classList.add('weak');   text.textContent = 'ضعیف';  text.style.color = '#ff3366'; }
        else if (score <= 4) { bar.classList.add('medium'); text.textContent = 'متوسط'; text.style.color = '#ffaa00'; }
        else                 { bar.classList.add('strong'); text.textContent = 'قوی';   text.style.color = '#39FF14'; }
    });
}

document.getElementById('resetForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const pw   = document.getElementById('newPassword').value;
    const cpw  = document.getElementById('newPasswordConfirm').value;

    if (pw !== cpw) {
        Popup.error('عدم تطابق', 'رمز عبور و تکرار آن یکسان نیستند.');
        return;
    }
    if (pw.length < 8) {
        Popup.warning('رمز عبور کوتاه است', 'رمز عبور باید حداقل ۸ کاراکتر باشد.');
        return;
    }

    document.getElementById('loadingOverlay').classList.add('active');

    try {
        const csrfRes  = await fetch('backend/api/csrf.php', { credentials: 'same-origin' });
        const csrfData = await csrfRes.json();

        const fd = new FormData(form);
        fd.append('action', 'reset');
        fd.append('csrf_token', csrfData.token || '');

        const res  = await fetch('backend/api/forgot_password.php?action=reset', {
            method: 'POST', credentials: 'same-origin', body: fd,
        });
        const data = await res.json();

        if (data.success) {
            Popup.success('موفق', data.message);
            setTimeout(() => { window.location.href = 'Form.html'; }, 1800);
        } else {
            Popup.error('خطا', data.message || 'بازیابی رمز عبور ناموفق بود.');
        }
    } catch (err) {
        Popup.error('خطای ارتباطی', 'ارتباط با سرور برقرار نشد.');
    } finally {
        document.getElementById('loadingOverlay').classList.remove('active');
    }
});
</script>
</body>
</html>
