<?php
/**
 * API: CSRF Token
 * GET /backend/api/csrf.php  →  { token: "..." }
 *
 * Frontend calls this once on page load, then includes the token
 * in every POST request as the field "csrf_token".
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/config/config.php';
require_once dirname(__DIR__, 2) . '/backend/config/database.php';
require_once dirname(__DIR__, 2) . '/backend/core/Session.php';
require_once dirname(__DIR__, 2) . '/backend/core/Auth.php';
require_once dirname(__DIR__, 2) . '/backend/core/CSRF.php';
require_once dirname(__DIR__, 2) . '/backend/core/Response.php';

use Core\Session;
use Core\Auth;
use Core\CSRF;
use Core\Response;

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');

Session::start();
Auth::bootFromCookie();

Response::json(['token' => CSRF::token()]);
