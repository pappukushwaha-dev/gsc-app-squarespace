<?php
/**
 * api/subscription-card-return.php
 *
 * Return URL of the Stripe "save a card" page (Checkout, setup mode) that
 * api/subscription-cancel.php opens when "Keep my plan" is clicked after an
 * IMMEDIATE cancel and the customer has no saved card.
 *
 * The card is now saved on the Stripe customer. Here auto-renewal is turned
 * back on: new subscription, same price, first charge when the paid period
 * ends (gscCancelRenew). Then back to Current Plan.
 *
 * Nothing is trusted from the URL except the Checkout session id; store,
 * old subscription and customer are read from Stripe and must match the DB.
 */
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';
require_once __DIR__ . '/../includes/gsc_cancel.php';

$col = gscCancelIdColumn();

function gscCardReturnFail(string $storeId, string $log, string $msg): void
{
    if ($log !== '') {
        error_log('[subscription-card-return] ' . $log);
    }
    $back = '../current-plan.php' . ($storeId !== '' ? '?' . gscCancelIdColumn() . '=' . rawurlencode($storeId) : '');
    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Auto-renewal</title></head><body style="font-family:system-ui,sans-serif;max-width:520px;margin:60px auto;padding:0 16px;color:#1f2937">'
        . '<h2 style="margin:0 0 12px">Auto-renewal is not on yet</h2>'
        . '<p style="line-height:1.5">' . htmlspecialchars($msg) . '</p>'
        . '<p><a href="' . htmlspecialchars($back) . '" style="display:inline-block;background:#458cf5;color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none">Back to My Plan</a></p>'
        . '</body></html>';
    exit;
}

$sessionId = (string)($_GET['session_id'] ?? '');
if (!preg_match('/^cs_[A-Za-z0-9_]{10,200}$/', $sessionId)) {
    gscCardReturnFail('', '', 'This link is not valid. Open the app and click "Keep my plan" again.');
}

try {
    $secret = (string)(get_stripe_credentials($pdo)['secret_key'] ?? '');
} catch (Throwable $e) {
    $secret = '';
}
if ($secret === '') {
    gscCardReturnFail('', 'Stripe secret key unavailable', 'Billing is temporarily unavailable. Please contact support.');
}

[$code, $cs] = gscCancelStripeRequest($secret, 'GET', '', ['expand' => ['setup_intent']],
    'checkout/sessions/' . rawurlencode($sessionId));
$storeId = (string)($cs['metadata']['gsc_store'] ?? '');
$fromSub = (string)($cs['metadata']['gsc_renew_from'] ?? '');
$retry   = 'Your card was saved. Open the app and click "Keep my plan" again, or contact support.';

if ($code !== 200 || ($cs['mode'] ?? '') !== 'setup' || $storeId === '' || $fromSub === '') {
    gscCardReturnFail($storeId, "session {$sessionId} unusable HTTP {$code}: " . ($cs['error']['message'] ?? ''),
        'This link is not valid. Open the app and click "Keep my plan" again.');
}
$pm = $cs['setup_intent']['payment_method'] ?? '';
$pm = is_array($pm) ? (string)($pm['id'] ?? '') : (string)$pm;
if (($cs['status'] ?? '') !== 'complete' || ($cs['setup_intent']['status'] ?? '') !== 'succeeded' || $pm === '') {
    gscCardReturnFail($storeId, "session {$sessionId} not completed", 'The card was not saved. Open the app and click "Keep my plan" to try again.');
}

$sub = gscCancelFindSub($pdo, $storeId);
if (!$sub) {
    gscCardReturnFail($storeId, "no subscription row for {$storeId}", $retry);
}
$done = '../current-plan.php?' . $col . '=' . rawurlencode($storeId) . '&plan_kept=1';

// Page reloaded after it already worked: the store now follows the new subscription.
if ((string)$sub['stripe_subscription_id'] !== $fromSub) {
    if ($sub['state'] === 'active') {
        header('Location: ' . $done);
        exit;
    }
    gscCardReturnFail($storeId, "store {$storeId} no longer on {$fromSub}", $retry);
}

[$code, $live] = gscCancelStripeRequest($secret, 'GET', $fromSub);
if ($code !== 200 || (string)($live['customer'] ?? '') !== (string)($cs['customer'] ?? '')) {
    gscCardReturnFail($storeId, "read {$fromSub} HTTP {$code} / customer mismatch", $retry);
}
if (!in_array(strtolower((string)($live['status'] ?? '')), ['canceled', 'incomplete_expired'], true)) {
    gscCardReturnFail($storeId, "{$fromSub} is {$live['status']}, not cancelled", $retry);
}

// Future invoices of this customer use the new card too (best effort).
gscCancelStripeRequest($secret, 'POST', '', ['invoice_settings[default_payment_method]' => $pm],
    'customers/' . rawurlencode((string)$live['customer']));

[$ok, $res] = gscCancelRenew($pdo, $secret, $sub, $storeId, $live, $pm);
if (!$ok) {
    gscCardReturnFail($storeId, $res, (strpos($res, 'nothing to renew') !== false)
        ? 'This plan has already ended. Choose a plan on the Pricing page to subscribe again.'
        : $retry);
}
header('Location: ' . $done);
exit;
