<?php
declare(strict_types=1);

/**
 * admin/core/StoreListManager.php
 *
 * Store lists that match the Wix admin sidebar:
 *   paid          stores whose latest subscription is a paid plan (any status), test stores excluded
 *   unauthorized  installs that never finished authorising the app (no site URL / no access token)
 *   test          our own stores (makkpress in the site URL, name, owner email or Google email)
 * Used by paid-stores.php, unauthorized-stores.php, test-stores.php via partials/store-list.php.
 */
class StoreListManager
{
    /* ---- platform specific ---- */
    public const ID_COL       = 'shop_id';
    private const STORE_TABLE = 'SquarespaceSite';
    private const NAME_SQL    = "COALESCE(NULLIF(ws.site_display_name,''), ws.site_url)";
    private const URL_SQL     = "ws.site_url";
    private const EMAIL_SQL   = "ws.owner_email";
    private const UNAUTH_SQL  = "(IFNULL(TRIM(ws.site_url),'') = '' OR IFNULL(ws.access_token,'') = '')";

    public const TYPES = [
        'paid'         => ['title' => 'Paid Stores',         'empty' => 'No paid stores yet.'],
        'unauthorized' => ['title' => 'Unauthorized Stores', 'empty' => 'No unauthorized stores.'],
        'test'         => ['title' => 'Test Stores',         'empty' => 'No test stores.'],
    ];

    /** Plan filter of the Paid list (computed from the latest subscription row). */
    public const PLAN_FILTERS = [
        'renewing'  => 'Active (renews)',
        'ending'    => 'Cancelled, access left',
        'ended'     => 'Ended',
        'monthly'   => 'Monthly',
        'yearly'    => 'Yearly',
    ];

    private static PDO $pdo;

    public static function initialize(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    private static function testSql(): string
    {
        // Columns differ in collation (some are utf8mb4_bin): compare each one in one collation.
        $c = static fn(string $e) => "LOWER(CONVERT(IFNULL({$e}, '') USING utf8mb4) COLLATE utf8mb4_unicode_ci) LIKE '%makkpress%'";
        return '(' . implode(' OR ', [$c(self::URL_SQL), $c(self::NAME_SQL), $c(self::EMAIL_SQL), $c('ga.email')]) . ')';
    }

    private static function fromSql(): string
    {
        $t  = self::STORE_TABLE;
        $id = self::ID_COL;
        // One Google account and one (latest) subscription per store.
        return "
            FROM `{$t}` ws
            LEFT JOIN google_accounts ga
                ON ga.id = (SELECT MAX(g2.id) FROM google_accounts g2 WHERE g2.{$id} = ws.{$id} COLLATE utf8mb4_unicode_ci)
            LEFT JOIN app_subscriptions s
                ON s.id = (SELECT MAX(s2.id) FROM app_subscriptions s2 WHERE s2.{$id} = ws.{$id} COLLATE utf8mb4_unicode_ci)
        ";
    }

    private static function whereSql(string $type, string $search, string $plan, array &$params, array $filters = []): string
    {
        $w = [];
        if ($type === 'paid') {
            $w[] = "s.plan_name IS NOT NULL AND LOWER(TRIM(s.plan_name)) NOT IN ('', 'free', 'basic')";
            $w[] = 'NOT (' . self::testSql() . ')';
            $live = "LOWER(IFNULL(s.status,'')) IN ('active','trialing','past_due')";
            switch ($plan) {
                case 'renewing': $w[] = "{$live} AND s.cancelled_at IS NULL AND (s.expires_on IS NULL OR s.expires_on > NOW() - INTERVAL 1 DAY)"; break;
                case 'ending':   $w[] = "NOT ({$live} AND s.cancelled_at IS NULL) AND s.expires_on > NOW()"; break;
                case 'ended':    $w[] = "NOT ({$live} AND s.cancelled_at IS NULL) AND (s.expires_on IS NULL OR s.expires_on <= NOW())"; break;
                case 'monthly':  $w[] = "LOWER(s.billing_period) = 'monthly'"; break;
                case 'yearly':   $w[] = "LOWER(s.billing_period) = 'yearly'"; break;
            }
        } elseif ($type === 'unauthorized') {
            $w[] = self::UNAUTH_SQL;
        } else {
            $w[] = self::testSql();
        }
        if ($search !== '') {
            $w[] = '(ws.' . self::ID_COL . ' LIKE :q1 OR ' . self::NAME_SQL . ' LIKE :q2 OR ' . self::URL_SQL . ' LIKE :q3 OR '
                 . self::EMAIL_SQL . ' LIKE :q4 OR ga.email LIKE :q5 OR s.plan_name LIKE :q6)';
            for ($i = 1; $i <= 6; $i++) {
                $params[":q{$i}"] = '%' . $search . '%';
            }
        }
        if ($filters) { // 2026-11-24: Wix-style filters, /var/www/html/_shared/admin_store_filters.php
            require_once '/var/www/html/_shared/admin_store_filters.php';
            [$sfW, $sfP] = gsc_sf_where($filters, self::ID_COL, 'ws.' . self::ID_COL, 'ws.created_at', 'ws.is_active');
            $w = array_merge($w, $sfW);
            $params += $sfP;
        }
        return ' WHERE ' . implode(' AND ', $w);
    }

    public static function getStores(string $type, string $search, string $plan, int $perPage, int $page, array $filters = []): array
    {
        try {
            $params = [];
            $stmt = self::$pdo->prepare('
                SELECT
                    ws.' . self::ID_COL . ' AS store_id,
                    ' . self::NAME_SQL . ' AS store_name,
                    ' . self::URL_SQL . ' AS site_url,
                    ' . self::EMAIL_SQL . ' AS owner_email,
                    ws.is_active, ws.created_at,
                    ga.email AS google_email,
                    s.plan_name, s.billing_period, s.status AS sub_status, s.expires_on, s.cancelled_at,
                    s.stripe_subscription_id
                ' . self::fromSql() . self::whereSql($type, $search, $plan, $params, $filters) . '
                ORDER BY ws.created_at DESC, ws.id DESC
                LIMIT :offset, :limit');
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', max(0, ($page - 1) * $perPage), PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('StoreListManager Error: ' . $e->getMessage());
            return [];
        }
    }

    public static function getTotal(string $type, string $search = '', string $plan = '', array $filters = []): int
    {
        try {
            $params = [];
            $stmt = self::$pdo->prepare('SELECT COUNT(*) ' . self::fromSql() . self::whereSql($type, $search, $plan, $params, $filters));
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('StoreListManager Count Error: ' . $e->getMessage());
            return 0;
        }
    }

    /** [label, tone] of the plan today, same rules as the app (1-day grace after expiry). */
    public static function planState(array $r): array
    {
        if (empty($r['plan_name'])) {
            return ['No plan', 'neutral'];
        }
        $live    = in_array(strtolower((string)$r['sub_status']), ['active', 'trialing', 'past_due'], true);
        $expires = !empty($r['expires_on']) ? strtotime((string)$r['expires_on']) : null;
        if ($live && empty($r['cancelled_at']) && ($expires === null || $expires + 86400 > time())) {
            return [strtolower((string)$r['sub_status']) === 'past_due' ? 'Payment due' : 'Active', strtolower((string)$r['sub_status']) === 'past_due' ? 'warning' : 'success'];
        }
        if ($expires && $expires > time()) {
            return ['Cancelled, ends ' . date('j M Y', $expires), 'warning'];
        }
        return ['Ended', 'danger'];
    }
}
