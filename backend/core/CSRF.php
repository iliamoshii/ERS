<?php
/**
 * CSRF Protection
 * Generates and validates synchronizer tokens stored in session.
 */

declare(strict_types=1);

namespace Core;

final class CSRF
{
    private const SESSION_KEY = '_csrf_token';

    /** Generate (or reuse) a CSRF token for the current session. */
    public static function token(): string
    {
        if (!Session::has(self::SESSION_KEY)) {
            Session::set(self::SESSION_KEY, self::generate());
        }

        return Session::get(self::SESSION_KEY);
    }

    /** Render a hidden <input> field. */
    public static function field(): string
    {
        return sprintf(
            '<input type="hidden" name="csrf_token" value="%s">',
            htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Validate the token from a request.
     * Rotates the token after successful validation.
     */
    public static function verify(string $token): bool
    {
        $stored = Session::get(self::SESSION_KEY, '');

        if (!hash_equals($stored, $token)) {
            return false;
        }

        // Rotate after use
        Session::set(self::SESSION_KEY, self::generate());

        return true;
    }

    /** Validate from POST data; throws on failure. */
    public static function verifyRequest(): void
    {
        $token = $_POST['csrf_token'] ?? '';

        if (!self::verify($token)) {
            http_response_code(403);
            Response::json(['success' => false, 'message' => 'درخواست نامعتبر. لطفاً صفحه را رفرش کنید.'], 403);
            exit;
        }
    }

    private static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }
}
