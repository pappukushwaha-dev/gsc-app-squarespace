<?php
// webhook/app-uninstall.php — Squarespace app uninstall webhook
// Same pattern as Wix webhook/app-uninstall.php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';

global $pdo;

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$instanceId = $input['instanceId'] ?? $input['site_id'] ?? $input['instance_id'] ?? null;

if (!$instanceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'missing_instance_id']);
    exit;
}

// Mark site as inactive instead of deleting (preserve history)
$stmt = $pdo->prepare("
    UPDATE SquarespaceSite
    SET is_active = 0, event_type = 'UNINSTALLED', uninstalled_at = NOW(), updated_at = NOW()
    WHERE instance_id = ?
");
$stmt->execute([$instanceId]);

// Also mark verification as failed
$stmt2 = $pdo->prepare("
    UPDATE gsc_domain_verifications
    SET verification_status = 'failed', updated_at = NOW()
    WHERE instance_id = ?
");
$stmt2->execute([$instanceId]);

echo json_encode([
    'success' => true,
    'instance_id' => $instanceId,
    'message' => 'App uninstalled',
]);
exit;
