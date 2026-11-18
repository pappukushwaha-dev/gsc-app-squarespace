<?php
/**
 * includes/gsc_cancel.php
 *
 * Helpers for the in-app Stripe cancellation flow:
 *   partials/cancel-popup.php      4-step cancel modal (CancellationFlow.open())
 *   api/subscription-cancel.php    saves answers, schedules / resumes the cancel in Stripe
 *
 * Cancelling here = Stripe "cancel at period end". The merchant keeps the
 * paid plan until expires_on; webhook/payment.php then handles
 * customer.subscription.updated / .deleted as usual.
 */

if (!function_exists('gscCancelIdColumn')) {

    /** Store id column of app_subscriptions on this platform. */
    function gscCancelIdColumn(): string
    {
        return 'shop_id';
    }

    /**
     * Per-store token embedded in the page that renders the popup. The API
     * only acts for the store whose token it gets, so a guessed store id is
     * not enough to cancel someone's subscription. Cookies are not used:
     * inside the platform's admin iframe the PHP session cookie is not sent.
     */
    function gscCancelToken(string $storeId): string
    {
        $secret = (string)(getenv('ENCRYPTION_SECRET') ?: ($_ENV['ENCRYPTION_SECRET'] ?? ''));
        if ($secret === '') {
            // credentials.php runs DB queries at file scope with $pdo.
            global $pdo;
            require_once __DIR__ . '/credentials.php';
            $secret = defined('ENCRYPTION_KEY') ? (string)ENCRYPTION_KEY : '';
        }
        return hash_hmac('sha256', 'gsc-cancel-flow|' . $storeId, $secret . '|' . __DIR__);
    }

    /**
     * Latest Stripe subscription row of the store, plus a derived 'state':
     *   active           auto-renew ON  -> can be cancelled
     *   cancel_scheduled auto-renew OFF, paid period still running -> can be resumed
     *   ended            cancelled / expired / unpaid -> nothing to do
     */
    function gscCancelFindSub(PDO $pdo, string $storeId): ?array
    {
        if ($storeId === '') {
            return null;
        }
        $col = gscCancelIdColumn();
        try {
            $q = $pdo->prepare("
                SELECT id, plan_name, billing_period, status, expires_on, cancelled_at, stripe_subscription_id
                FROM app_subscriptions
                WHERE {$col} = ?
                  AND stripe_subscription_id LIKE 'sub\\_%'
                ORDER BY id DESC
                LIMIT 1
            ");
            $q->execute([$storeId]);
            $row = $q->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            error_log('[gsc_cancel] lookup failed: ' . $e->getMessage());
            return null;
        }
        if (!$row) {
            return null;
        }

        $now     = time();
        $expires = !empty($row['expires_on']) ? strtotime((string)$row['expires_on']) : null;
        $live    = in_array(strtolower((string)$row['status']), ['active', 'trialing', 'past_due'], true);
        // Same 1-day grace as includes/plan_guard.php gscStripePaidStatus().
        $inPeriod = $expires === null || $expires + 86400 > $now;

        if ($live && $inPeriod && empty($row['cancelled_at'])) {
            $row['state'] = 'active';
        } elseif ($live && $expires !== null && $expires > $now && !empty($row['cancelled_at'])) {
            $row['state'] = 'cancel_scheduled';
        } else {
            $row['state'] = 'ended';
        }
        $row['access_until'] = $expires ? gmdate('j M Y', $expires) : null;
        return $row;
    }

    /**
     * Unix time until which the store has ALREADY PAID, when that paid
     * period is still running but nothing will renew it (cancelled now or
     * at period end, not refunded). null when auto-renew is on or nothing
     * is left. api/billing.php starts a new subscription's billing then,
     * so buying again never charges twice for the same period.
     */
    function gscCancelPaidUntil(PDO $pdo, string $storeId): ?int
    {
        $sub = gscCancelFindSub($pdo, $storeId);
        if (!$sub || $sub['state'] === 'active' || empty($sub['expires_on'])) {
            return null;
        }
        $until = strtotime((string)$sub['expires_on']);
        return ($until && $until > time()) ? $until : null;
    }

    /** Stripe API call to /v1/{path} (default subscriptions/{subId}). Returns [httpCode, decodedBody]. */
    function gscCancelStripeRequest(string $secret, string $method, string $subId, array $params = [], string $path = '', string $idemKey = ''): array
    {
        $url = 'https://api.stripe.com/v1/' . ($path !== '' ? $path : 'subscriptions/' . rawurlencode($subId));
        if ($method !== 'POST' && $params) {
            $url .= '?' . http_build_query($params);
        }
        $ch = curl_init($url);
        if ($idemKey !== '') {
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Idempotency-Key: ' . $idemKey]);
        }
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        curl_setopt_array($ch, [
            CURLOPT_USERPWD        => $secret . ':',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 25,
        ]);
        $raw  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            return [0, ['error' => ['message' => $err ?: 'Network error']]];
        }
        return [$code, json_decode((string)$raw, true) ?: []];
    }

    /**
     * Card the customer can be billed on: the subscription's card, else the
     * customer's default card, else the newest card saved on the customer
     * (e.g. saved on the card page but the browser closed before returning).
     */
    function gscCancelFindCard(string $secret, array $live): string
    {
        $pm = $live['default_payment_method'] ?? '';
        $pm = is_array($pm) ? (string)($pm['id'] ?? '') : (string)$pm;
        $customer = (string)($live['customer'] ?? '');
        if ($pm !== '' || $customer === '') {
            return $pm;
        }
        $c  = gscCancelStripeRequest($secret, 'GET', '', [], 'customers/' . rawurlencode($customer))[1];
        $pm = $c['invoice_settings']['default_payment_method'] ?? '';
        $pm = is_array($pm) ? (string)($pm['id'] ?? '') : (string)$pm;
        if ($pm !== '') {
            return $pm;
        }
        $l = gscCancelStripeRequest($secret, 'GET', '', ['type' => 'card', 'limit' => 1],
            'customers/' . rawurlencode($customer) . '/payment_methods')[1];
        return (string)($l['data'][0]['id'] ?? '');
    }

    /**
     * Turn auto-renewal back on after an IMMEDIATE cancel (Stripe cannot
     * un-cancel): a new subscription for the same customer, price and card
     * with trial_end = the paid-until date, so the first charge happens only
     * when the period already paid for ends. The store row then follows the
     * new subscription (expires_on unchanged).
     * Returns [true, newSubId] or [false, log message].
     */
    function gscCancelRenew(PDO $pdo, string $secret, array $sub, string $storeId, array $live, string $pm): array
    {
        $oldId     = (string)$sub['stripe_subscription_id'];
        $paidUntil = !empty($sub['expires_on']) ? (int)strtotime((string)$sub['expires_on']) : 0;
        $priceId   = (string)($live['items']['data'][0]['price']['id'] ?? '');
        $customer  = (string)($live['customer'] ?? '');
        if ($paidUntil <= time() + 600 || $priceId === '' || $customer === '' || $pm === '') {
            return [false, "renew {$oldId}: nothing to renew (paid until {$paidUntil}, price '{$priceId}', card '{$pm}')"];
        }
        $params = [
            'customer'               => $customer,
            'items[0][price]'        => $priceId,
            'default_payment_method' => $pm,
            'trial_end'              => $paidUntil,
            'proration_behavior'     => 'none',
        ];
        foreach ((array)($live['metadata'] ?? []) as $k => $v) {
            $params["metadata[{$k}]"] = (string)$v;
        }
        $params['metadata[gsc_renewed_from]'] = $oldId;

        [$code, $new] = gscCancelStripeRequest($secret, 'POST', '', $params, 'subscriptions', 'gsc-renew-' . $oldId . '-' . $pm);
        if ($code !== 200 || empty($new['id'])) {
            return [false, "renew {$oldId} failed HTTP {$code}: " . ($new['error']['message'] ?? '')];
        }
        $col = gscCancelIdColumn();
        $pdo->prepare("UPDATE app_subscriptions SET stripe_subscription_id = :new, status = 'active', cancelled_at = NULL, updated_at = NOW()
                       WHERE id = :id AND {$col} = :store")
            ->execute([':new' => $new['id'], ':id' => $sub['id'], ':store' => $storeId]);
        try {
            $pdo->prepare("UPDATE cancellation_sessions SET final_action = 'resumed', updated_at = NOW() WHERE {$col} = ?")->execute([$storeId]);
        } catch (Throwable $e) {
            error_log('[gsc_cancel] resume tracking failed: ' . $e->getMessage());
        }
        return [true, (string)$new['id']];
    }
}
