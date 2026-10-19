<?php

declare(strict_types=1);

/**
 * Squarespace GSC App — includes/config.php
 * Database connection + global constants.
 * Same pattern as Wix/Shopify/BigCommerce.
 */

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---- Database connection ----
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
        'message' => $e->getMessage()
    ]);
    exit;
}

$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// ---- Constants ----
define('APP_BASE',          '/gsc-app-squarespace');
define('APP_NAME',          'Google Search Console — Squarespace');
define('APP_PLATFORM',      'squarespace');
define('ENCRYPTION_KEY',    $_ENV['ENCRYPTION_KEY'] ?? getenv('ENCRYPTION_KEY') ?? '');
define('GOOGLE_CLIENT_ID',  $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?? '');
define('GOOGLE_REDIRECT_URI', $_ENV['GOOGLE_REDIRECT_URI'] ?? getenv('GOOGLE_REDIRECT_URI') ?? '');

define('GSC_SCOPES', 'https://www.googleapis.com/auth/webmasters.readonly https://www.googleapis.com/auth/webmasters');

echo 'config_loaded';
