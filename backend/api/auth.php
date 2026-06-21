<?php
/**
 * API: Authentication
 * POST /backend/api/auth.php?action=register|login|logout
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/backend/config/config.php';
require_once dirname(__DIR__, 2) . '/backend/config/database.php';
require_once dirname(__DIR__, 2) . '/backend/core/Session.php';
require_once dirname(__DIR__, 2) . '/backend/core/Auth.php';
require_once dirname(__DIR__, 2) . '/backend/core/CSRF.php';
require_once dirname(__DIR__, 2) . '/backend/core/Response.php';
require_once dirname(__DIR__, 2) . '/backend/core/Validator.php';
require_once dirname(__DIR__, 2) . '/backend/core/ActivityLogger.php';
require_once dirname(__DIR__, 2) . '/backend/models/User.php';
require_once dirname(__DIR__, 2) . '/backend/controllers/AuthController.php';

use Core\Session;
use Core\Auth;
use Core\Response;
use Controllers\AuthController;

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('روش درخواست پشتیبانی نمی‌شود.', [], 405);
}

Session::start();
Auth::bootFromCookie();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$ctrl   = new AuthController();

match ($action) {
    'register' => $ctrl->register(),
    'login'    => $ctrl->login(),
    'logout'   => $ctrl->logout(),
    default    => Response::error('اکشن نامعتبر.', [], 400),
};
