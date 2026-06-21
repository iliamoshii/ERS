<?php
/**
 * Auth
 * Central authentication state — check login, role, and remember-me.
 */

declare(strict_types=1);

namespace Core;

use Config\Database;
use PDO;

final class Auth
{
    // ── Getters ───────────────────────────────────────────────

    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function id(): ?int
    {
        return Session::get('user_id');
    }

    public static function user(): ?array
    {
        return Session::get('user');
    }

    public static function role(): ?string
    {
        return Session::get('user_role');
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    // ── Session bootstrap (call once per request after session start) ──

    /**
     * Try to restore a session from a remember-me cookie.
     * Must be called before any auth checks if the session is empty.
     */
    public static function bootFromCookie(): void
    {
        if (self::check()) {
            return;
        }

        $cookieName = REMEMBER_COOKIE_NAME;

        if (empty($_COOKIE[$cookieName])) {
            return;
        }

        [$selector, $validator] = self::parseCookie($_COOKIE[$cookieName]);

        if ($selector === null || $validator === null) {
            self::clearRememberCookie();
            return;
        }

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT u.*, r.name AS role_name
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.remember_token IS NOT NULL
                AND u.status = "active"
                AND u.remember_expires > NOW()
                AND LEFT(u.remember_token, 16) = ?
              LIMIT 1'
        );
        $stmt->execute([$selector]);
        $user = $stmt->fetch();

        if (!$user) {
            self::clearRememberCookie();
            return;
        }

        // Constant-time compare of validator portion
        $storedValidator = substr($user['remember_token'], 16);
        if (!hash_equals($storedValidator, hash('sha256', $validator))) {
            self::clearRememberCookie();
            return;
        }

        // Valid — populate session
        self::populateSession($user);

        // Rotate the token
        self::issueRememberToken($user['id']);
    }

    /** Store user data in session after successful login */
    public static function login(array $user): void
    {
        Session::regenerate();
        self::populateSession($user);
    }

    /**
     * Refresh the cached session user array after a profile/avatar update,
     * WITHOUT regenerating the session ID (that only happens on actual login).
     */
    public static function refreshSession(array $user): void
    {
        self::populateSession($user);
    }

    /** Clear session and remember-me cookie */
    public static function logout(): void
    {
        if (self::check()) {
            // Remove remember token from DB
            $pdo = Database::getInstance();
            $pdo->prepare('UPDATE users SET remember_token = NULL, remember_expires = NULL WHERE id = ?')
                ->execute([self::id()]);
        }

        self::clearRememberCookie();
        Session::destroy();
    }

    /** Issue a new remember-me token and write cookie */
    public static function issueRememberToken(int $userId): void
    {
        $selector  = bin2hex(random_bytes(8));          // 16 hex chars
        $validator = bin2hex(random_bytes(16));          // 32 hex chars — sent in cookie
        $hash      = hash('sha256', $validator);
        $token     = $selector . $hash;                 // stored in DB
        $expires   = date('Y-m-d H:i:s', time() + (REMEMBER_COOKIE_DAYS * 86400));

        $pdo = Database::getInstance();
        $pdo->prepare('UPDATE users SET remember_token = ?, remember_expires = ? WHERE id = ?')
            ->execute([$token, $expires, $userId]);

        // Cookie value = selector + raw validator
        $cookieValue = $selector . ':' . $validator;

        setcookie(
            REMEMBER_COOKIE_NAME,
            $cookieValue,
            [
                'expires'  => time() + (REMEMBER_COOKIE_DAYS * 86400),
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]
        );
    }

    // ── Private helpers ───────────────────────────────────────

    private static function populateSession(array $user): void
    {
        Session::set('user_id',   (int) $user['id']);
        Session::set('user_role', $user['role_name']);
        Session::set('user', [
            'id'        => (int) $user['id'],
            'full_name' => $user['full_name'],
            'username'  => $user['username'],
            'email'     => $user['email'],
            'phone'     => $user['phone'],
            'avatar'    => $user['avatar'],
            'status'    => $user['status'],
            'role'      => $user['role_name'],
        ]);
    }

    private static function parseCookie(string $cookie): array
    {
        $parts = explode(':', $cookie, 2);
        if (count($parts) !== 2) {
            return [null, null];
        }
        return [$parts[0], $parts[1]];
    }

    private static function clearRememberCookie(): void
    {
        setcookie(REMEMBER_COOKIE_NAME, '', [
            'expires'  => time() - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[REMEMBER_COOKIE_NAME]);
    }
}
