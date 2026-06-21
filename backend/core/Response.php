<?php
/**
 * Response Helper
 * Standardised JSON output + XSS-safe escaping utilities.
 */

declare(strict_types=1);

namespace Core;

final class Response
{
    /**
     * Send a JSON response and terminate.
     *
     * @param array<string,mixed> $data
     */
    public static function json(array $data, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    /** Shorthand: success response */
    public static function success(string $message, array $extra = [], int $status = 200): never
    {
        self::json(array_merge(['success' => true, 'message' => $message], $extra), $status);
    }

    /** Shorthand: error response */
    public static function error(string $message, array $extra = [], int $status = 400): never
    {
        self::json(array_merge(['success' => false, 'message' => $message], $extra), $status);
    }

    /** Redirect and terminate */
    public static function redirect(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }

    // ── Output escaping ───────────────────────────────────────

    /** Escape for HTML output */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Escape for HTML attribute */
    public static function attr(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** Strip all tags, trim, cast to string */
    public static function sanitize(mixed $value): string
    {
        return trim(strip_tags((string) $value));
    }

    /** Cast to int safely */
    public static function int(mixed $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
}
