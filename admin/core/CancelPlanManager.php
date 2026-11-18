<?php
declare(strict_types=1);

/**
 * admin/core/CancelPlanManager.php
 *
 * Data for admin/cancel-plan.php: the answers merchants gave in the in-app
 * cancel flow (partials/cancel-popup.php -> api/subscription-cancel.php ->
 * cancellation_sessions), joined with the store and its latest subscription.
 */
class CancelPlanManager
{
    /* Platform specific: store table, store id column, and its name / url / email columns. */
    private const STORE_TABLE = 'SquarespaceSite';
    private const ID_COL      = 'shop_id';
    private const NAME_SQL    = "COALESCE(NULLIF(w.site_display_name,''), w.domain)";
    private const URL_SQL     = "COALESCE(NULLIF(w.site_url,''), w.domain)";
    private const EMAIL_SQL   = "w.owner_email";

    /** final_action values written by api/subscription-cancel.php */
    public const RESULTS = [
        'cancelled'         => 'Cancelled',
        'resumed'           => 'Came back (Keep my plan)',
        'kept_subscription' => 'Kept plan in the flow',
        'accepted_offer'    => 'Clicked an offer',
        'abandoned'         => 'Closed the flow',
    ];

    private static PDO $pdo;

    public static function initialize(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    /** FROM + JOINs shared by list and count. One store row and one subscription row per session. */
    private static function fromSql(): string
    {
        $t  = self::STORE_TABLE;
        $id = self::ID_COL;
        return "
            FROM cancellation_sessions r
            LEFT JOIN `{$t}` w ON w.id = (SELECT MAX(w2.id) FROM `{$t}` w2 WHERE w2.{$id} = r.{$id} COLLATE utf8mb4_unicode_ci)
            LEFT JOIN app_subscriptions s ON s.id = (SELECT MAX(s2.id) FROM app_subscriptions s2 WHERE s2.{$id} = r.{$id} COLLATE utf8mb4_unicode_ci)
        ";
    }

    /** WHERE for search text + result filter. */
    private static function whereSql(string $search, string $result, array &$params): string
    {
        $where = ' WHERE 1=1';
        if ($search !== '') {
            $where .= ' AND (r.' . self::ID_COL . ' LIKE :q1 OR ' . self::NAME_SQL . ' LIKE :q2 OR ' . self::URL_SQL . ' LIKE :q3
                        OR ' . self::EMAIL_SQL . ' LIKE :q4 OR r.join_reason LIKE :q5 OR r.leave_reason LIKE :q6 OR r.leave_extra LIKE :q7)';
            for ($i = 1; $i <= 7; $i++) {
                $params[":q{$i}"] = '%' . $search . '%';
            }
        }
        if ($result === 'open') {
            $where .= ' AND r.final_action IS NULL';
        } elseif (isset(self::RESULTS[$result])) {
            $where .= ' AND r.final_action = :result';
            $params[':result'] = $result;
        }
        return $where;
    }

    public static function getFilteredSessions(string $search = '', string $result = '', int $perPage = 10, int $page = 1): array
    {
        try {
            $params = [];
            $sql = '
                SELECT
                    r.id, r.' . self::ID_COL . ' AS store_id, r.current_step, r.completed, r.final_action,
                    r.join_reason, r.join_extra, r.leave_reason, r.leave_extra, r.offer_type, r.offer_selected,
                    r.created_at, r.updated_at,
                    ' . self::NAME_SQL . ' AS store_name,
                    ' . self::URL_SQL . ' AS site_url,
                    ' . self::EMAIL_SQL . ' AS email,
                    s.plan_name, s.billing_period, s.status AS sub_status, s.expires_on, s.cancelled_at
                ' . self::fromSql() . self::whereSql($search, $result, $params) . '
                ORDER BY r.updated_at DESC, r.id DESC
                LIMIT :offset, :limit';
            $stmt = self::$pdo->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->bindValue(':offset', max(0, ($page - 1) * $perPage), PDO::PARAM_INT);
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log('CancelPlanManager Error: ' . $e->getMessage());
            return [];
        }
    }

    public static function getTotal(string $search = '', string $result = ''): int
    {
        try {
            $params = [];
            $stmt = self::$pdo->prepare('SELECT COUNT(*) ' . self::fromSql() . self::whereSql($search, $result, $params));
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log('CancelPlanManager Count Error: ' . $e->getMessage());
            return 0;
        }
    }

    /** Totals per result for the summary cards. */
    public static function getSummary(): array
    {
        $out = ['total' => 0, 'cancelled' => 0, 'saved' => 0, 'abandoned' => 0];
        try {
            foreach (self::$pdo->query('SELECT final_action, COUNT(*) n FROM cancellation_sessions GROUP BY final_action') as $row) {
                $n = (int)$row['n'];
                $out['total'] += $n;
                $a = (string)$row['final_action'];
                if ($a === 'cancelled') {
                    $out['cancelled'] += $n;
                } elseif (in_array($a, ['resumed', 'kept_subscription', 'accepted_offer'], true)) {
                    $out['saved'] += $n;
                } else {
                    $out['abandoned'] += $n;
                }
            }
        } catch (PDOException $e) {
            error_log('CancelPlanManager Summary Error: ' . $e->getMessage());
        }
        return $out;
    }
}
