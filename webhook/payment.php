<?php
declare(strict_types=1);

require __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

use Stripe\Webhook;

$stripeCreds = get_stripe_credentials($pdo);

$endpointSecret = $stripeCreds['webhook_secret'];

if (!$endpointSecret) {
    http_response_code(500);
    echo 'Webhook secret not configured';
    exit;
}

function logToFile(string $label, $data): void
{
    $logFile = '/var/www/private-logs/squarespace/googlesearchconsole/webhook/stripe_webhook_debug.txt';

    $entry = "============================\n";
    $entry .= date('Y-m-d H:i:s') . " | " . $label . "\n";

    if (is_string($data)) {
        $entry .= $data . "\n";
    } else {
        $entry .= print_r($data, true) . "\n";
    }

    $entry .= "============================\n\n";

    file_put_contents($logFile, $entry, FILE_APPEND);
}
// logger
function logWebhook(PDO $pdo, string $stage, $data, ?string $eventType = null, ?string $error = null)
{
    $stmt = $pdo->prepare("
        INSERT INTO webhook_test_logs (stage, event_type, raw_data, error_message)
        VALUES (:stage, :event, :raw, :error)
    ");

    $stmt->execute([
        ':stage' => $stage,
        ':event' => $eventType,
        ':raw'   => json_encode($data, JSON_PRETTY_PRINT),
        ':error' => $error
    ]);
}

// Raw input
$payload = file_get_contents('php://input');

$headers = function_exists('getallheaders') ? getallheaders() : [];

$sigHeader = 
    $_SERVER['HTTP_STRIPE_SIGNATURE'] 
    ?? $_SERVER['REDIRECT_HTTP_STRIPE_SIGNATURE'] 
    ?? ($headers['Stripe-Signature'] ?? '')
    ?? '';

logWebhook($pdo, 'RAW 01', $payload);
logWebhook($pdo, 'HEADERS', $headers);

// verify signature
try {
    $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        logToFile('SIGNATURE OK', $event->type);

} catch (Throwable $e) {
    logWebhook($pdo, 'SIGNATURE_FAILED', $payload, null, $e->getMessage());
     logToFile('SIGNATURE FAILED', $e->getMessage());
    logToFile('FAILED PAYLOAD', $payload);
    http_response_code(400);
    exit;
}

// Only this app's events. All GSC apps share ONE Stripe account and every
// endpoint receives every event; without this filter a purchase made on
// another platform was written into this app's database.
const GSC_STRIPE_PLATFORM = 'squarespace-googlesearchconsole';
function gscStripeEventPlatform($event): ?string
{
    $o = $event->data->object ?? null;
    if (!$o) {
        return null;
    }
    $m = $o->metadata->platform ?? null;
    if (!$m && isset($o->lines->data[0])) {
        $m = $o->lines->data[0]->metadata->platform ?? null;
    }
    if (!$m) {
        $m = $o->parent->subscription_details->metadata->platform ?? null;
    }
    return $m ? (string)$m : null;
}
$gscEventPlatform = gscStripeEventPlatform($event);
if ($gscEventPlatform !== null && strcasecmp($gscEventPlatform, GSC_STRIPE_PLATFORM) !== 0) {
    logWebhook($pdo, 'SKIPPED_OTHER_PLATFORM', ['platform' => $gscEventPlatform], $event->type);
    http_response_code(200);
    echo json_encode(['success' => true, 'skipped' => 'other platform']);
    exit;
}

// helper get plan
function getPlan(PDO $pdo, string $priceId): ?array
{
    $stmt = $pdo->prepare("
        SELECT name, billingPeriod
        FROM pricing_plans
        WHERE price_id = :pid
        LIMIT 1
    ");
    $stmt->execute([':pid' => $priceId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// event handle
switch ($event->type) {

    // subscription add/update
    case 'customer.subscription.created':
    case 'customer.subscription.updated':

        $sub = $event->data->object;
        $customerId = $sub->customer ?? null;
        // logToFile('SUB EVENT', $event->type);
        // logToFile('SUB OBJECT', $sub);
        $item = $sub->items->data[0] ?? null;

        $meta = $sub->metadata ?? (object)[];

       $shopId = $meta->shop_id ?? null;
        $priceId = $item->price->id ?? null;

        logWebhook($pdo, 'SUB_META', [
            'subscription' => $sub->id,
            'shop_id' => $shopId,
            'price_id' => $priceId
        ], $event->type);


        if (!$priceId || !$shopId) {
            logWebhook($pdo, 'SKIPPED - PUS', [
                'priceId' => $priceId,
                'shop_id'  => $shopId,
            ], $event->type, 'Missing price/user/site');
            break;
        }

        $plan = getPlan($pdo, $priceId);
        if (!$plan) {
            logWebhook($pdo, 'SKIPPED - PNF', [
                'priceId' => $priceId
            ], $event->type, 'Plan not found');
            break;
        }

        $stmt = $pdo->prepare("
            INSERT INTO app_subscriptions
            (shop_id, stripe_subscription_id, customer_id, plan_name, billing_period,
            status, price, currency, raw_event, started_at, expires_on, payment_method)
            VALUES
            (:instid,:sub,:cid,:plan,:period,:status,:price,:currency,:raw,
            FROM_UNIXTIME(:start),FROM_UNIXTIME(:end), :pm)
            ON DUPLICATE KEY UPDATE
                /* Paid-until date. Stripe moves current_period_end forward at
                   renewal BEFORE the card is charged (and even when the charge
                   fails -> past_due), so for an existing subscription only
                   invoice.payment_succeeded may extend expires_on. It is set
                   here only for a new subscription / empty value. Must stay
                   the FIRST assignment: it compares the stored
                   stripe_subscription_id before it is overwritten below. */
                expires_on = IF(expires_on IS NOT NULL
                                AND stripe_subscription_id <=> VALUES(stripe_subscription_id),
                                expires_on, VALUES(expires_on)),
                status = VALUES(status),
                stripe_subscription_id = VALUES(stripe_subscription_id),
                customer_id = COALESCE(VALUES(customer_id), customer_id),
                price = VALUES(price),
                currency = VALUES(currency),
                raw_event = VALUES(raw_event),
                plan_name = VALUES(plan_name),
                billing_period = VALUES(billing_period),
                /* Cancel at period end: keep the paid period, record when
                   it will end; cleared again when auto-renew is turned back
                   on. Access is decided by expires_on, not this column. */
                cancelled_at = :cancel_at,
                payment_method = VALUES(payment_method),
                updated_at = NOW()
        ");

        /* Since Stripe API 2025-03 current_period_start/end live on the
           subscription ITEM, not the subscription; reading only the old
           fields gave NULL -> started_at NOT NULL violation -> HTTP 500 on
           every subscription event. */
        $periodStart = $sub->current_period_start ?? ($item->current_period_start ?? null) ?? ($sub->start_date ?? time());
        $periodEnd   = $sub->current_period_end ?? ($item->current_period_end ?? null);
        $cancelAt    = (!empty($sub->cancel_at_period_end) || !empty($sub->cancel_at))
            ? date('Y-m-d H:i:s', (int)($sub->cancel_at ?? $periodEnd ?? time()))
            : null;

        $stmt->execute([
            ':cancel_at' => $cancelAt,
            ':instid'      => $shopId,
            ':sub'      => $sub->id,
            ':cid'      => $customerId,
            ':plan'     => $plan['name'],
            ':period'   => $plan['billingPeriod'],
            ':status'   => $sub->status,
            ':price'    => $item->price->unit_amount / 100,
            ':currency' => strtoupper($item->price->currency),
            ':raw'      => json_encode($event->toArray()),
            ':start'    => $periodStart,
            ':end'      => $periodEnd,
            ':pm'       => 'Stripe'
        ]);

        logWebhook($pdo, 'SUBSCRIPTION_SAVED', [
            'subscription' => $sub->id,
            'priceId'      => $priceId
        ], $event->type);

    break;


    // subscription delete
    case 'customer.subscription.deleted':

        /* Cancelling is not refunding. Whether cancelled immediately in
           Stripe, at period end, or by Stripe after failed renewal
           payments, the merchant keeps access until the end of the period
           actually PAID for: expires_on, which only
           invoice.payment_succeeded extends. Stripe's current_period_end is
           deliberately NOT used - after a failed renewal it points at the
           end of an unpaid period. If the paid period is already over,
           access ends now. */
        $sub         = $event->data->object;
        $cancelledAt = !empty($sub->canceled_at)
            ? date('Y-m-d H:i:s', (int)$sub->canceled_at)
            : date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            UPDATE app_subscriptions
            SET status = 'cancelled',
                cancelled_at = :cancelled_at,
                expires_on = CASE WHEN expires_on > NOW() THEN expires_on ELSE NOW() END,
                updated_at = NOW()
            WHERE stripe_subscription_id = :sid
        ");
        $stmt->execute([
            ':sid'          => $sub->id,
            ':cancelled_at' => $cancelledAt,
        ]);

        logWebhook($pdo, 'SUBSCRIPTION_CANCELLED', [
            'subscription' => $sub->id,
            'cancelled_at' => $cancelledAt,
        ], $event->type);

        break;


    // invoice payment success

case 'invoice.payment_succeeded':

    $invoice = $event->data->object;
    $customerId = $invoice->customer ?? null;

    $line = $invoice->lines->data[0] ?? null;
    if (!$line) {
        logWebhook($pdo, 'NO_LINE_ITEM', $invoice->id, $event->type);
        break;
    }

    // Extract IDs from YOUR payload structure
    $shopId = $line->metadata->shop_id ?? null;
    $priceId    = $line->pricing->price_details->price ?? null;
    $subscriptionId = $invoice->parent->subscription_details->subscription ?? null;

    if (!$customerId && $subscriptionId) {
        $stmt = $pdo->prepare("
            SELECT customer_id 
            FROM app_subscriptions 
            WHERE stripe_subscription_id = :sid 
            LIMIT 1
        ");
        $stmt->execute([':sid' => $subscriptionId]);
        $customerId = $stmt->fetchColumn() ?: null;
    }

    if (!$shopId || !$subscriptionId || !$priceId) {
        logWebhook($pdo, 'META_INCOMPLETE', [
            'shop_id'   => $shopId,
            'subscription'  => $subscriptionId,
            'price_id'      => $priceId
        ], $event->type);
        break;
    }

    logWebhook($pdo, 'INVOICE_PARSED', [
        'invoice_id'   => $invoice->id,
        'subscription' => $subscriptionId,
        'price_id'     => $priceId,
        'shop_id'  => $shopId
    ], $event->type);

    $plan = getPlan($pdo, $priceId);
    if (!$plan) {
        logWebhook($pdo, 'SKIPPED_PLAN', [
            'price_id' => $priceId
        ], $event->type);
        break;
    }

    try {
        $periodStart = $line->period->start;
        $periodEnd   = $line->period->end;

        // Check if subscription already exists
        $stmt = $pdo->prepare("
            SELECT id FROM app_subscriptions
            WHERE stripe_subscription_id = :sid
            LIMIT 1
        ");
        $stmt->execute([':sid' => $subscriptionId]);
        $exists = $stmt->fetch();

        if ($exists) {
            // Update existing
            $stmt = $pdo->prepare("
                UPDATE app_subscriptions
                SET status='active',
                    customer_id = COALESCE(:cid, customer_id),
                    price=:price,
                    currency=:currency,
                    raw_event=:raw,
                    expires_on=FROM_UNIXTIME(:end),
                    updated_at=NOW()
                WHERE stripe_subscription_id=:sid
            ");

            $stmt->execute([
                ':cid'      => $customerId,
                ':price'    => $invoice->amount_paid / 100,
                ':currency' => strtoupper($invoice->currency),
                ':raw'      => json_encode($event->toArray()),
                ':end'      => $periodEnd,
                ':sid'      => $subscriptionId
            ]);

            logWebhook($pdo, 'INVOICE_UPDATED', $subscriptionId, $event->type);

        } else {
            // Cancel other active subs for this instance
            $pdo->prepare("
                UPDATE app_subscriptions
                SET status = 'cancelled',
                    expires_on = NOW(),
                    updated_at = NOW()
                WHERE shop_id = :instid
                  AND status = 'active'
                  AND stripe_subscription_id != :sid
            ")->execute([
                ':instid' => $shopId,
                ':sid'    => $subscriptionId
            ]);

            // Insert new
            $stmt = $pdo->prepare("
                INSERT INTO app_subscriptions
                (shop_id, stripe_subscription_id, customer_id, plan_name, billing_period,
                 status, price, currency, raw_event, started_at, expires_on, payment_method)
                VALUES
                (:instid,:sid,:cid,:plan,:period,'active',:price,:currency,:raw,
                 FROM_UNIXTIME(:start),FROM_UNIXTIME(:end), :pm)
                ON DUPLICATE KEY UPDATE
                    stripe_subscription_id = VALUES(stripe_subscription_id),
                    customer_id = COALESCE(VALUES(customer_id), customer_id),
                    plan_name = VALUES(plan_name),
                    billing_period = VALUES(billing_period),
                    status = VALUES(status),
                    price = VALUES(price),
                    currency = VALUES(currency),
                    raw_event = VALUES(raw_event),
                    expires_on = VALUES(expires_on),
                    cancelled_at = NULL,
                    payment_method = VALUES(payment_method),
                    updated_at = NOW()
            ");

            $stmt->execute([
                ':instid'   => $shopId,
                ':sid'      => $subscriptionId,
                ':cid'      => $customerId,
                ':plan'     => $plan['name'],
                ':period'   => $plan['billingPeriod'],
                ':price'    => $invoice->amount_paid / 100,
                ':currency' => strtoupper($invoice->currency),
                ':raw'      => json_encode($event->toArray()),
                ':start'    => $periodStart,
                ':end'      => $periodEnd,
                ':pm'       => 'Stripe'
            ]);

            logWebhook($pdo, 'INVOICE_INSERTED', $subscriptionId, $event->type);
        }

    } catch (Throwable $e) {
        logWebhook($pdo, 'DB_ERROR', $subscriptionId, $event->type, $e->getMessage());
    }

    break;

    // debug
    default:
        logWebhook($pdo, 'UNHANDLED ', $event, $event->type);
}

// res
http_response_code(200);
echo json_encode(['success' => true]);
