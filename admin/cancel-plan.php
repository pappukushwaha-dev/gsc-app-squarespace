<?php
/**
 * admin/cancel-plan.php
 *
 * What merchants answered in the in-app cancel flow (cancellation_sessions):
 * why they installed, why they leave, which help was offered, and how it
 * ended (cancelled / came back / kept / closed), plus their plan today.
 */
$title    = 'Cancel Plan';
$subTitle = 'Cancellation feedback';

ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/core/SessionManager.php';
require_once __DIR__ . '/core/AuthController.php';
require_once __DIR__ . '/core/CancelPlanManager.php';

SessionManager::startDatabaseSession();

if (!AuthController::isAuthenticated()) {
    header('Location: ' . APP_BASE . '/admin/sign-in.php');
    exit;
}

CancelPlanManager::initialize($pdo);

$perPage = (int)($_GET['show'] ?? 10);
$perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
$currentPage  = max(1, (int)($_GET['page'] ?? 1));
$searchQuery  = trim((string)($_GET['search'] ?? ''));
$resultFilter = (string)($_GET['result'] ?? '');
if ($resultFilter !== 'open' && !isset(CancelPlanManager::RESULTS[$resultFilter])) {
    $resultFilter = '';
}

$rows       = CancelPlanManager::getFilteredSessions($searchQuery, $resultFilter, $perPage, $currentPage);
$totalRows  = CancelPlanManager::getTotal($searchQuery, $resultFilter);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$summary    = CancelPlanManager::getSummary();

$h = static fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');

$badge = [
    'success' => 'bg-success-100 text-success-600 dark:bg-success-600/20 dark:text-success-400',
    'danger'  => 'bg-danger-100 text-danger-600 dark:bg-danger-600/20 dark:text-danger-400',
    'warning' => 'bg-warning-100 text-warning-600 dark:bg-warning-600/20 dark:text-warning-400',
    'info'    => 'bg-primary-100 text-primary-600 dark:bg-primary-600/20 dark:text-primary-400',
    'neutral' => 'bg-neutral-100 text-neutral-600 dark:bg-neutral-600/20 dark:text-neutral-300',
];
$resultBadge = static function (?string $a) use ($badge): array {
    switch ((string)$a) {
        case 'cancelled':         return ['Cancelled', $badge['danger']];
        case 'resumed':           return ['Came back', $badge['success']];
        case 'kept_subscription': return ['Kept plan', $badge['success']];
        case 'accepted_offer':    return ['Clicked offer', $badge['info']];
        case 'abandoned':         return ['Closed flow', $badge['neutral']];
        default:                  return ['Not finished', $badge['warning']];
    }
};
$offerLabels = [
    'support' => 'Contact support', 'guided_setup' => 'Guided setup', 'free_plan_info' => 'Free plan info',
    'reconnect' => 'Reconnect Google', 'seo_review' => 'SEO review', 'data_check' => 'Data check',
    'feedback' => 'Feedback', 'none' => 'No offer',
];
/** Plan today, from app_subscriptions (same rules as the app's sidebar). */
$planToday = static function (array $r) use ($badge): array {
    if (empty($r['plan_name'])) {
        return ['No paid plan', $badge['neutral']];
    }
    $live    = in_array(strtolower((string)$r['sub_status']), ['active', 'trialing', 'past_due'], true);
    $expires = !empty($r['expires_on']) ? strtotime((string)$r['expires_on']) : null;
    if ($live && empty($r['cancelled_at']) && ($expires === null || $expires + 86400 > time())) {
        return ['Active, renews', $badge['success']];
    }
    if ($expires && $expires > time()) {
        return ['Ends ' . date('j M Y', $expires), $badge['warning']];
    }
    return ['Ended', $badge['danger']];
};

$script = '
<script>
    $(document).ready(function () {
        var timer = null;
        function fetchRows(page) {
            var params = new URLSearchParams();
            params.set("show", $("#per-page-select").val());
            params.set("page", page || 1);
            var q = $("#search-input").val(); if (q) params.set("search", q);
            var r = $("#result-select").val(); if (r) params.set("result", r);
            $.get("cancel-plan.php?" + params.toString(), function (response) {
                var $r = $("<div>").html(response);
                $(".cp-table").html($r.find(".cp-table").html());
                $(".cp-pager").html($r.find(".cp-pager").html());
                window.history.replaceState({}, "", "cancel-plan.php?" + params.toString());
            });
        }
        $("#search-input").on("input", function () { clearTimeout(timer); timer = setTimeout(function () { fetchRows(1); }, 300); });
        $("#per-page-select, #result-select").on("change", function () { fetchRows(1); });
        $(document).on("click", ".cp-pager a[data-page]", function (e) { e.preventDefault(); fetchRows($(this).data("page")); });
    });
</script>
';

include './partials/layouts/layoutTop.php';
?>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php foreach ([
        ['Cancel flows started', $summary['total'], 'tabler:list-details', 'text-primary-600'],
        ['Cancelled', $summary['cancelled'], 'tabler:calendar-cancel', 'text-danger-600'],
        ['Saved (kept / came back)', $summary['saved'], 'tabler:heart-handshake', 'text-success-600'],
        ['Closed / not finished', $summary['abandoned'], 'tabler:door-exit', 'text-neutral-500'],
    ] as [$label, $value, $icon, $color]): ?>
        <div class="card rounded-xl border-0 p-5 bg-white dark:bg-neutral-700">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm text-neutral-500 dark:text-neutral-300 mb-1"><?= $h($label) ?></p>
                    <p class="text-2xl font-bold dark:text-white"><?= (int)$value ?></p>
                </div>
                <iconify-icon icon="<?= $h($icon) ?>" class="text-3xl <?= $h($color) ?>"></iconify-icon>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card rounded-xl border-0 overflow-hidden">

    <div class="card-header border-b bg-white dark:bg-neutral-700 py-4 px-6 flex flex-wrap items-center gap-3">
        <span class="text-base font-medium">Show</span>
        <select id="per-page-select" class="form-select form-select-sm w-auto dark:bg-neutral-600 rounded-lg">
            <?php foreach ([10, 25, 50, 100] as $n): ?>
                <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
            <?php endforeach; ?>
        </select>

        <select id="result-select" class="form-select form-select-sm w-auto dark:bg-neutral-600 rounded-lg">
            <option value="">All results</option>
            <?php foreach (CancelPlanManager::RESULTS as $k => $label): ?>
                <option value="<?= $h($k) ?>" <?= $resultFilter === $k ? 'selected' : '' ?>><?= $h($label) ?></option>
            <?php endforeach; ?>
            <option value="open" <?= $resultFilter === 'open' ? 'selected' : '' ?>>Not finished</option>
        </select>

        <form class="navbar-search" onsubmit="return false;">
            <input type="text" id="search-input" class="bg-white dark:bg-neutral-700 h-10"
                placeholder="Search store, email, reason" value="<?= $h($searchQuery) ?>">
            <iconify-icon icon="ion:search-outline" class="icon"></iconify-icon>
        </form>
    </div>

    <div class="card-body p-6">
        <div class="cp-table overflow-x-auto">
            <table class="table bordered-table sm-table text-sm">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Store</th>
                        <th>Result</th>
                        <th>Why they installed</th>
                        <th>Why they are leaving</th>
                        <th>Offer shown</th>
                        <th>Plan today</th>
                        <th>Last update</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="9" class="text-center py-6 text-neutral-500">No cancellation feedback yet.</td></tr>
                <?php else: foreach ($rows as $i => $r):
                    [$resLabel, $resClass]   = $resultBadge($r['final_action']);
                    [$planLabel, $planClass] = $planToday($r);
                    $offer = $offerLabels[$r['offer_type'] ?? ''] ?? ($r['offer_type'] ?: '—');
                    $detail = [
                        'store'      => $r['store_name'] ?: $r['store_id'],
                        'store_id'   => $r['store_id'],
                        'url'        => $r['site_url'],
                        'email'      => $r['email'],
                        'result'     => $resLabel,
                        'step'       => (int)$r['current_step'],
                        'join'       => $r['join_reason'],
                        'join_extra' => $r['join_extra'],
                        'leave'      => $r['leave_reason'],
                        'leave_extra'=> $r['leave_extra'],
                        'offer'      => $offer,
                        'offer_sel'  => $r['offer_selected'],
                        'plan'       => trim(($r['plan_name'] ?? '') . ' ' . ($r['billing_period'] ? '(' . $r['billing_period'] . ')' : '')),
                        'plan_today' => $planLabel,
                        'started'    => $r['created_at'],
                        'updated'    => $r['updated_at'],
                    ];
                ?>
                    <tr>
                        <td><?= (($currentPage - 1) * $perPage) + $i + 1 ?></td>
                        <td style="min-width:180px;max-width:260px">
                            <div class="font-semibold dark:text-white" style="word-break:break-all"><?= $h($r['store_name'] ?: $r['store_id']) ?></div>
                            <?php if (!empty($r['site_url'])):
                                $url = preg_match('~^https?://~i', $r['site_url']) ? $r['site_url'] : 'https://' . $r['site_url']; ?>
                                <a href="<?= $h($url) ?>" target="_blank" rel="noopener" class="text-primary-600 text-xs break-all"><?= $h($r['site_url']) ?></a>
                            <?php endif; ?>
                            <div class="text-xs text-neutral-500" style="word-break:break-all"><?= $h($r['email'] ?: '—') ?></div>
                        </td>
                        <td><span class="px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap <?= $resClass ?>"><?= $h($resLabel) ?></span>
                            <?php if (!$r['final_action']): ?><div class="text-xs text-neutral-500 mt-1">Stopped at step <?= (int)$r['current_step'] ?> of 4</div><?php endif; ?>
                        </td>
                        <td class="min-w-[160px]"><?= $h($r['join_reason'] ?: '—') ?></td>
                        <td class="min-w-[180px]">
                            <?= $h($r['leave_reason'] ?: '—') ?>
                            <?php if (!empty($r['leave_extra'])): ?>
                                <div class="text-xs text-neutral-500 italic mt-1">“<?= $h(mb_strimwidth((string)$r['leave_extra'], 0, 80, '…')) ?>”</div>
                            <?php endif; ?>
                        </td>
                        <td><?= $h($offer) ?></td>
                        <td>
                            <span class="px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap <?= $planClass ?>"><?= $h($planLabel) ?></span>
                            <?php if (!empty($r['plan_name'])): ?><div class="text-xs text-neutral-500 mt-1"><?= $h($r['plan_name']) ?></div><?php endif; ?>
                        </td>
                        <td class="whitespace-nowrap"><?= $h(date('j M Y, H:i', strtotime((string)$r['updated_at']))) ?></td>
                        <td>
                            <button type="button" title="View details"
                                class="bg-cstm-primary-10 hover:bg-cstm-primary-20 text-cstm-primary w-10 h-10 flex justify-center items-center rounded-full"
                                data-detail='<?= json_encode($detail, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'
                                onclick="showCancelDetails(JSON.parse(this.dataset.detail))">
                                <i class="fa-solid fa-eye text-lg"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="cp-pager flex flex-wrap items-center justify-between gap-3 mt-6">
            <span>Showing <?= $totalRows ? (($currentPage - 1) * $perPage) + 1 : 0 ?>
                to <?= min($currentPage * $perPage, $totalRows) ?> of <?= $totalRows ?> entries</span>
            <ul class="pagination flex gap-2">
                <li class="<?= ($currentPage <= 1) ? 'opacity-50 pointer-events-none' : '' ?>">
                    <a data-page="<?= $currentPage - 1 ?>" class="page-link bg-neutral-100 dark:bg-neutral-600 h-8 w-8 flex justify-center items-center rounded-lg text-sm" href="#">
                        <iconify-icon icon="ep:d-arrow-left"></iconify-icon></a>
                </li>
                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li><a data-page="<?= $p ?>" href="#" class="page-link h-8 w-8 flex justify-center items-center rounded-lg text-sm <?= ($p === $currentPage) ? 'bg-cstm-primary text-white' : 'bg-neutral-100 dark:bg-neutral-600' ?>"><?= $p ?></a></li>
                <?php endfor; ?>
                <li class="<?= ($currentPage >= $totalPages) ? 'opacity-50 pointer-events-none' : '' ?>">
                    <a data-page="<?= $currentPage + 1 ?>" class="page-link bg-neutral-100 dark:bg-neutral-600 h-8 w-8 flex justify-center items-center rounded-lg text-sm" href="#">
                        <iconify-icon icon="ep:d-arrow-right"></iconify-icon></a>
                </li>
            </ul>
        </div>
    </div>
</div>

<div id="cpModal" class="fixed inset-0 bg-cstm-black-50 hidden items-center justify-center z-50 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-neutral-800 rounded-xl shadow-lg max-w-2xl w-full p-6 relative max-h-[90vh] overflow-y-auto">
        <button type="button" onclick="closeCpModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 text-xl" aria-label="Close">&times;</button>
        <div id="cpModalContent"></div>
    </div>
</div>

<script>
    function cpEsc(v) { var d = document.createElement('div'); d.textContent = (v === null || v === undefined || v === '') ? '—' : String(v); return d.innerHTML; }
    function showCancelDetails(d) {
        var field = function (label, value) {
            return '<div class="pb-2 border-b border-gray-100 dark:border-gray-600"><p class="font-medium text-gray-500 dark:text-gray-400">' + label + '</p>'
                 + '<p class="text-gray-800 dark:text-gray-200" style="word-break:break-all">' + cpEsc(value) + '</p></div>';
        };
        var block = function (label, value, extra) {
            return '<div class="col-span-full"><p class="font-medium text-gray-500 dark:text-gray-400 mb-1">' + label + '</p>'
                 + '<div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg text-gray-800 dark:text-gray-200">' + cpEsc(value)
                 + (extra ? '<div class="mt-2 italic text-gray-600 dark:text-gray-300">“' + cpEsc(extra) + '”</div>' : '') + '</div></div>';
        };
        document.getElementById('cpModalContent').innerHTML =
            '<h2 class="text-xl font-bold mb-5 text-gray-900 dark:text-white">Cancellation details</h2>'
          + '<div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3 text-sm">'
          + field('Store', d.store) + field('Store ID', d.store_id)
          + field('Store URL', d.url) + field('Email', d.email)
          + field('Result', d.result) + field('Reached step', d.step + ' of 4')
          + field('Plan', d.plan) + field('Plan today', d.plan_today)
          + block('Why they installed', d.join, d.join_extra)
          + block('Why they are leaving', d.leave, d.leave_extra)
          + field('Offer shown', d.offer) + field('Offer clicked', d.offer_sel)
          + field('Started', d.started) + field('Last update', d.updated)
          + '</div>';
        var m = document.getElementById('cpModal'); m.classList.remove('hidden'); m.classList.add('flex');
    }
    function closeCpModal() { var m = document.getElementById('cpModal'); m.classList.add('hidden'); m.classList.remove('flex'); }
    document.getElementById('cpModal').addEventListener('click', function (e) { if (e.target === this) closeCpModal(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeCpModal(); });
</script>

<?php include './partials/layouts/layoutBottom.php'; ?>
