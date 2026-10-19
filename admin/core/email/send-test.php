<?php
// admin/core/email/send-test.php — Send test email
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$to = $data['to'] ?? '';

if (!$to) {
    echo json_encode(['success' => false, 'error' => 'missing_email']);
    exit;
}

try {
    $sent = mail($to, 'Test Email', 'This is a test email from GSC Squarespace App.');
    echo json_encode(['success' => $sent, 'message' => $sent ? 'Test email sent' : 'Failed to send']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
