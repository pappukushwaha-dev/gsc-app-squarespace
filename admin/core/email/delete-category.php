<?php
// admin/core/email/delete-category.php — Delete email category
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$categoryId = $data['category_id'] ?? null;

if (!$categoryId) {
    echo json_encode(['success' => false, 'error' => 'missing_category_id']);
    exit;
}

try {
    $pdo->prepare("DELETE FROM email_categories WHERE id = ?")->execute([$categoryId]);
    echo json_encode(['success' => true, 'message' => 'Category deleted']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
