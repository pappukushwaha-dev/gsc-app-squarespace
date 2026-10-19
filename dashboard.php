<?php
// dashboard.php — Squarespace GSC Dashboard
// Same pattern as Wix/Shopify dashboard.php

declare(strict_types=1);

$title = 'Dashboard';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/credentials.php';

// Auth check
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_logged_in'])) {
    header('Location: sign-in.php');
    exit;
}

// URL params first: a deep link or the magic-login redirect names the
// instance explicitly, and the session (one cookie shared across the
// family panels on this domain) must never override it.
$instanceId = $_GET['instanceid']
    ?? $_GET['instance_id']
    ?? $_SESSION['instanceid']
    ?? $_SESSION['instance_id']
    ?? null;

// Fetch site info
$siteInfo = [];
if ($instanceId) {
    $stmt = $pdo->prepare("
        SELECT site_url, site_display_name, is_active, event_type, created_at
        FROM SquarespaceSite
        WHERE instance_id = ?
        LIMIT 1
    ");
    $stmt->execute([$instanceId]);
    $siteInfo = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
}

// Fetch verification status
$verificationStatus = 'pending';
if ($instanceId) {
    $stmt = $pdo->prepare("SELECT verification_status FROM gsc_domain_verifications WHERE instance_id = ? LIMIT 1");
    $stmt->execute([$instanceId]);
    $verificationStatus = $stmt->fetchColumn() ?? 'pending';
}

// Fetch Google connection status
$googleConnected = false;
if ($instanceId) {
    $stmt = $pdo->prepare("SELECT connected FROM google_accounts WHERE instance_id = ? LIMIT 1");
    $stmt->execute([$instanceId]);
    $googleConnected = (bool) ($stmt->fetchColumn() ?? 0);
}

// Mock stats (in real implementation, these come from GSC API via cron)
$stats = [
    'total_clicks'    => 0,
    'total_impressions' => 0,
    'avg_ctr'         => '0.00',
    'avg_position'    => '0.00',
    'total_queries'   => 0,
];
?>
<?php include __DIR__ . '/partials/layouts/layoutTopnew.php'; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Dashboard</h1>
    <p class="text-neutral-600 dark:text-neutral-400">
        <?= htmlspecialchars($siteInfo['site_display_name'] ?? 'Squarespace Site') ?>
        <span class="text-sm text-neutral-500 dark:text-neutral-400 font-normal">
            (<?= htmlspecialchars($siteInfo['site_url'] ?? '-') ?>)
        </span>
    </p>
</div>

<!-- Connection Status Alert -->
<?php if (!$googleConnected): ?>
<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-6">
    <strong>Google not connected.</strong>
    <a href="setup-wizard.php?step=1" class="text-yellow-700 underline ml-2">Connect with Google</a>
    to start tracking Search Console data.
</div>
<?php elseif ($verificationStatus !== 'verified'): ?>
<div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded mb-6">
    <strong>Domain not verified.</strong>
    <a href="setup-wizard.php?step=2" class="text-blue-700 underline ml-2">Verify Domain</a>
    to enable data tracking.
</div>
<?php else: ?>
<div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6">
    <strong>Connected & Verified!</strong>
    <span class="ml-2">Your Squarespace site is tracking in Google Search Console.</span>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <div class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Total Clicks</div>
        <div class="text-3xl font-bold text-neutral-800 dark:text-white mt-1"><?= number_format($stats['total_clicks']) ?></div>
    </div>
    <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <div class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Impressions</div>
        <div class="text-3xl font-bold text-blue-600 dark:text-blue-400 mt-1"><?= number_format($stats['total_impressions']) ?></div>
    </div>
    <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <div class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Avg CTR</div>
        <div class="text-3xl font-bold text-green-600 dark:text-green-400 mt-1"><?= $stats['avg_ctr'] ?>%</div>
    </div>
    <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <div class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider">Avg Position</div>
        <div class="text-3xl font-bold text-purple-600 dark:text-purple-400 mt-1"><?= $stats['avg_position'] ?></div>
    </div>
</div>

<!-- Reports Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <?php include __DIR__ . '/partials/reports/kpis.php'; ?>
    <?php include __DIR__ . '/partials/reports/top_pages.php'; ?>
    <?php include __DIR__ . '/partials/reports/traffic_sources.php'; ?>
    <?php include __DIR__ . '/partials/reports/trends.php'; ?>
</div>

<!-- Rich Results Report -->
<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white">Rich Results</h2>
    </div>
    <?php include __DIR__ . '/partials/reports/rich_results_summary.php'; ?>
</div>

<?php include __DIR__ . '/partials/layouts/layoutBottom.php'; ?>
