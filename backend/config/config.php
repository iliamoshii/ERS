<?php
/**
 * Application Configuration
 * Stadium Reservation System
 */

declare(strict_types=1);

// ── Environment ──────────────────────────────────────────────
define('APP_ENV',  'development');   // 'development' | 'production'
define('APP_NAME', 'رزرو ورزشگاه');
define('APP_URL',  'http://localhost/stadium');


define('APP_ROOT', dirname(__DIR__, 2));

// ── Database ─────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_PORT',    '3306');
define('DB_NAME',    'stadium_reservation');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Session ───────────────────────────────────────────────────
define('SESSION_NAME',            'STADIUM_SID');
define('SESSION_LIFETIME',        7200);          // 2 hours in seconds
define('SESSION_REGENERATE_EVERY', 300);          // regenerate ID every 5 min

// ── Remember Me Cookie ───────────────────────────────────────
define('REMEMBER_COOKIE_NAME',    'stadium_rm');
define('REMEMBER_COOKIE_DAYS',    30);

// ── Security ─────────────────────────────────────────────────
define('CSRF_TOKEN_LIFETIME',     3600);          // 1 hour
define('MAX_LOGIN_ATTEMPTS',      5);
define('LOCKOUT_MINUTES',         15);
define('BCRYPT_COST',             12);

// ── Uploads ──────────────────────────────────────────────────

define('UPLOAD_DIR',       APP_ROOT . '/assets/images/uploads/');
define('UPLOAD_URL',       APP_URL  . '/assets/images/uploads/');
define('AVATAR_DIR',       APP_ROOT . '/assets/images/avatars/');
define('AVATAR_URL',       APP_URL  . '/assets/images/avatars/');
define('MAX_FILE_SIZE',    5 * 1024 * 1024);       // 5 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// ── Pagination ───────────────────────────────────────────────
define('PER_PAGE_ADMIN', 15);
define('PER_PAGE_USER',  10);

// ── Timezone ─────────────────────────────────────────────────
date_default_timezone_set('Asia/Tehran');

// ── Error Reporting ──────────────────────────────────────────
if (APP_ENV === 'development') {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
    ini_set('log_errors', '1');
    ini_set('error_log', APP_ROOT . '/logs/php_errors.log');
}

// ── Autoloader (simple PSR-4 style) ──────────────────────────
spl_autoload_register(function (string $class): void {
    $base = APP_ROOT . '/backend/';


    $map  = [
        'Config\\'      => 'config/',
        'Core\\'        => 'core/',
        'Models\\'      => 'models/',
        'Controllers\\' => 'controllers/',
        'Middleware\\'  => 'middleware/',
    ];

    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $relative = substr($class, strlen($prefix));
            $file     = $base . $dir . str_replace('\\', '/', $relative) . '.php';


            if (!file_exists($file)) {
                $file = $base . ucfirst($dir) . str_replace('\\', '/', $relative) . '.php';
            }

            if (file_exists($file)) {
                require_once $file;
            }
            return;
        }
    }
});