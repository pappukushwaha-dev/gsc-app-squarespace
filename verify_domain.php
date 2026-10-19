<?php
// verify_domain.php — Standalone verify domain endpoint
declare(strict_types=1);

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/credentials.php';
require_once __DIR__ . '/includes/google/get_account.php';
require_once __DIR__ . '/includes/google/token_manager.php';

$instanceId = $_SESSION['instanceid'] ?? null;

if (!$instanceId) {
    echo json_encode(['success' => false, 'error' => 'missing_instance']);
    exit;
}

include __DIR__ . '/api/google/verify_domain.php';
