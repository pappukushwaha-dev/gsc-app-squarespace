<?php
/**
 * admin/core/StoreExport.php - CSV export of the admin store lists (2026-11-24, Wix-style filters).
 *  - from stores.php:          search, store_status, plan_status, plan_key, date_from, date_to, export_type
 *  - from partials/store-list: list=paid|test|unauthorized, search, plan (paid), plan_status, plan_key
 * Filter rules: /var/www/html/_shared/admin_store_filters.php. Old file: /var/backups/gsc-admin-wix-filters-allplatforms-20261124/
 */
declare(strict_types=1);
ini_set('display_errors', '0');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/SessionManager.php';
require_once __DIR__ . '/AuthController.php';
SessionManager::startDatabaseSession();
if (!AuthController::isAuthenticated()) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Forbidden');
}
require_once '/var/www/html/_shared/admin_store_filters.php';

$idCol   = 'shop_id';
$filters = gsc_sf_read($_GET);
$search  = trim((string) ($_GET['search'] ?? ''));
$list    = (string) ($_GET['list'] ?? '');
$rows    = [];

try {
    if (in_array($list, ['paid', 'test', 'unauthorized'], true)) {
        require_once __DIR__ . '/StoreListManager.php';
        StoreListManager::initialize($pdo);
        $plan = (string) ($_GET['plan'] ?? '');
        if ($list !== 'paid' || !isset(StoreListManager::PLAN_FILTERS[$plan])) {
            $plan = '';
        }
        $filters['export_type'] = 'all';
        if ($list === 'paid') {
            $filters['plan_status'] = 'all';
        }
        $src = StoreListManager::getStores($list, $search, $plan, 100000, 1, $filters);
        $labels = gsc_sf_plan_labels($pdo, $idCol, array_column($src, 'store_id'));
        foreach ($src as $r) {
            $rows[] = [
                'id'      => $r['store_id'],
                'name'    => $r['store_name'] ?? '',
                'email'   => $r['owner_email'] ?? '',
                'url'     => $r['site_url'] ?? '',
                'google'  => $r['google_email'] ?? '',
                'plan'    => $labels[(string) $r['store_id']]['label'] ?? 'No Plan',
                'status'  => ((int) ($r['is_active'] ?? 0) === 1) ? 'Active' : 'Inactive',
                'created' => $r['created_at'] ?? '',
            ];
        }
        $name = $list . '_stores';
    } else {
        require_once __DIR__ . '/StoreManager.php';
        StoreManager::initialize($pdo);
        $legacy = strtolower($search);
        if (in_array($legacy, ['active', 'inactive'], true)) {
            $filters['status'] = $legacy;
            $search = '';
        }
        foreach (StoreManager::getFilteredStores($search, 100000, 1, $filters) as $r) {
            $url = (string) ($r['domain'] ?? '');
            if ($url === '') {
                $url = (string) ($r['shop_domain'] ?? $r['site_url'] ?? '');
            }
            $rows[] = [
                'id'      => $r[$idCol] ?? '',
                'name'    => $r['shop_name'] ?? '',
                'email'   => $r['owner_email'] ?? ($r['email'] ?? ''),
                'url'     => $url,
                'google'  => $r['google_email'] ?? '',
                'plan'    => $r['plan_name'] ?? '',
                'status'  => $r['status_label'] ?? '',
                'created' => $r['created_at'] ?? '',
            ];
        }
        $name = 'stores';
    }
} catch (Throwable $e) {
    error_log('[StoreExport] ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Export failed. Please try again.');
}

gsc_sf_csv($name . '_export_' . date('Y-m-d_H-i-s') . '.csv', [
    '#'       => 'S.No.',
    'name'    => 'Store Name',
    'email'   => 'Email',
    'url'     => 'Site URL',
    'google'  => 'Google Account',
    'plan'    => 'Plan',
    'status'  => 'Status',
    'created' => 'Installed On',
    'id'      => 'Store ID',
], $rows);
