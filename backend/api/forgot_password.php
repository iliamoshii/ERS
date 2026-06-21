<?php
/**
 * Public API: Password Reset
 * POST /backend/api/forgot_password.php?action=request   { identifier }
 * POST /backend/api/forgot_password.php?action=reset      { token, password, password_confirm }
 *
 * NOTE: This local XAMPP setup has no SMTP/mail transport configured
 * (see backend/config/config.php — no MAIL_* constants). For local
 * testing, the "request" action returns the reset link directly in
 * the JSON response when a matching account is found. Before any real
 * deployment, replace the `sendResetEmail()` stub below with an actual
 * mailer (e.g. PHPMailer) and stop returning `dev_reset_url` in the
 * response.
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/bootstrap.php';

use Core\CSRF;
use Core\Response;
use Core\Validator;
use Core\ActivityLogger;
use Models\User;
use Models\PasswordReset;

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('روش درخواست پشتیبانی نمی‌شود.', [], 405);
}

CSRF::verifyRequest();

$action = trim($_POST['action'] ?? '');

match ($action) {
    'request' => requestReset(),
    'reset'   => performReset(),
    default   => Response::error('اکشن نامعتبر است.', [], 400),
};

// ── Step 1: request a reset link ─────────────────────────────
function requestReset(): never
{
    $identifier = trim($_POST['identifier'] ?? '');

    if ($identifier === '') {
        Response::error('لطفاً نام کاربری، ایمیل یا موبایل خود را وارد کنید.');
    }

    $userModel = new User();
    $user      = $userModel->findByIdentifier($identifier);

    // Always show the same generic message regardless of whether the
    // account exists — avoids leaking which identifiers are registered.
    $genericMessage = 'در صورتی که این حساب در سامانه ثبت شده باشد، لینک بازیابی رمز عبور برای شما ارسال می‌شود.';

    if (!$user || $user['status'] !== 'active') {
        Response::success($genericMessage);
    }

    $resetModel = new PasswordReset();
    $rawToken   = $resetModel->createToken((int) $user['id']);
    $resetUrl   = APP_URL . '/reset-password.php?token=' . $rawToken;

    sendResetEmail($user, $resetUrl);

    ActivityLogger::log(
        'password_reset_requested',
        'درخواست بازیابی رمز عبور',
        'user',
        (int) $user['id'],
        null,
        (int) $user['id']
    );

    $payload = ['message' => $genericMessage];

    // DEV-MODE ONLY — see file header note. Remove before production.
    if (defined('APP_ENV') && APP_ENV === 'development') {
        $payload['dev_reset_url'] = $resetUrl;
    }

    Response::json(['success' => true] + $payload);
}

// ── Step 2: consume token, set new password ──────────────────
function performReset(): never
{
    $token   = trim($_POST['token'] ?? '');
    $pass    = $_POST['password']         ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if ($token === '') {
        Response::error('لینک بازیابی نامعتبر است.');
    }

    $resetModel = new PasswordReset();
    $row        = $resetModel->findValidByToken($token);

    if (!$row) {
        Response::error('لینک بازیابی منقضی شده یا قبلاً استفاده شده است.');
    }

    $v = new Validator(['password' => $pass]);
    $v->password('password', 'رمز عبور جدید');
    if ($v->fails()) {
        Response::error($v->firstError());
    }

    if ($pass !== $confirm) {
        Response::error('رمز عبور جدید و تکرار آن یکسان نیستند.');
    }

    $userModel = new User();
    $userModel->update((int) $row['user_id'], [
        'password_hash' => password_hash($pass, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
    ]);

    $resetModel->markUsed((int) $row['id']);
    $resetModel->invalidateAllForUser((int) $row['user_id']);

    ActivityLogger::log(
        'password_reset_completed',
        'بازیابی رمز عبور با موفقیت انجام شد',
        'user',
        (int) $row['user_id'],
        null,
        (int) $row['user_id']
    );

    Response::success('رمز عبور شما با موفقیت تغییر یافت. اکنون می‌توانید وارد شوید.');
}

/**
 * DEV STUB — replace with real mail sending (PHPMailer/SMTP) before
 * deploying anywhere reachable by real users. Currently a no-op;
 * the link is surfaced via `dev_reset_url` in the API response instead.
 */
function sendResetEmail(array $user, string $resetUrl): void
{
    // Intentionally empty in local/dev mode. See file header note.
}
