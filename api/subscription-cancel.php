<?php
/**
 * api/subscription-cancel.php
 *
 * Backend of the 4-step cancel modal (partials/cancel-popup.php).
 * POST JSON: { store_id, token, action, answers }
 *   action = track   save the answers so far (analytics), optional final_action
 *          = cancel  Stripe cancel_at_period_end=true (paid plan kept until expires_on)
 *          = resume  "Keep my plan", reads the subscription in Stripe first:
 *                    scheduled cancel -> removed; immediate cancel -> same price restarted on
 *                    the saved card, first charge when the paid period ends (no saved card ->
 *                    Stripe "save card" page, then api/subscription-card-return.php)
 * Answers go to cancellation_sessions (one row per store, admin → Cancel Plan),
 * keyed by the platform's store id column (gscCancelIdColumn()).
 */
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/credentials.php';
require_once __DIR__ . '/../includes/gsc_cancel.php';

function gscCancelOut(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    gscCancelOut(405, ['success' => false, 'message' => 'POST only']);
}

$in      = json_decode((string)file_get_contents('php://input'), true) ?: [];
$storeId = trim((string)($in['store_id'] ?? ''));
$token   = (string)($in['token'] ?? '');
$action  = (string)($in['action'] ?? 'track');
$answers = is_array($in['answers'] ?? null) ? $in['answers'] : [];

if ($storeId === '' || !hash_equals(gscCancelToken($storeId), $token)) {
    gscCancelOut(403, ['success' => false, 'message' => 'Session expired. Please reload the page and try again.']);
}

/** Upsert the flow answers (cancellation_sessions has UNIQUE instance_id). */
function gscCancelSaveAnswers(PDO $pdo, string $storeId, array $a, ?string $finalAction): void
{
    $s = static fn($v, int $max = 255) => ($v === null || $v === '') ? null : mb_substr((string)$v, 0, $max);
    try {
        $pdo->prepare("
            INSERT INTO cancellation_sessions
                (" . gscCancelIdColumn() . ", current_step, completed, final_action, join_reason, join_extra,
                 mirror_screen_shown, leave_reason, leave_extra, offer_type, offer_selected,
                 call_scheduled, review_shown, created_at, updated_at)
            VALUES
                (:id, :step, :completed, :final, :jr, :jx, :mirror, :lr, :lx, :ot, :os, 0, :review, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                current_step = VALUES(current_step),
                completed = VALUES(completed),
                final_action = VALUES(final_action),
                join_reason = VALUES(join_reason),
                join_extra = VALUES(join_extra),
                mirror_screen_shown = VALUES(mirror_screen_shown),
                leave_reason = VALUES(leave_reason),
                leave_extra = VALUES(leave_extra),
                offer_type = VALUES(offer_type),
                offer_selected = VALUES(offer_selected),
                review_shown = VALUES(review_shown),
                updated_at = NOW()
        ")->execute([
            ':id'        => $storeId,
            ':step'      => max(1, min(5, (int)($a['step'] ?? 1))),
            ':completed' => in_array($finalAction, ['cancelled', 'resumed'], true) ? 1 : 0,
            ':final'     => $finalAction,
            ':jr'        => $s($a['join_reason'] ?? null),
            ':jx'        => $s($a['join_extra'] ?? null, 2000),
            ':mirror'    => (int)(($a['step'] ?? 1) >= 2),
            ':lr'        => $s($a['leave_reason'] ?? null),
            ':lx'        => $s($a['leave_extra'] ?? null, 2000),
            ':ot'        => $s($a['offer_type'] ?? null),
            ':os'        => $s($a['offer_selected'] ?? null),
            ':review'    => (int)(($a['step'] ?? 1) >= 4),
        ]);
    } catch (Throwable $e) {
        error_log('[subscription-cancel] save answers failed: ' . $e->getMessage());
    }
}

/* ---------------- track ---------------- */
if ($action === 'track') {
    $final = (string)($in['final_action'] ?? '');
    $final = in_array($final, ['abandoned', 'kept_subscription', 'accepted_offer'], true) ? $final : null;
    gscCancelSaveAnswers($pdo, $storeId, $answers, $final);
    gscCancelOut(200, ['success' => true]);
}

if (!in_array($action, ['cancel', 'resume'], true)) {
    gscCancelOut(400, ['success' => false, 'message' => 'Unknown action']);
}

/* ---------------- cancel / resume ---------------- */
$sub = gscCancelFindSub($pdo, $storeId);
if (!$sub) {
    gscCancelOut(404, ['success' => false, 'message' => 'No Stripe subscription found for this store.']);
}

try {
    $secret = (string)(get_stripe_credentials($pdo)['secret_key'] ?? '');
} catch (Throwable $e) {
    $secret = '';
}
if ($secret === '') {
    error_log('[subscription-cancel] Stripe secret key unavailable');
    gscCancelOut(500, ['success' => false, 'message' => 'Billing is temporarily unavailable. Please contact support.']);
}

$col   = gscCancelIdColumn();
$subId = (string)$sub['stripe_subscription_id'];
$until = (string)($sub['access_until'] ?? '');

/* ---------------- resume ("Keep my plan") ----------------
   Stripe is read first - it is the source of truth for HOW the plan was
   cancelled:
     scheduled (cancel_at_period_end / cancel_at)  -> remove it, plan active again
     immediately (subscription status canceled)    -> cannot be resumed; the
        merchant buys again (api/billing.php starts billing when the paid
        period ends, so nothing is charged twice). */
if ($action === 'resume') {
    [$code, $live] = gscCancelStripeRequest($secret, 'GET', $subId);
    if ($code !== 200) {
        error_log("[subscription-cancel] resume read {$subId} failed HTTP {$code}: " . ($live['error']['message'] ?? ''));
        gscCancelOut(502, ['success' => false, 'message' => 'Could not reach billing right now. Please try again or contact support.']);
    }

    if (in_array(strtolower((string)($live['status'] ?? '')), ['canceled', 'incomplete_expired'], true)) {
        /* Cancelled immediately: Stripe cannot un-cancel a subscription.
           Auto-renewal comes back as a new subscription on the same price,
           customer and card, first charge when the paid period ends
           (gscCancelRenew). No saved card -> a Stripe page that ONLY saves a
           card (Checkout setup mode: no price, no trial text); its return
           URL, api/subscription-card-return.php, then does the same. */
        $paidUntil = !empty($sub['expires_on']) ? strtotime((string)$sub['expires_on']) : 0;
        $customer  = (string)($live['customer'] ?? '');
        if ($paidUntil <= time() + 600 || $customer === '' || empty($live['items']['data'][0]['price']['id'])) {
            gscCancelOut(409, ['success' => false, 'message' => 'This plan has already ended. Choose a plan on the Pricing page to subscribe again.']);
        }

        $pm = gscCancelFindCard($secret, $live);
        if ($pm === '') {
            $base = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'makkpressapps.com') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/x/api/x.php')), '/');
            [$code, $cs] = gscCancelStripeRequest($secret, 'POST', '', [
                'mode'                    => 'setup',
                'customer'                => $customer,
                'payment_method_types[0]' => 'card',
                'metadata[gsc_store]'       => $storeId,
                'metadata[gsc_renew_from]'  => $subId,
                'success_url'             => $base . '/api/subscription-card-return.php?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'              => $base . '/current-plan.php?' . $col . '=' . rawurlencode($storeId),
            ], 'checkout/sessions');
            if ($code !== 200 || empty($cs['url'])) {
                error_log("[subscription-cancel] card page for {$subId} failed HTTP {$code}: " . ($cs['error']['message'] ?? ''));
                gscCancelOut(502, ['success' => false, 'message' => 'Could not turn auto-renewal back on right now. Please try again or contact support.']);
            }
            gscCancelOut(200, ['success' => false, 'needs_card' => true, 'checkout_url' => $cs['url'],
                'message' => 'Please save a card to turn auto-renewal back on. Nothing is charged until ' . $until . '.']);
        }

        [$ok, $res] = gscCancelRenew($pdo, $secret, $sub, $storeId, $live, $pm);
        if (!$ok) {
            error_log('[subscription-cancel] ' . $res);
            gscCancelOut(502, ['success' => false, 'message' => 'Could not turn auto-renewal back on right now. Please try again or contact support.']);
        }
        gscCancelOut(200, ['success' => true, 'renewed' => true, 'renews_on' => $until]);
    }

    $wasScheduled = !empty($live['cancel_at_period_end']) || !empty($live['cancel_at']);
    if ($wasScheduled) {
        $params = !empty($live['cancel_at_period_end']) ? ['cancel_at_period_end' => 'false'] : ['cancel_at' => ''];
        $params['metadata[gsc_resumed_via]'] = 'in_app';
        [$code, $live] = gscCancelStripeRequest($secret, 'POST', $subId, $params);
        if ($code !== 200) {
            error_log("[subscription-cancel] resume {$subId} failed HTTP {$code}: " . ($live['error']['message'] ?? ''));
            gscCancelOut(502, ['success' => false, 'message' => 'Could not turn auto-renewal back on right now. Please try again or contact support.']);
        }
    }

    // Stripe renews it again -> the plan is active (customer.subscription.updated writes the same).
    $status = strtolower((string)($live['status'] ?? 'active'));
    if (!in_array($status, ['active', 'trialing', 'past_due'], true)) {
        $status = 'active';
    }
    $pdo->prepare("UPDATE app_subscriptions SET status = :st, cancelled_at = NULL, updated_at = NOW() WHERE id = :id AND {$col} = :store")
        ->execute([':st' => $status, ':id' => $sub['id'], ':store' => $storeId]);
    try {
        // Keep the reasons given at cancel time; just record that they came back.
        $pdo->prepare("UPDATE cancellation_sessions SET final_action = 'resumed', updated_at = NOW() WHERE {$col} = ?")
            ->execute([$storeId]);
    } catch (Throwable $e) {
        error_log('[subscription-cancel] resume tracking failed: ' . $e->getMessage());
    }
    gscCancelOut(200, ['success' => true, 'already' => !$wasScheduled, 'renews_on' => $until]);
}

/* ---------------- cancel (schedule at period end) ---------------- */
if ($sub['state'] === 'cancel_scheduled') {
    gscCancelOut(200, ['success' => true, 'already' => true, 'access_until' => $until]);
}
if ($sub['state'] !== 'active') {
    gscCancelOut(409, ['success' => false, 'message' => 'This subscription has already ended. Choose a plan on the Pricing page to subscribe again.']);
}

[$code, $resp] = gscCancelStripeRequest($secret, 'POST', $subId, [
    'cancel_at_period_end'        => 'true',
    'metadata[gsc_cancel_via]'    => 'in_app',
    'metadata[gsc_cancel_reason]' => mb_substr((string)($answers['leave_reason'] ?? ''), 0, 200),
]);
if ($code !== 200) {
    error_log("[subscription-cancel] cancel {$subId} failed HTTP {$code}: " . ($resp['error']['message'] ?? ''));
    gscCancelOut(502, ['success' => false, 'message' => 'Could not update the subscription right now. Please try again or contact support.']);
}

/* Reflect it at once (the customer.subscription.updated webhook writes the same values). */
$item     = $resp['items']['data'][0] ?? [];
$cancelAt = (int)($resp['cancel_at'] ?? ($resp['current_period_end'] ?? ($item['current_period_end'] ?? 0)));
$pdo->prepare("
    UPDATE app_subscriptions
    SET cancelled_at = COALESCE(:cancel_at, expires_on, NOW()), updated_at = NOW()
    WHERE id = :id AND {$col} = :store
")->execute([
    ':cancel_at' => $cancelAt > 0 ? date('Y-m-d H:i:s', $cancelAt) : null,
    ':id'        => $sub['id'],
    ':store'     => $storeId,
]);
gscCancelSaveAnswers($pdo, $storeId, $answers + ['step' => 4], 'cancelled');
gscCancelOut(200, ['success' => true, 'access_until' => $until]);
