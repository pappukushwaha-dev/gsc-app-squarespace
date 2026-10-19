<?php

declare(strict_types=1);

/**
 * Squarespace GSC App — includes/config.php
 *
 * .env loading + database connection + global constants.
 *
 * Order matters: the .env is parsed FIRST (into $_ENV + putenv) so every
 * later env read — DB creds, ENCRYPTION_SECRET — sees real values.
 *
 * Nothing is echoed here. Debug output is controlled by APP_DEBUG in the
 * .env (default: off) — a stray echo before headers breaks every redirect
 * and every JSON endpoint.
 */

/* ---------- .env loader (no composer dependency needed) ---------- */

$gscEnvFile = dirname(__DIR__) . '/.env';
if (is_file($gscEnvFile)) {
    foreach (file($gscEnvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $gscLine) {
        $gscLine = trim($gscLine);
        if ($gscLine === '' || str_starts_with($gscLine, '#') || !str_contains($gscLine, '=')) {
            continue;
        }
        [$gscK, $gscV] = array_pad(explode('=', $gscLine, 2), 2, '');
        $gscK = trim($gscK);
        $gscV = trim(trim($gscV), "\"'");
        if ($gscK !== '' && !array_key_exists($gscK, $_ENV)) {
            $_ENV[$gscK] = $gscV;
            putenv("$gscK=$gscV");
        }
    }
}
unset($gscEnvFile, $gscLine, $gscK, $gscV);

/* ---------- error display: APP_DEBUG decides, default OFF ---------- */

$gscDebug = in_array(strtolower((string) ($_ENV['APP_DEBUG'] ?? '0')), ['1', 'true', 'yes'], true);
ini_set('display_errors', $gscDebug ? '1' : '0');
ini_set('display_startup_errors', $gscDebug ? '1' : '0');
error_reporting($gscDebug ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_NOTICE);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ---------- database connection ---------- */

$host     = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
$dbname   = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'squarespace_googlesearchconsole';
$dbuser   = $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'root';
$dbpass   = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? '';
$charset  = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'db_connection_failed',
    ]);
    exit;
}

$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

/* ---------- encryption key (family derivation) ----------
   The .env carries ENCRYPTION_SECRET. base64: prefix is decoded; the
   resulting key is padded/truncated to exactly 32 bytes for AES-256.
   credentials.php reuses this via defined() checks. */
$gscRawSecret = $_ENV['ENCRYPTION_SECRET'] ?? getenv('ENCRYPTION_SECRET') ?: '';
if (!defined('ENCRYPTION_KEY')) {
    $gscKey = str_starts_with($gscRawSecret, 'base64:')
        ? base64_decode(substr($gscRawSecret, 7))
        : $gscRawSecret;
    if ($gscKey === false || $gscKey === '') {
        $gscKey = str_pad('squarespace-gsc-fallback', 32, "\0");
    }
    if (strlen($gscKey) < 32) {
        $gscKey = str_pad($gscKey, 32, "\0");
    } elseif (strlen($gscKey) > 32) {
        $gscKey = substr($gscKey, 0, 32);
    }
    define('ENCRYPTION_KEY', $gscKey);
}
unset($gscRawSecret, $gscKey);

/* ---------- app constants ---------- */

define('APP_BASE',     '/squarespace/googlesearchconsole');
define('APP_URL',      rtrim((string) ($_ENV['APP_URL'] ?? 'https://makkpressapps.com/squarespace/googlesearchconsole'), '/'));
define('APP_NAME',     'Google Search Console — Squarespace');
define('APP_PLATFORM', 'squarespace');

define('GOOGLE_CLIENT_ID',     $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?? '');
define('GOOGLE_REDIRECT_URI',  $_ENV['GOOGLE_REDIRECT_URI'] ?? getenv('GOOGLE_REDIRECT_URI') ?? '');

define('GSC_SCOPES', 'https://www.googleapis.com/auth/webmasters.readonly https://www.googleapis.com/auth/webmasters');

define('STRIPE_SECRET_KEY',     $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY') ?? '');
define('STRIPE_PUBLIC_KEY',     $_ENV['STRIPE_PUBLIC_KEY'] ?? getenv('STRIPE_PUBLIC_KEY') ?? '');
define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET'] ?? getenv('STRIPE_WEBHOOK_SECRET') ?? '');

define('MAIL_FROM', $_ENV['MAIL_FROM'] ?? 'noreply@makkpressapps.com');
