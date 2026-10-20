<?php
// includes/config.php — Squarespace GSC App
// .env loading (phpdotenv) + DB (db.php) + global constants.

declare(strict_types=1);

/* ==========================================================
   VENDOR AUTOLOAD (phpdotenv, google client, stripe, phpmailer)
========================================================== */
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

/* ==========================================================
   .ENV (safe load — missing file is not fatal)
========================================================== */
Dotenv::createImmutable(__DIR__ . '/../')->safeLoad();

/* ==========================================================
   DEBUG (APP_DEBUG=1 shows errors — keep 0 in production)
========================================================== */
$APP_DEBUG = ($_ENV['APP_DEBUG'] ?? '0') === '1';
ini_set('display_errors', $APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', $APP_DEBUG ? '1' : '0');
error_reporting($APP_DEBUG ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ==========================================================
   DATABASE (includes/db.php connects + exposes $pdo)
========================================================== */
require_once __DIR__ . '/db.php';

/* ==========================================================
   APPLICATION CONSTANTS
========================================================== */
/*
 | APP_BASE  → subfolder path, never ends with a slash
 | APP_URL   → full URL, always ends with a slash
 | Locally (php -S from the app folder) the app IS the web root,
 | so APP_BASE is empty and the URL comes from .env.
 */
$httpHost = $_SERVER['HTTP_HOST'] ?? '';
$isLocal  = str_contains($httpHost, 'localhost') || str_contains($httpHost, '127.0.0.1');

define('APP_BASE', $isLocal ? '' : '/squarespace/googlesearchconsole');

$appUrl = rtrim((string) ($_ENV['APP_URL'] ?? ($isLocal
    ? 'http://127.0.0.1:8099'
    : 'https://makkpressapps.com/squarespace/googlesearchconsole')), '/');
define('APP_URL',  $appUrl . '/');
define('BASE_URL', $appUrl . '/');
define('HOST_URL', APP_URL);

define('APP_NAME',     'Google Search Console — Squarespace');
define('APP_PLATFORM', 'squarespace');

/* ==========================================================
   ENCRYPTION KEY (family derivation from ENCRYPTION_SECRET)
========================================================== */
$rawKey = $_ENV['ENCRYPTION_SECRET'] ?? getenv('ENCRYPTION_SECRET') ?: '';
if (!defined('ENCRYPTION_KEY')) {
    $key = str_starts_with($rawKey, 'base64:')
        ? base64_decode(substr($rawKey, 7))
        : $rawKey;
    if ($key === false || $key === '') {
        $key = str_pad('squarespace-gsc', 32, "\0");
    }
    if (strlen($key) < 32) {
        $key = str_pad($key, 32, "\0");
    } elseif (strlen($key) > 32) {
        $key = substr($key, 0, 32);
    }
    define('ENCRYPTION_KEY', $key);
}

/* ==========================================================
   GOOGLE OAUTH (google_settings table wins; .env is fallback)
   Saved from: admin panel → Settings → Google settings
========================================================== */
define('GOOGLE_CLIENT_ID',     $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?? '');
define('GOOGLE_REDIRECT_URI',  $_ENV['GOOGLE_REDIRECT_URI'] ?? getenv('GOOGLE_REDIRECT_URI') ?? '');

define('GSC_SCOPES', 'https://www.googleapis.com/auth/webmasters.readonly https://www.googleapis.com/auth/webmasters');

/* ==========================================================
   STRIPE (fallbacks — the real keys live in admin_settings,
   saved from: admin panel → Settings → Stripe Integration)
========================================================== */
define('STRIPE_PUBLIC_KEY',     $_ENV['STRIPE_PUBLIC_KEY'] ?? getenv('STRIPE_PUBLIC_KEY') ?? '');
define('STRIPE_SECRET_KEY',     $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY') ?? '');
define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET'] ?? getenv('STRIPE_WEBHOOK_SECRET') ?? '');

/* ==========================================================
   MAIL FROM (magic links + notifications)
========================================================== */
define('MAIL_FROM', $_ENV['MAIL_FROM'] ?? 'noreply@makkpressapps.com');
