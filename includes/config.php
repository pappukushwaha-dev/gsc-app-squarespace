<?php
// includes/config.php
declare(strict_types=1);

/* ==========================================================
   AUTOLOADER (.env)
========================================================== */
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // no fatal error if .env missing


/* ==========================================================
   DATABASE CONNECTION (PDO)
========================================================== */
require_once __DIR__ . '/db.php'; // ensures $pdo is available


/* ==========================================================
   APPLICATION ENVIRONMENT
========================================================== */
$APP_ENV      = $_ENV['APP_ENV']      ?? 'production';
$APP_DEBUG    = ($_ENV['APP_DEBUG'] ?? '0') === '1';
$APP_TIMEZONE = $_ENV['APP_TIMEZONE'] ?? 'UTC';

date_default_timezone_set($APP_TIMEZONE);


/* ==========================================================
   APPLICATION CONSTANTS
========================================================== */
/*
 | IMPORTANT RULE:
 | - APP_BASE → NEVER end with slash
 | - APP_URL  → ALWAYS end with slash
 */

// Local (Docker) vs Live detection
$http_host = $_SERVER['HTTP_HOST'] ?? '';
$is_local  = str_contains($http_host, 'localhost') || str_contains($http_host, '127.0.0.1');

if ($is_local) {
    // Local Docker — no subfolder, URL from .env (http://localhost:8082/)
    define("APP_BASE", "");
    define("APP_URL", rtrim(
        $_ENV['APP_URL'] ?? "http://" . $http_host . "/",
        '/'
    ) . '/');
} else {
    // Live server
    define("APP_BASE", "/ecwid/googlesearchconsole");
    define("APP_URL", rtrim(
        $_ENV['APP_URL'] ?? "https://makkpressapps.com/ecwid/googlesearchconsole/",
        '/'
    ) . '/');
}

// 🔥 Proper HOST_URL for OAuth + redirects
// Always ends with slash
define("HOST_URL", APP_URL);


/* ==========================================================
   DEBUG MODE
========================================================== */

if ($APP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
}