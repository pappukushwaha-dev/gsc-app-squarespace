<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

$shopId = $_GET['shop_id'] ?? null;

if (!$shopId) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, rating, submitted
    FROM app_reviews
    WHERE shop_id = :id
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([':id' => $shopId]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode([
        'success' => true,
        'exists'  => false
    ]);
    exit;
}

echo json_encode([
    'success'   => true,
    'exists'    => true,
    'rating'    => $row['rating'],
    'submitted' => (int)$row['submitted']
]);
exit;
