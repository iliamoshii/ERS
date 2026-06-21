<?php
/**
 * AuthMiddleware
 * Guards API endpoints and PHP pages.
 */

declare(strict_types=1);

namespace Middleware;

use Core\Auth;
use Core\Response;
use Core\Session;

final class AuthMiddleware
{
    /**
     * Require an authenticated user.
     * For API calls → JSON 401. For page calls → redirect to login.
     */
    public static function requireLogin(bool $isApi = false): void
    {
        if (Auth::check()) {
            return;
        }

        if ($isApi) {
            Response::error('برای انجام این کار باید وارد شوید.', [], 401);
        }

        Session::flash('redirect_after_login', $_SERVER['REQUEST_URI'] ?? '');
        Response::redirect(APP_URL . '/Form.html');
    }

    /**
     * Require an admin role.
     */
    public static function requireAdmin(bool $isApi = false): void
    {
        self::requireLogin($isApi);

        if (Auth::isAdmin()) {
            return;
        }

        if ($isApi) {
            Response::error('دسترسی محدود است.', [], 403);
        }

        Response::redirect(APP_URL . '/user/dashboard.php');
    }

    /**
     * Require that the user is a guest (not logged in).
     * Redirect logged-in visitors away from auth pages.
     */
    public static function requireGuest(): void
    {
        if (!Auth::check()) {
            return;
        }

        if (Auth::isAdmin()) {
            Response::redirect(APP_URL . '/admin/index.php');
        }

        Response::redirect(APP_URL . '/user/dashboard.php');
    }
}
