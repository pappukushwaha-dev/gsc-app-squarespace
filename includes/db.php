<?php
// includes/db.php — Database connection
declare(strict_types=1);

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
    echo json_encode(['success' => false, 'error' => 'db_connection_failed', 'message' => $e->getMessage()]);
    exit;
}

$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
