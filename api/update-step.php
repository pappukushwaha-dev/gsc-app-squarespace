<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$shopId = $_SESSION['shop_id'] ?? $_SESSION['shop_id'] ?? $_POST['shopId']?? null;
if (!$shopId) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Missing instance ID']);
    exit;
}

$step = $_POST['step'] ?? null;
if (!in_array($step, ['1', '2', '3'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid step']);
    exit;
}

try {
    $column = 'step' . intval($step);
    $stmt = $pdo->prepare("
        INSERT INTO setup_wizard_status (shop_id, $column, created_at)
        VALUES (:shop_id, 1, NOW(3))
        ON DUPLICATE KEY UPDATE $column = 1, updated_at = NOW(3)
    ");
    $stmt->execute([':shop_id' => $shopId]);
    echo json_encode(['success' => true, 'message' => "Step {$step} saved."]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
