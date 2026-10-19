<?php
// api/billing.php — plan activation + Stripe checkout creation.
// Free plan: DB-only activation. Paid: redirect to Stripe Checkout;
// the webhook completes the subscription.

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';
require_once __DIR__ . '/../vendor/autoload.php';

global $pdo;

/* ---------- Stripe credentials (DB-backed) ---------- */

try {
    $stripeCreds = get_stripe_credentials($pdo);
} catch (Throwable $e) {
    error_log('Stripe credential error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Stripe credentials unavailable']);
    exit;
}

if (empty($stripeCreds['secret_key'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Stripe secret key not configured']);
    exit;
}

\Stripe\Stripe::setApiKey($stripeCreds['secret_key']);

/* ---------- identity ---------- */

$shopId = trim((string) ($_POST['shop_id']
    ?? $_GET['shop_id']
    ?? $_SESSION['shop_id']
    ?? ''));

if ($shopId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing shop_id']);
    exit;
}

$_SESSION['shop_id'] = $shopId;

/* ---------- input ---------- */

$priceId       = $_POST['price_id'] ?? null;
$billingPeriod = $_POST['billing_period'] ?? null;

if (!in_array($billingPeriod, ['monthly', 'yearly', 'free'], true)
    || ($billingPeriod !== 'free' && !$priceId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid plan request']);
    exit;
}

/* ---------- plan lookup (by billing period + stripe price) ---------- */

$stmt = $pdo->prepare("
    SELECT id, name, code, price, billingPeriod
    FROM pricing_plans
    WHERE billingPeriod = :bp AND showInMarket = 1
    LIMIT 1
");
$stmt->execute([':bp' => $billingPeriod]);
$plan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plan) {
    http_response_code(404);
    echo json_encode(['error' => 'Plan not found']);
    exit;
}

$price  = (float) $plan['price'];
$isFree = ($billingPeriod === 'free' || $price <= 0.0);

/* ---------- active-subscription guard ---------- */

$check = $pdo->prepare("
    SELECT billing_period FROM app_subscriptions
    WHERE shop_id = :shop_id AND status = 'active'
    LIMIT 1
");
$check->execute([':shop_id' => $shopId]);
$activeBillingPeriod = $check->fetchColumn();

if ($activeBillingPeriod && $activeBillingPeriod !== 'free' && $isFree) {
    http_response_code(409);
    echo json_encode(['error' => 'Cannot downgrade to Free while a paid plan is active']);
    exit;
}

/* ---------- FREE plan: DB-only activation ---------- */

if ($isFree) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO app_subscriptions
                (shop_id, stripe_subscription_id, customer_id, plan_name,
                 billing_period, status, price, currency, raw_event,
                 started_at, expires_on, cancelled_at, payment_method)
            VALUES
                (:shop_id, NULL, NULL, :plan_name, 'free', 'active', 0, 'USD',
                 NULL, NOW(), NULL, NULL, 'free')
            ON DUPLICATE KEY UPDATE
                stripe_subscription_id = NULL,
                customer_id            = NULL,
                plan_name              = VALUES(plan_name),
                billing_period         = 'free',
                status                 = 'active',
                price                  = 0,
                currency               = 'USD',
                raw_event              = NULL,
                started_at             = NOW(),
                expires_on             = NULL,
                cancelled_at           = NULL,
                payment_method         = 'free',
                updated_at             = NOW()
        ");
        $stmt->execute([
            ':shop_id'   => $shopId,
            ':plan_name' => $plan['name'],
        ]);

        $pdo->commit();

        header('Location: ' . APP_URL . '/dashboard.php?shop_id=' . urlencode($shopId));
        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Free plan activation failed: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to activate free plan']);
        exit;
    }
}

/* ---------- PAID plan: owner details + Stripe Checkout ---------- */

$stmt = $pdo->prepare("
    SELECT owner_email AS email, domain
    FROM SquarespaceSite
    WHERE shop_id = :shop_id
    LIMIT 1
");
$stmt->execute([':shop_id' => $shopId]);
$site = $stmt->fetch(PDO::FETCH_ASSOC);

$ownerEmail = trim((string) ($site['email'] ?? ''));
$websiteUrl = trim((string) ($site['domain'] ?? ''));

if ($ownerEmail === '' || !filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Owner email not found for this shop']);
    exit;
}
if ($websiteUrl === '' || !filter_var('https://' . preg_replace('#^https?://#i', '', $websiteUrl), FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or missing website URL']);
    exit;
}
if (!preg_match('#^https?://#i', $websiteUrl)) {
    $websiteUrl = 'https://' . $websiteUrl;
}

try {
    $session = \Stripe\Checkout\Session::create([
        'mode'                 => 'subscription',
        'customer_email'       => $ownerEmail,
        'allow_promotion_codes' => true,
        'line_items'           => [[
            'price'    => $priceId,
            'quantity' => 1,
        ]],
        'subscription_data' => [
            'metadata' => [
                'shop_id'        => $shopId,
                'platform'       => 'squarespace-googlesearchconsole',
                'billing_period' => $billingPeriod,
                'price_id'       => $priceId,
                'website_url'    => $websiteUrl,
            ],
        ],
        'success_url' => APP_URL . '/thank-you.php?session_id={CHECKOUT_SESSION_ID}&shop_id=' . urlencode($shopId),
        'cancel_url'  => APP_URL . '/pricing.php',
    ]);

    header('Location: ' . $session->url);
    exit;

} catch (Throwable $e) {
    error_log('Stripe checkout error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error'       => 'Stripe checkout failed',
        'stripe_error' => $e->getMessage(),
    ]);
}
