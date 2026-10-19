<?php

declare(strict_types=1);

class StoreManager
{
    private static PDO $pdo;

    public static function initialize(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    private static function assertPdo(): void
    {
        if (!isset(self::$pdo)) {
            throw new RuntimeException('PDO not initialized');
        }
    }

    /* ------------------------------------------------------------
       TRIAL CHECK
    ------------------------------------------------------------ */
    private static function buildTrialMap(array $shopIds): array
    {
        if (!$shopIds) return [];

        $in = implode(',', array_fill(0, count($shopIds), '?'));

        $stmt = self::$pdo->prepare("
            SELECT shop_id
            FROM app_free_trials
            WHERE shop_id IN ($in)
              AND status = 'active'
        ");
        $stmt->execute($shopIds);

        return array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
    }

    /* ------------------------------------------------------------
       SUBSCRIPTION PLAN
    ------------------------------------------------------------ */
    private static function getSubscriptionPlan(string $shopId): ?string
    {
        $stmt = self::$pdo->prepare("
            SELECT plan_name, billing_period
            FROM app_subscriptions
            WHERE shop_id = ?
              AND status = 'active'
            ORDER BY started_at DESC
            LIMIT 1
        ");
        $stmt->execute([$shopId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        return ucfirst($row['plan_name']) .
            ($row['billing_period'] !== 'free' ? " ({$row['billing_period']})" : '');
    }

    /* ------------------------------------------------------------
       STORE LIST
    ------------------------------------------------------------ */
    public static function getFilteredStores(string $search = '', int $perPage = 10, int $page = 1): array
    {
        self::assertPdo();

        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT
                ws.shop_id,
                ws.domain,
                ws.site_display_name AS shop_name,
                ws.owner_email,
                ws.event_type,
                ws.is_active,
                ws.created_at,
                ga.email AS google_email,
                gdv.verification_status,
                s.status AS sitemap_status,
                ss.status
                FROM SquarespaceSite ws
                LEFT JOIN google_accounts ga
                ON ga.shop_id COLLATE utf8mb4_unicode_ci = ws.shop_id COLLATE utf8mb4_unicode_ci
                LEFT JOIN gsc_domain_verifications gdv
                ON gdv.shop_id COLLATE utf8mb4_unicode_ci = ws.shop_id COLLATE utf8mb4_unicode_ci
                LEFT JOIN sitemaps s
                ON s.shop_id COLLATE utf8mb4_unicode_ci = ws.shop_id COLLATE utf8mb4_unicode_ci
                LEFT JOIN app_subscriptions ss
                ON ss.shop_id COLLATE utf8mb4_unicode_ci = ws.shop_id COLLATE utf8mb4_unicode_ci
            ";

        $conditions = [];

        if (strtolower($search) === 'active') {
            $conditions[] = "ws.event_type = 'install'";
        } elseif (strtolower($search) === 'inactive') {
            $conditions[] = "ws.event_type = 'uninstall'";
        } elseif ($search !== '') {
            $q = self::$pdo->quote('%' . $search . '%');
            $conditions[] = "
                (ws.site_display_name LIKE $q
                OR ws.domain LIKE $q
                OR ws.owner_email LIKE $q
                OR ga.email LIKE $q)
            ";
        }

        // Combine conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $sql .= "
            GROUP BY ws.shop_id
            ORDER BY ws.created_at DESC
            LIMIT " . (int)$perPage . "
            OFFSET " . (int)$offset . "
        ";

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute();

        $stores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$stores) return [];

        // Add status_label and plan_name safely
        $shopIds = array_column($stores, 'shop_id');
        $trialMap = self::buildTrialMap($shopIds);

        foreach ($stores as &$store) {
            $iid = $store['shop_id'];
            $store['status_label'] = (($store['event_type'] == 'install' ? 'Active' : 'Inactive'));
            $store['plan_name'] = isset($trialMap[$iid]) ? 'Trial' : (self::getSubscriptionPlan($iid) ?: 'No Plan');
        }

        return $stores;
    }

    /* ------------------------------------------------------------
       TOTAL COUNT
    ------------------------------------------------------------ */
    public static function getTotalStores(string $search = ''): int
    {
        self::assertPdo();

        $sql = "
            SELECT COUNT(DISTINCT ws.shop_id)
            FROM SquarespaceSite ws
            LEFT JOIN google_accounts ga
            ON ga.shop_id COLLATE utf8mb4_unicode_ci = ws.shop_id COLLATE utf8mb4_unicode_ci
        ";

        $conditions = [];

        if (strtolower($search) === 'active') {
            $conditions[] = "ws.event_type = 'install'";
        } elseif (strtolower($search) === 'inactive') {
            $conditions[] = "ws.event_type = 'uninstall'";
        } elseif ($search !== '') {
            $q = self::$pdo->quote('%' . $search . '%');
            $conditions[] = "
                (ws.site_display_name LIKE $q
                OR ws.domain LIKE $q
                OR ws.owner_email LIKE $q
                OR ga.email LIKE $q)
            ";
        }

        // Combine conditions
        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    /* ------------------------------------------------------------
       SINGLE STORE DETAILS
    ------------------------------------------------------------ */
    public static function getStoreDetails(string $shopId): array|false
    {
        self::assertPdo();

        $stmt = self::$pdo->prepare("
            SELECT
                ws.*,
                ga.email AS google_email,
                gdv.verification_status,
                s.status AS sitemap_status
                FROM SquarespaceSite ws
                LEFT JOIN google_accounts ga
                ON ga.shop_id COLLATE utf8mb4_unicode_ci
                = ws.shop_id COLLATE utf8mb4_unicode_ci

                LEFT JOIN gsc_domain_verifications gdv
                ON gdv.shop_id COLLATE utf8mb4_unicode_ci
                = ws.shop_id COLLATE utf8mb4_unicode_ci

                LEFT JOIN sitemaps s
                ON s.shop_id COLLATE utf8mb4_unicode_ci
                = ws.shop_id COLLATE utf8mb4_unicode_ci

                LIMIT 1
        ");

        $stmt->execute([$shopId]);

        $store = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$store) return false;

        /* STATUS */
        $store['status_label'] =
            ($store['google_email'] && $store['verification_status'] === 'verified')
            ? 'Active'
            : 'Inactive';

        /* PLAN */
        if (self::buildTrialMap([$shopId])) {
            $store['plan_name'] = 'Trial';
        } else {
            $plan = self::getSubscriptionPlan($shopId);
            $store['plan_name'] = $plan ?: 'Free';
        }

        return $store;
    }
}
