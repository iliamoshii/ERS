<?php
/**
 * Session Manager
 * Handles secure session start, regeneration and timeout.
 */

declare(strict_types=1);

namespace Core;

final class Session
{
    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            self::$started = true;
            return;
        }

        // Secure session cookie parameters
        session_name(SESSION_NAME);

        session_set_cookie_params([
            'lifetime' => 0,                         // until browser closes
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']),  // true on HTTPS
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
        self::$started = true;

        self::enforceTimeout();
        self::regenerateIfNeeded();
    }

    // ── Public helpers ────────────────────────────────────────

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_unset();

            if (ini_get('session.use_cookies')) {
                $p = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $p['path'],
                    $p['domain'],
                    $p['secure'],
                    $p['httponly']
                );
            }

            session_destroy();
        }

        self::$started = false;
    }

    /** Force regenerate immediately (call after login/privilege change) */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
        $_SESSION['_last_regenerated'] = time();
    }

    // ── Flash messages ────────────────────────────────────────

    public static function flash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }

    public static function getFlash(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }

    // ── Private helpers ───────────────────────────────────────

    private static function enforceTimeout(): void
    {
        if (!isset($_SESSION['_last_activity'])) {
            $_SESSION['_last_activity'] = time();
            return;
        }

        $elapsed = time() - (int) $_SESSION['_last_activity'];

        if ($elapsed > SESSION_LIFETIME) {
            // Session expired — destroy and restart clean
            self::destroy();
            self::start();
            return;
        }

        $_SESSION['_last_activity'] = time();
    }

    private static function regenerateIfNeeded(): void
    {
        $last = (int) ($_SESSION['_last_regenerated'] ?? 0);

        if ((time() - $last) > SESSION_REGENERATE_EVERY) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerated'] = time();
        }
    }
}
