<?php
// admin/core/email/save-category.php — Save email category
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$name = $data['name'] ?? '';

if (!$name) {
    echo json_encode(['success' => false, 'error' => 'missing_name']);
    exit;
}

try {
    $pdo->prepare("INSERT INTO email_categories (name, created_at) VALUES (?, NOW())")->execute([$name]);
    echo json_encode(['success' => true, 'message' => 'Category saved']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
