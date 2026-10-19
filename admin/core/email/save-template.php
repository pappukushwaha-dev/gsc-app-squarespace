<?php
// admin/core/email/save-template.php — Save email template
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$name = $data['name'] ?? '';
$subject = $data['subject'] ?? '';
$body = $data['body'] ?? '';
$categoryId = $data['category_id'] ?? null;

if (!$name || !$subject) {
    echo json_encode(['success' => false, 'error' => 'missing_fields']);
    exit;
}

try {
    $pdo->prepare("INSERT INTO email_templates (name, subject, body, category_id, created_at) VALUES (?, ?, ?, ?, NOW())")
        ->execute([$name, $subject, $body, $categoryId]);
    echo json_encode(['success' => true, 'message' => 'Template saved']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
