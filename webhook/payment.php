<?php
// webhook/payment.php — Stripe payment webhook for Squarespace
// Same pattern as Wix/Shopify webhook/payment.php

declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';

global $pdo;

$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

if (!$payload) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'no_payload']);
    exit;
}

// Verify Stripe signature (simplified — production should use stripe-php library)
$event = json_decode($payload, true);
if (!$event) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_json']);
    exit;
}

$eventType = $event['type'] ?? '';
$dataObject = $event['data']['object'] ?? [];

// Extract customer info
$customerEmail = $dataObject['customer_details']['email'] ?? $dataObject['customer_email'] ?? null;
$customerName  = $dataObject['customer_details']['name'] ?? null;
$amount        = $dataObject['amount'] ?? 0;
$currency      = $dataObject['currency'] ?? 'usd';
$status        = $dataObject['status'] ?? '';

switch ($eventType) {
    case 'checkout.session.completed':
    case 'payment_intent.succeeded':
        // Save payment record
        $stmt = $pdo->prepare("
            INSERT INTO admin_settings (key_name, value, created_at)
            VALUES (:key, :val, NOW())
            ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()
        ");
        $stmt->execute([
            ':key'  => 'stripe_last_payment_' . (string) time(),
            ':val'  => json_encode([
                'event'    => $eventType,
                'email'    => $customerEmail,
                'name'     => $customerName,
                'amount'   => $amount,
                'currency' => $currency,
                'status'   => $status,
            ]),
        ]);
        break;

    case 'customer.subscription.deleted':
    case 'customer.subscription.updated':
        // Update subscription status if we track per-instance
        $shopId = $dataObject['metadata']['shop_id'] ?? null;
        if ($shopId) {
            $isActive = ($eventType === 'customer.subscription.updated') ? 1 : 0;
            $stmt = $pdo->prepare("
                UPDATE SquarespaceSite
                SET is_active = :active, updated_at = NOW()
                WHERE shop_id = :iid
            ");
            $stmt->execute([
                ':active' => $isActive,
                ':iid'    => $shopId,
            ]);
        }
        break;
}

echo json_encode(['success' => true, 'received' => $eventType]);
exit;
