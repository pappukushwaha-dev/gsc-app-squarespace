<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/tickets_min.php';

// read body (JSON)
$input = json_decode(file_get_contents('php://input'), true) ?: [];

$shopId = trim($input['shop_id'] ?? $_SESSION['shop_id'] ?? '');
$subject = trim($input['subject'] ?? '');

if ($shopId === '' || $subject === '') {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$res = createTicketMinimal($pdo, ['shop_id' => $shopId, 'subject' => $subject]);
echo json_encode($res);
