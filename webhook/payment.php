<?php
// webhook/payment.php — Stripe webhook (Squarespace platform)
// WordPress-parity: signature verification, subscription upsert keyed on
// shop_id (from checkout metadata), plan resolution by price_id, and
// cancellation handling. Unrecognised prices are skipped (other apps on
// the same Stripe account have their own endpoints).

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';
require_once __DIR__ . '/../vendor/autoload.php';

global $pdo;
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$payload   = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

$stripeCreds    = get_stripe_credentials($pdo);
$endpointSecret = $stripeCreds['webhook_secret'] ?? null;

if (!$endpointSecret) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'webhook_secret_not_configured']);
    exit;
}

function logWebhookEvent(PDO $pdo, string $stage, string $raw, ?string $eventType = null, ?string $error = null): void
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO webhook_test_logs (stage, event_type, raw_data, error_message)
            VALUES (:stage, :event, :raw, :error)
        ");
        $stmt->execute([
            ':stage' => $stage,
            ':event' => $eventType,
            ':raw'   => substr(json_encode($raw, JSON_PRETTY_PRINT), 0, 60000),
            ':error' => $error,
        ]);
    } catch (Throwable $e) {
        error_log('webhook log failed: ' . $e->getMessage());
    }
}

try {
    $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
} catch (Throwable $e) {
    logWebhookEvent($pdo, 'SIGNATURE_FAILED', $payload, null, $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'signature_verification_failed']);
    exit;
}

logWebhookEvent($pdo, 'EVENT_OK', $payload, $event->type);
$dataObject = $event->data->object ?? null;

/**
 * Resolve the local plan from a Stripe price id.
 */
function getPlanByPrice(PDO $pdo, string $priceId): ?array
{
    $stmt = $pdo->prepare("
        SELECT id, name, billingPeriod, price
        FROM pricing_plans
        WHERE price_id = :pid
        LIMIT 1
    ");
    $stmt->execute([':pid' => $priceId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

switch ($event->type) {

    case 'customer.subscription.created':
    case 'customer.subscription.updated': {

        $subscription = $dataObject;
        $shopId  = $subscription->metadata->shop_id ?? null;
        $period  = $subscription->metadata->billing_period ?? null;
        $priceId = $subscription->items->data[0]->price->id ?? null;
        $status  = $subscription->status ?? '';
        $custId  = $subscription->customer ?? null;
        $periodEnd = $subscription->items->data[0]->current_period_end ?? null;

        if (!$shopId || !$priceId) {
            logWebhookEvent($pdo, 'SKIPPED - no shop_id/price_id', $payload, $event->type);
            break;
        }

        $plan = getPlanByPrice($pdo, $priceId);
        if (!$plan) {
            logWebhookEvent($pdo, 'SKIPPED - PNF', $payload, $event->type, 'Plan not found for ' . $priceId);
            break;
        }

        $isActive = in_array($status, ['active', 'trialing'], true);
        $expiresOn = $periodEnd ? date('Y-m-d H:i:s', (int) $periodEnd) : null;

        $stmt = $pdo->prepare("
            INSERT INTO app_subscriptions
                (shop_id, stripe_subscription_id, customer_id, plan_name, billing_period,
                 status, price, currency, raw_event, started_at, expires_on, payment_method)
            VALUES
                (:shop_id, :sub, :cust, :plan, :period, :status, :price, 'USD', :raw,
                 NOW(), :expires, 'stripe')
            ON DUPLICATE KEY UPDATE
                stripe_subscription_id = VALUES(stripe_subscription_id),
                customer_id            = VALUES(customer_id),
                plan_name              = VALUES(plan_name),
                billing_period         = VALUES(billing_period),
                status                 = VALUES(status),
                price                  = VALUES(price),
                expires_on             = VALUES(expires_on),
                raw_event              = VALUES(raw_event),
                updated_at             = NOW()
        ");
        $stmt->execute([
            ':shop_id' => $shopId,
            ':sub'     => $subscription->id,
            ':cust'    => $custId,
            ':plan'    => $plan['name'],
            ':period'  => $period ?: $plan['billingPeriod'],
            ':status'  => $isActive ? 'active' : 'inactive',
            ':price'   => $plan['price'],
            ':raw'     => json_encode($event, JSON_UNESCAPED_SLASHES),
            ':expires' => $expiresOn,
        ]);

        // keep the site active while its subscription is
        $pdo->prepare("UPDATE SquarespaceSite SET is_active = 1 WHERE shop_id = ?")
            ->execute([$shopId]);

        logWebhookEvent($pdo, 'SUB_SAVED', $payload, $event->type);
        break;
    }

    case 'customer.subscription.deleted': {

        $shopId = $dataObject->metadata->shop_id ?? null;
        if ($shopId) {
            $pdo->prepare("
                UPDATE app_subscriptions
                SET status = 'cancelled', cancelled_at = NOW()
                WHERE shop_id = ?
            ")->execute([$shopId]);

            logWebhookEvent($pdo, 'SUB_CANCELLED', $payload, $event->type);
        }
        break;
    }

    case 'invoice.payment_succeeded': {
        // renewals land here: refresh the expiry from the subscription period
        $subId = $dataObject->subscription ?? null;
        if ($subId) {
            try {
                $sub = \Stripe\Subscription::retrieve($subId);
                $shopId = $sub->metadata->shop_id ?? null;
                $periodEnd = $sub->items->data[0]->current_period_end ?? null;
                if ($shopId && $periodEnd) {
                    $pdo->prepare("
                        UPDATE app_subscriptions
                        SET status = 'active', expires_on = FROM_UNIXTIME(?)
                        WHERE shop_id = ?
                    ")->execute([$periodEnd, $shopId]);
                }
            } catch (Throwable $e) {
                error_log('invoice.payment_succeeded refresh: ' . $e->getMessage());
            }
        }
        break;
    }

    default:
        // other event types are received and ignored
        break;
}

echo json_encode(['success' => true, 'received' => $event->type]);
exit;
