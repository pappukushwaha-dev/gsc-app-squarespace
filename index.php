<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

/*
|--------------------------------------------------------------------------
| Shopify GDPR Webhook Router
|--------------------------------------------------------------------------
*/

$rawBody = file_get_contents('php://input');
$hmac    = $_SERVER['HTTP_X_SHOPIFY_HMAC_SHA256'] ?? '';

/*
|--------------------------------------------------------------------------
| Shopify may call /webhook with invalid or missing HMAC on purpose
| MUST return 401 (NOT 400)
|--------------------------------------------------------------------------
*/
if (!$rawBody || !$hmac) {
    http_response_code(401);
    exit;
}

$calculated = base64_encode(
    hash_hmac('sha256', $rawBody, $appSecretKey, true)
);

if (!hash_equals($calculated, $hmac)) {
    http_response_code(401);
    exit;
}

$topic = $_SERVER['HTTP_X_SHOPIFY_TOPIC'] ?? '';

switch ($topic) {
    case 'customers/data_request':
        require __DIR__ . '/webhook_gdpr_customers_data_request.php';
        break;

    case 'customers/redact':
        require __DIR__ . '/webhook_gdpr_customers_redact.php';
        break;

    case 'shop/redact':
        require __DIR__ . '/webhook_gdpr_shop_redact.php';
        break;

    default:
        // Shopify may ping base URL
        break;
}

http_response_code(200);
echo 'OK';
