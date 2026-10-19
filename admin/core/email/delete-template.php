<?php
// admin/core/email/delete-template.php — Delete email template
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$templateId = $data['template_id'] ?? null;

if (!$templateId) {
    echo json_encode(['success' => false, 'error' => 'missing_template_id']);
    exit;
}

try {
    $pdo->prepare("DELETE FROM email_templates WHERE id = ?")->execute([$templateId]);
    echo json_encode(['success' => true, 'message' => 'Template deleted']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
