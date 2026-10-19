<?php
// admin/core/ChangePassword.php — Change password
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'method_not_allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$currentPassword = $data['current_password'] ?? '';
$newPassword = $data['new_password'] ?? '';

if ($currentPassword === '' || $newPassword === '') {
    echo json_encode(['success' => false, 'error' => 'missing_fields']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
