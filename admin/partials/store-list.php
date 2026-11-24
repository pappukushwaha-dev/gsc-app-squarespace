<?php
/**
 * admin/partials/store-list.php
 *
 * Shared page for paid-stores.php, unauthorized-stores.php and test-stores.php.
 * The including file sets $listType ('paid' | 'unauthorized' | 'test').
 */
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../core/SessionManager.php';
require_once __DIR__ . '/../core/AuthController.php';
require_once __DIR__ . '/../core/StoreListManager.php';

SessionManager::startDatabaseSession();

if (!AuthController::isAuthenticated()) {
    header('Location: ' . APP_BASE . '/admin/sign-in.php');
    exit;
}

$listType = isset(StoreListManager::TYPES[$listType ?? '']) ? $listType : 'paid';
$title    = StoreListManager::TYPES[$listType]['title'];
$subTitle = $title;
$selfUrl  = basename($_SERVER['SCRIPT_NAME'] ?? 'paid-stores.php');

StoreListManager::initialize($pdo);

$perPage = (int)($_GET['show'] ?? 10);
$perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$searchQuery = trim((string)($_GET['search'] ?? ''));
$planFilter  = (string)($_GET['plan'] ?? '');
if ($listType !== 'paid' || !isset(StoreListManager::PLAN_FILTERS[$planFilter])) {
    $planFilter = '';
}

// 2026-11-24: Wix-style filter bar (plan status / plan name), /var/www/html/_shared/admin_store_filters.php
require_once '/var/www/html/_shared/admin_store_filters.php';
$sfFilters = gsc_sf_read($_GET);
$sfFilters['export_type'] = 'all';
$sfFilters['status'] = 'all';
if ($listType === 'paid') {
    $sfFilters['plan_status'] = 'all'; // the Paid list keeps its own plan filter (?plan=)
}

$stores      = StoreListManager::getStores($listType, $searchQuery, $planFilter, $perPage, $currentPage, $sfFilters);
$totalStores = StoreListManager::getTotal($listType, $searchQuery, $planFilter, $sfFilters);
$totalPages  = max(1, (int)ceil($totalStores / $perPage));

$h = static fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$tone = [
    'success' => 'bg-success-100 text-success-600 dark:bg-success-600/20 dark:text-success-400',
    'danger'  => 'bg-danger-100 text-danger-600 dark:bg-danger-600/20 dark:text-danger-400',
    'warning' => 'bg-warning-100 text-warning-600 dark:bg-warning-600/20 dark:text-warning-400',
    'neutral' => 'bg-neutral-100 text-neutral-600 dark:bg-neutral-600/20 dark:text-neutral-300',
];
$idParam = StoreListManager::ID_COL;

$script = str_replace(['__SELF__', '__PAID__', '__LIST__'], [json_encode($selfUrl), $listType === 'paid' ? 'true' : 'false', json_encode($listType)], <<<'JS'
<script>
    // 2026-11-24: Wix-style filters (shared header from /var/www/html/_shared/admin_store_filters.php)
    $(document).ready(function () {
        var timer = null, self = __SELF__, isPaid = __PAID__, listType = __LIST__;
        function params(page) {
            var p = new URLSearchParams();
            p.set("show", $("#per-page-select").val());
            p.set("page", page || 1);
            var q = ($("#search-input").val() || "").trim(); if (q) p.set("search", q);
            var ps = $("#plan-status-select").val() || ""; if (ps && ps !== "all") p.set(isPaid ? "plan" : "plan_status", ps);
            var pk = $("#plan-name-select").val() || "all"; if (pk !== "all") p.set("plan_key", pk);
            return p;
        }
        function fetchRows(page) {
            var p = params(page);
            $.get(self + "?" + p.toString(), function (response) {
                var $r = $("<div>").html(response);
                $(".sl-table").html($r.find(".sl-table").html());
                $(".sl-pager").html($r.find(".sl-pager").html());
                $("#totalRecords").text($r.find("#totalRecords").text());
                window.history.replaceState({}, "", self + "?" + p.toString());
            });
        }
        $("#search-input").on("input", function () { clearTimeout(timer); timer = setTimeout(function () { fetchRows(1); }, 300); });
        $("#per-page-select, #plan-status-select, #plan-name-select").on("change", function () { fetchRows(1); });
        $(document).on("click", ".sl-pager a[data-page]", function (e) { e.preventDefault(); fetchRows($(this).data("page")); });
        $("#reset-filters").on("click", function () {
            $("#search-input").val("");
            $("#per-page-select, #plan-status-select, #plan-name-select").prop("selectedIndex", 0);
            fetchRows(1);
        });
        $("#exportData").on("click", function () {
            var p = params(1);
            p.delete("show"); p.delete("page");
            p.set("list", listType);
            p.set("export", "1");
            window.location.href = "./core/StoreExport.php?" + p.toString();
        });
    });
</script>
JS);

include './partials/layouts/layoutTop.php';
?>

<div class="card rounded-xl border-0 overflow-hidden">
    <?= gsc_sf_header_html([
        'heading'             => $title,
        'total'               => $totalStores,
        'search'              => $searchQuery,
        'per_page'            => $perPage,
        'filters'             => $sfFilters,
        'plan_status_options' => $listType === 'paid' ? ['' => 'All'] + StoreListManager::PLAN_FILTERS : GSC_SF_PLAN_STATUS,
        'plan_status_value'   => $listType === 'paid' ? $planFilter : $sfFilters['plan_status'],
    ]) ?>

    <div class="card-body p-6">
        <div class="sl-table cstm-scroll-sm overflow-x-auto">
            <table class="table bordered-table sm-table table-auto text-sm">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Name / Email</th>
                        <th>Site URL</th>
                        <th>Google Account</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Installed On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($stores)): ?>
                    <tr><td colspan="8" class="text-center py-6 text-neutral-500"><?= $h(StoreListManager::TYPES[$listType]['empty']) ?></td></tr>
                <?php else: foreach ($stores as $i => $s):
                    $name = $s['store_name'] ?: $s['store_id'];
                    $url  = (string)($s['site_url'] ?? '');
                    $href = ($url !== '' && !preg_match('~^https?://~i', $url)) ? 'https://' . $url : $url;
                    [$planLabel, $planTone] = StoreListManager::planState($s);
                    $active = (int)($s['is_active'] ?? 0) === 1;
                ?>
                    <tr>
                        <td><?= (($currentPage - 1) * $perPage) + $i + 1 ?></td>
                        <td style="max-width:260px">
                            <div class="flex items-center gap-2">
                                <div class="w-8 h-8 shrink-0 rounded-full bg-cstm-secondary flex items-center justify-center text-sm font-bold text-white">
                                    <?= $h(strtoupper(mb_substr((string)$name, 0, 1))) ?>
                                </div>
                                <div style="word-break:break-all">
                                    <span class="block font-medium"><?= $h($name) ?></span>
                                    <span class="block text-xs text-secondary-light"><?= $h($s['owner_email'] ?: 'No Email') ?></span>
                                </div>
                            </div>
                        </td>
                        <td style="max-width:240px;word-break:break-all">
                            <?php if ($href !== ''): ?>
                                <a href="<?= $h($href) ?>" target="_blank" rel="noopener" class="text-primary-500 hover:text-primary-600"><?= $h($url) ?></a>
                            <?php else: ?>
                                <span class="text-neutral-400">Not connected</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $h($s['google_email'] ?: '—') ?></td>
                        <td>
                            <?php if (!empty($s['plan_name'])): ?>
                                <div class="font-medium whitespace-nowrap"><?= $h($s['plan_name']) ?><?= $s['billing_period'] ? ' (' . $h(ucfirst((string)$s['billing_period'])) . ')' : '' ?></div>
                            <?php endif; ?>
                            <span class="inline-block mt-1 px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap <?= $tone[$planTone] ?>"><?= $h($planLabel) ?></span>
                        </td>
                        <td>
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-medium <?= $active ? $tone['success'] : $tone['danger'] ?>"><?= $active ? 'Active' : 'Inactive' ?></span>
                        </td>
                        <td class="whitespace-nowrap"><?= $s['created_at'] ? $h(date('Y-m-d H:i', strtotime((string)$s['created_at']))) : 'N/A' ?></td>
                        <td>
                            <a href="store-view.php?<?= $idParam ?>=<?= urlencode((string)$s['store_id']) ?>"
                                class="bg-cstm-primary-20 hover:bg-cstm-primary-30 w-10 h-10 flex justify-center items-center rounded-full text-cstm-primary"
                                title="View Store">
                                <iconify-icon icon="solar:eye-bold" class="text-xl"></iconify-icon>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="sl-pager flex items-center justify-between flex-wrap gap-2 mt-6">
            <span class="text-sm">Showing <?= $totalStores ? (($currentPage - 1) * $perPage) + 1 : 0 ?>
                to <?= min($currentPage * $perPage, $totalStores) ?> of <?= $totalStores ?> entries</span>
            <ul class="pagination flex items-center gap-2">
                <li class="<?= ($currentPage <= 1) ? 'opacity-50 pointer-events-none' : '' ?>">
                    <a href="#" data-page="<?= max(1, $currentPage - 1) ?>" class="page-link bg-neutral-100 dark:bg-neutral-700 rounded-lg h-8 w-8 flex items-center justify-center">
                        <iconify-icon icon="ep:d-arrow-left"></iconify-icon></a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++):
                    if ($p === 1 || $p === $totalPages || abs($p - $currentPage) <= 2): ?>
                    <li><a href="#" data-page="<?= $p ?>" class="page-link rounded-lg h-8 w-8 flex items-center justify-center text-sm <?= ($p === $currentPage) ? 'bg-cstm-primary text-white' : 'bg-neutral-100 dark:bg-neutral-700' ?>"><?= $p ?></a></li>
                <?php elseif (abs($p - $currentPage) === 3): ?>
                    <li class="px-1 text-neutral-400">…</li>
                <?php endif; endfor; ?>
                <li class="<?= ($currentPage >= $totalPages) ? 'opacity-50 pointer-events-none' : '' ?>">
                    <a href="#" data-page="<?= min($totalPages, $currentPage + 1) ?>" class="page-link bg-neutral-100 dark:bg-neutral-700 rounded-lg h-8 w-8 flex items-center justify-center">
                        <iconify-icon icon="ep:d-arrow-right"></iconify-icon></a>
                </li>
            </ul>
        </div>
    </div>
</div>

<?php include './partials/layouts/layoutBottom.php'; ?>
