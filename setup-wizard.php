<?php
// setup-wizard.php — Squarespace GSC Setup Wizard entry point
// Same pattern as Wix/Shopify setup-wizard.php

declare(strict_types=1);

$title    = 'Google Search Console Setup';
$subTitle = 'Setup';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/credentials.php';

// --- Instance / site check ---
// Squarespace uses site_id as instance_id equivalent
$instanceId = $_SESSION['instanceid']
    ?? $_SESSION['instance_id']
    ?? $_SESSION['squarespace_site_id']
    ?? null;

if (!$instanceId) {
    header("Location: step-one.php");
    exit();
}

// Check subscription (same as Shopify)
$subStmt = $pdo->prepare("
    SELECT plan_name, status
    FROM app_subscriptions
    WHERE instance_id = :iid
    LIMIT 1
");
$subStmt->execute([':iid' => $instanceId]);
$subscription = $subStmt->fetch(PDO::FETCH_ASSOC);
$hasActiveSubscription = ($subscription && $subscription['status'] === 'active');

// --- Step handling ---
$step = $_GET['step'] ?? '1';
if (!in_array($step, ['1', '2', '3'], true)) {
    $step = '1';
}

$steps = [
    '1' => ['label' => 'Step 1', 'title' => 'Connect Your Domain'],
    '2' => ['label' => 'Step 2', 'title' => 'Verify Domain or Add New'],
    '3' => ['label' => 'Step 3', 'title' => 'Submit Sitemap'],
];

$currentStep = (int)$step;
$prevStep    = max(1, $currentStep - 1);
$nextStep    = min(count($steps), $currentStep + 1);

// --- Fetch Squarespace site data ---
$siteUrl = null;
$siteStmt = $pdo->prepare("
    SELECT site_url, site_display_name
    FROM SquarespaceSite
    WHERE instance_id = :iid
      AND is_active = 1
    LIMIT 1
");
$siteStmt->execute([':iid' => $instanceId]);
$siteRow = $siteStmt->fetch(PDO::FETCH_ASSOC);
if ($siteRow) {
    $siteUrl = $siteRow['site_url'];
}

// --- Layout start ---
include './partials/layouts/layoutTopnew.php';
include __DIR__ . '/partials/ticket-submit-popup.php';
include __DIR__ . '/partials/instruction-popup.php';
?>
<style>
    .box-shadow-cstm {
        box-shadow: 0 0 0 4px #fff;
    }
    .dark .box-shadow-cstm {
        box-shadow: 0 0 0 4px #273142;
    }
    .muted-progress-line {
        left: 15%;
        right: 15%;
        margin-top: -6px;
        margin-inline: auto;
        top: 22px;
    }
    .progress-line {
        left: 15%;
        margin-top: -6px;
        top: 22px;
    }
</style>

<!-- ===================== HEADER CARD ===================== -->
<div class="border border-neutral-200 bg-white shadow-sm mb-6 rounded-lg"
    style="background:linear-gradient(135deg,#c8dcfa 0%,#e4ebf9 100%)">
    <div class="grid grid-cols-1 md:grid-cols-3 items-center gap-2 px-6 py-4">
        <div>
            <h4 class="text-xl font-semibold flex items-center gap-2">
                <iconify-icon icon="heroicons:globe-alt-solid" class="text-2xl text-cstm-primary"></iconify-icon>
                Website
            </h4>
            <p class="mt-2">
                <?php if ($siteUrl): ?>
                    <a href="<?= htmlspecialchars($siteUrl) ?>"
                        target="_blank"
                        class="px-2 py-1.5 border rounded-lg text-xs font-medium bg-white text-cstm-primary hover:underline max-w-max truncate border-gray-300">
                        <?= htmlspecialchars(parse_url($siteUrl, PHP_URL_HOST)) ?>
                    </a>
                <?php else: ?>
                    <span class="text-sm text-gray-500">Website not linked</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="text-center">
            <h4 class="text-4xl font-semibold text-neutral-800 dark:text-neutral-100 text-center">
                Setup Wizard
            </h4>
            <p class="text-neutral-600 text-center text-sm mt-1">Follow the steps to configure your Squarespace site.</p>
        </div>
        <div class="flex justify-end">
            <button id="support-ticket-btn" class="btn btn-cstm-primary flex justify-center items-center gap-2 text-base">
                <iconify-icon icon="bx:support" width="18" height="18"></iconify-icon>
                Support
            </button>
        </div>
    </div>
</div>

<!-- ===================== PROGRESS BAR ===================== -->
<div class="max-w-4xl mx-auto px-4 mb-6">
    <div class="relative flex items-center mb-4">
        <div class="flex-1 h-1 bg-neutral-200 dark:bg-neutral-700 rounded-full"></div>
        <?php foreach ($steps as $key => $stepInfo): ?>
            <?php $isActive = $key === $step; ?>
            <div class="flex-none w-12 h-12 rounded-full flex items-center justify-center
                <?php echo $isActive ? 'bg-cstm-primary text-white' : 'bg-neutral-200 dark:bg-neutral-700 text-neutral-500'; ?>
                z-10 relative">
                <span class="text-sm font-bold"><?= $key ?></span>
            </div>
            <?php if ($key < count($steps)): ?>
                <div class="flex-1 h-1
                    <?php echo $currentStep > (int)$key ? 'bg-cstm-primary' : 'bg-neutral-200 dark:bg-neutral-700'; ?>
                "></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <div class="flex justify-between text-xs font-medium text-neutral-500 dark:text-neutral-400 mb-2">
        <?php foreach ($steps as $key => $stepInfo): ?>
            <span class="<?php echo $key === $step ? 'font-bold text-cstm-primary' : ''; ?>"><?= htmlspecialchars($stepInfo['title']) ?></span>
        <?php endforeach; ?>
    </div>
</div>

<!-- ===================== STEP CONTENT ===================== -->
<div class="max-w-4xl mx-auto px-4">
    <?php
    switch ($step) {
        case '1':
            include __DIR__ . '/step-one.php';
            break;
        case '2':
            include __DIR__ . '/step-two.php';
            break;
        case '3':
            include __DIR__ . '/step-three.php';
            break;
    }
    ?>

    <!-- Navigation buttons -->
    <div class="mt-8 flex justify-between">
        <?php if ($currentStep > 1): ?>
            <a href="setup-wizard.php?step=<?= $prevStep ?>"
                class="btn btn-outline-primary w-full md:w-auto flex items-center justify-center gap-2">
                <iconify-icon icon="ic:round-arrow-back"></iconify-icon> Previous
            </a>
        <?php endif; ?>
        <?php if ($currentStep < count($steps)): ?>
            <a href="setup-wizard.php?step=<?= $nextStep ?>"
                class="btn btn-cstm-primary w-full md:w-auto flex items-center justify-center gap-2">
                Next <iconify-icon icon="ic:round-arrow-forward"></iconify-icon>
            </a>
        <?php else: ?>
            <a href="dashboard.php"
                class="btn btn-cstm-primary w-full md:w-auto flex items-center justify-center gap-2">
                Go to Dashboard <iconify-icon icon="ic:round-arrow-forward"></iconify-icon>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php
include './partials/layouts/layoutBottom.php';
?>
