<?php
/**
 * AuthController
 * Handles register, login and logout actions.
 * Called from backend/api/auth.php
 */

declare(strict_types=1);

namespace Controllers;

use Config\Database;
use Core\Auth;
use Core\ActivityLogger;
use Core\CSRF;
use Core\Response;
use Core\Session;
use Core\Validator;
use Models\User;
use PDOException;

final class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // ──────────────────────────────────────────────────────────
    //  REGISTER
    // ──────────────────────────────────────────────────────────
    public function register(): never
    {
        CSRF::verifyRequest();

        $data = $this->input([
            'full_name', 'username', 'phone', 'email', 'password', 'password_confirm',
        ]);

        // Validation
        $v = (new Validator($data))
            ->required('full_name', 'نام و نام خانوادگی')
            ->minLength('full_name', 3, 'نام و نام خانوادگی')
            ->maxLength('full_name', 150, 'نام و نام خانوادگی')
            ->required('username', 'نام کاربری')
            ->username('username')
            ->required('phone', 'شماره موبایل')
            ->phone('phone')
            ->required('password', 'رمز عبور')
            ->password('password')
            ->matches('password_confirm', 'password', 'تکرار رمز عبور');

        // Optional email — validate only if provided
        if (!empty($data['email'])) {
            $v->email('email');
        }

        if ($v->fails()) {
            Response::error($v->firstError(), ['errors' => $v->errors()]);
        }

        // Uniqueness checks
        if ($this->userModel->findByUsername($data['username'])) {
            ActivityLogger::log('register_failed', "ثبت‌نام ناموفق: نام کاربری تکراری «{$data['username']}»", null, null, null, null);
            Response::error('این نام کاربری قبلاً استفاده شده است.');
        }

        if ($this->userModel->findByPhone($data['phone'])) {
            ActivityLogger::log('register_failed', "ثبت‌نام ناموفق: شماره موبایل تکراری «{$data['phone']}»");
            Response::error('این شماره موبایل قبلاً ثبت شده است.');
        }

        if (!empty($data['email']) && $this->userModel->findByEmail($data['email'])) {
            ActivityLogger::log('register_failed', "ثبت‌نام ناموفق: ایمیل تکراری «{$data['email']}»");
            Response::error('این ایمیل قبلاً ثبت شده است.');
        }

        // Create user
        try {
            $userId = $this->userModel->create([
                'full_name'     => $data['full_name'],
                'username'      => $data['username'],
                'phone'         => $data['phone'],
                'email'         => !empty($data['email']) ? $data['email'] : null,
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]),
            ]);

            $user = $this->userModel->findById($userId);

            ActivityLogger::log('user_register', "ثبت‌نام موفق: «{$data['username']}»", 'user', $userId, null, $userId);

            // Auto-login after register
            Auth::login($user);

            Response::success('ثبت‌نام با موفقیت انجام شد!', [
                'redirect' => APP_URL . '/user/dashboard.php',
            ], 201);

        } catch (PDOException $e) {
            ActivityLogger::log('register_failed', "خطا در ثبت‌نام: " . $e->getMessage());
            Response::error('خطا در ثبت‌نام. لطفاً دوباره تلاش کنید.', [], 500);
        }
    }

    // ──────────────────────────────────────────────────────────
    //  LOGIN
    // ──────────────────────────────────────────────────────────
    public function login(): never
    {
        CSRF::verifyRequest();

        $identifier = trim($_POST['identifier'] ?? '');
        $password   = $_POST['password']   ?? '';
        $remember   = !empty($_POST['remember_me']);
        $ip         = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($identifier === '' || $password === '') {
            Response::error('نام کاربری/ایمیل/موبایل و رمز عبور الزامی هستند.');
        }

        // Rate-limit by IP first (check login_attempts table)
        if ($this->isIpBlocked($ip)) {
            ActivityLogger::log('login_blocked', "IP مسدود شد: {$ip}");
            Response::error('تعداد تلاش‌های ورود بیش از حد مجاز است. لطفاً ' . LOCKOUT_MINUTES . ' دقیقه دیگر تلاش کنید.', [], 429);
        }

        $user = $this->userModel->findByIdentifier($identifier);

        if (!$user) {
            $this->recordFailedAttempt($ip, $identifier);
            ActivityLogger::log('login_failed', "ورود ناموفق: شناسه پیدا نشد «{$identifier}»");
            Response::error('نام کاربری یا رمز عبور اشتباه است.');
        }

        // Account status checks
        if ($user['status'] === 'banned') {
            ActivityLogger::log('login_failed', "ورود ناموفق: حساب مسدود «{$identifier}»", 'user', (int)$user['id']);
            Response::error('حساب کاربری شما مسدود شده است. با پشتیبانی تماس بگیرید.');
        }

        if ($user['status'] === 'inactive') {
            Response::error('حساب کاربری شما غیرفعال است.');
        }

        // Lockout check
        if ($this->userModel->isLocked($user)) {
            $remaining = (int) ceil((strtotime($user['locked_until']) - time()) / 60);
            Response::error("حساب موقتاً قفل شده است. {$remaining} دقیقه دیگر تلاش کنید.");
        }

        // Password check
        if (!password_verify($password, $user['password_hash'])) {
            $this->userModel->incrementLoginAttempts((int)$user['id']);
            $this->recordFailedAttempt($ip, $identifier);
            ActivityLogger::log('login_failed', "رمز عبور اشتباه: «{$identifier}»", 'user', (int)$user['id']);
            Response::error('نام کاربری یا رمز عبور اشتباه است.');
        }

        // Success
        $this->userModel->resetLoginAttempts((int)$user['id'], $ip);
        $this->clearFailedAttempts($ip);
        Auth::login($user);

        if ($remember) {
            Auth::issueRememberToken((int)$user['id']);
        }

        ActivityLogger::log('user_login', "ورود موفق: «{$user['username']}»", 'user', (int)$user['id']);

        $redirect = Auth::isAdmin()
            ? APP_URL . '/admin/index.php'
            : APP_URL . '/user/dashboard.php';

        Response::success('خوش آمدید!', ['redirect' => $redirect]);
    }

    // ──────────────────────────────────────────────────────────
    //  LOGOUT
    // ──────────────────────────────────────────────────────────
    public function logout(): never
    {
        CSRF::verifyRequest();

        if (Auth::check()) {
            ActivityLogger::log('user_logout', 'خروج از سیستم: «' . (Auth::user()['username'] ?? '') . '»');
        }

        Auth::logout();
        Response::redirect(APP_URL . '/index.html');
    }

    // ──────────────────────────────────────────────────────────
    //  Private helpers
    // ──────────────────────────────────────────────────────────

    private function input(array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            $out[$f] = trim($_POST[$f] ?? '');
        }
        return $out;
    }

    private function isIpBlocked(string $ip): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
              WHERE ip_address = ?
                AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)'
        );
        $stmt->execute([$ip, LOCKOUT_MINUTES]);
        return (int)$stmt->fetchColumn() >= MAX_LOGIN_ATTEMPTS;
    }

    private function recordFailedAttempt(string $ip, string $identifier): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO login_attempts (identifier, ip_address) VALUES (?, ?)'
        )->execute([$identifier, $ip]);
    }

    private function clearFailedAttempts(string $ip): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([$ip]);
    }
}
