<?php
// includes/google/disconnect.php — Disconnect Google account
declare(strict_types=1);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/credentials.php';

$shopId = $_SESSION['shop_id'] ?? null;

if (!$shopId) {
    echo json_encode(['success' => false, 'error' => 'missing_instance']);
    exit;
}

global $pdo;
$pdo->prepare("UPDATE google_accounts SET connected = 0, access_token = NULL, refresh_token = NULL WHERE shop_id = ?")
    ->execute([$shopId]);

$_SESSION['google_connected'] = false;

echo json_encode(['success' => true, 'message' => 'Google account disconnected']);
