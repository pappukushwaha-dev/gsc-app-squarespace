<?php
declare(strict_types=1);

/**
 * Lightweight user-panel activity tracking for the Squarespace app:
 * logins, logouts, page views and key merchant actions (disconnect).
 *
 * One INSERT per event, wrapped so that tracking can never take the
 * product down: a missing table, a DB hiccup - anything - is swallowed
 * and the page renders as if the logger were not there.
 *
 * Refresh spam is filtered: an identical page_view (same shop + URL)
 * within 5 minutes is skipped.
 *
 * Squarespace keys its tables on shop_id (not instance_id); the
 * activity table's instance_id column carries that shop_id value so the
 * admin viewer stays identical across platforms.
 *
 * View the data in admin/activity-logs.php.
 * Table: user_activity_logs (see 01-user-activity-logs.sql).
 */

if (!function_exists('log_activity')) {

    /**
     * One activity row for the shop in the session. page_view events are
     * de-duplicated: the same shop + URL inside 5 minutes logs once,
     * so refreshes do not flood the table.
     */
    function log_activity(string $actionType, string $pageUrl = ''): void
    {
        global $pdo;

        try {
            if (!isset($pdo) || !($pdo instanceof PDO)) {
                error_log('[activity-logger] skip: pdo unavailable');
                return;
            }

            $shopId = (string) ($_SESSION['shop_id'] ?? $_SESSION['shopid'] ?? '');
            if ($shopId === '') {
                error_log('[activity-logger] skip: no shop in session');
                return;
            }

            $pageUrl = substr($pageUrl, 0, 500);

            /* Same page + refresh -> skip. A real visit after the window
               logs again, so repeated distinct visits are still tracked. */
            if ($actionType === 'page_view' && $pageUrl !== '') {
                $dup = $pdo->prepare(
                    "SELECT id FROM user_activity_logs
                     WHERE instance_id = :iid AND action_type = 'page_view' AND page_url = :page
                       AND created_at >= (NOW() - INTERVAL 5 MINUTE)
                     LIMIT 1"
                );
                $dup->execute([':iid' => $shopId, ':page' => $pageUrl]);
                if ($dup->fetchColumn()) {
                    return;
                }
            }

            /* Owner email for this shop - resolved once per request and
               reused by the login + page_view calls that follow. */
            static $emailCache = null;
            if ($emailCache === null) {
                $emailCache = '';
                $stmt = $pdo->prepare('SELECT owner_email FROM SquarespaceSite WHERE shop_id = :id LIMIT 1');
                $stmt->execute([':id' => $shopId]);
                $emailCache = (string) ($stmt->fetchColumn() ?: '');
            }

            $stmt = $pdo->prepare(
                'INSERT INTO user_activity_logs
                    (instance_id, email, action_type, page_url, ip_address, user_agent)
                 VALUES
                    (:iid, :email, :action, :page, :ip, :agent)'
            );
            $stmt->execute([
                ':iid'    => substr($shopId, 0, 191),
                ':email'  => substr($emailCache, 0, 320),
                ':action' => $actionType,
                ':page'   => $pageUrl,
                ':ip'     => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
                ':agent'  => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
            ]);
        } catch (Throwable $e) {
            // Tracking must never break the product.
        }
    }

    /**
     * Called once per page render from partials/layouts/layoutTop.php.
     * Logs a 'login' the first time a session is seen, then a
     * 'page_view' for the page being opened.
     */
    function track_page_activity(): void
    {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                return;
            }

            $shopId = $_SESSION['shop_id'] ?? $_SESSION['shopid'] ?? null;
            if (!$shopId) {
                return;
            }

            if (empty($_SESSION['_activity_login_logged'])) {
                $_SESSION['_activity_login_logged'] = 1;
                log_activity('login');
            }

            log_activity('page_view', (string) ($_SERVER['REQUEST_URI'] ?? ''));
        } catch (Throwable $e) {
            // never break the page
        }
    }
}
