<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$shopId = $data['shop_id'] ?? null;

if (!$shopId) {
    echo json_encode(['success' => false]);
    exit;
}

// Note: app_reviews table doesn't have a 'submitted' column
// This endpoint is kept for compatibility but doesn't update anything
// The review popup will handle the "already submitted" state via check-review.php
$stmt = $pdo->prepare("
    SELECT id
    FROM app_reviews
    WHERE shop_id = :id
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([':id' => $shopId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['success' => true]);
exit;
