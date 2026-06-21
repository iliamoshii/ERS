<?php
/**
 * Bootstrap
 * Include this at the very top of every protected PHP page.
 * Provides: session, auth, CSRF, response helpers, all models.
 */

declare(strict_types=1);

define('APP_BOOTSTRAP', true);

require_once dirname(__DIR__) . '/backend/config/config.php';
require_once dirname(__DIR__) . '/backend/config/database.php';
require_once dirname(__DIR__) . '/backend/core/Session.php';
require_once dirname(__DIR__) . '/backend/core/Auth.php';
require_once dirname(__DIR__) . '/backend/core/CSRF.php';
require_once dirname(__DIR__) . '/backend/core/Response.php';
require_once dirname(__DIR__) . '/backend/core/Validator.php';
require_once dirname(__DIR__) . '/backend/core/ActivityLogger.php';
require_once dirname(__DIR__) . '/backend/middleware/AuthMiddleware.php';
require_once dirname(__DIR__) . '/backend/models/User.php';
require_once dirname(__DIR__) . '/backend/models/Location.php';
require_once dirname(__DIR__) . '/backend/models/Reservation.php';
require_once dirname(__DIR__) . '/backend/models/Comment.php';
require_once dirname(__DIR__) . '/backend/models/Notification.php';
require_once dirname(__DIR__) . '/backend/models/Category.php';
require_once dirname(__DIR__) . '/backend/models/ContactMessage.php';
require_once dirname(__DIR__) . '/backend/models/PasswordReset.php';

use Core\Session;
use Core\Auth;

// Start session and restore remember-me
Session::start();
Auth::bootFromCookie();

// Security headers for HTML pages
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');