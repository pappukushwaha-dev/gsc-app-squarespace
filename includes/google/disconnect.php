<?php
// includes/google/disconnect.php — Disconnect Google account
declare(strict_types=1);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/credentials.php';

$instanceId = $_SESSION['instanceid'] ?? null;

if (!$instanceId) {
    echo json_encode(['success' => false, 'error' => 'missing_instance']);
    exit;
}

global $pdo;
$pdo->prepare("UPDATE google_accounts SET connected = 0, access_token = NULL, refresh_token = NULL WHERE instance_id = ?")
    ->execute([$instanceId]);

$_SESSION['google_connected'] = false;

echo json_encode(['success' => true, 'message' => 'Google account disconnected']);
