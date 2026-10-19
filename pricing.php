<?php

declare(strict_types=1);

/**
 * pricing.php — plan cards + checkout.
 * Reads pricing_plans from the database; paid buttons post to
 * api/billing.php with shop_id + price_id. Buttons for prices that are
 * not mapped to a Stripe price yet render as "coming soon" (disabled).
 */

declare(strict_types=1);

$title    = 'Pricing';
$subTitle = 'Plans';

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/plan_guard.php';

$shopId = trim((string) ($_SESSION['shop_id'] ?? $_GET['shop_id'] ?? ''));
$plans  = [];

try {
    $rows = $pdo->query("
        SELECT id, name, code, price, billingPeriod, price_id
        FROM pricing_plans
        WHERE showInMarket = 1
        ORDER BY FIELD(billingPeriod, 'free', 'monthly', 'yearly'), id
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('pricing load failed: ' . $e->getMessage());
    $rows = [];
}

$free = null; $monthly = null; $yearly = null;
foreach ($rows as $r) {
    if ($r['billingPeriod'] === 'free')   { $free = $r; }
    if ($r['billingPeriod'] === 'monthly') { $monthly = $r; }
    if ($r['billingPeriod'] === 'yearly')  { $yearly = $r; }
}

$currentPlan = $shopId !== '' ? getPaidPlan($shopId) : null;
$currentBilling = $currentPlan['billing_period'] ?? 'free';
$currentLabel = $currentPlan
    ? $currentPlan['plan_name'] . ' (' . $currentPlan['billing_period'] . ')'
    : 'Free';

require_once __DIR__ . '/partials/layouts/layoutTopnew.php';
?>

<div class="max-w-6xl mx-auto px-4 py-10">
    <div class="text-center mb-8">
        <h1 class="text-3xl font-bold text-neutral-900 dark:text-white">Plans &amp; Pricing</h1>
        <p class="text-gray-500 mt-2">Current plan: <strong><?= htmlspecialchars($currentLabel) ?></strong></p>
        <?php if ($shopId === ''): ?>
            <p class="text-sm text-gray-500 mt-2">
                <a href="register.php" class="text-cstm-primary font-medium hover:underline">Register your Squarespace site</a>
                to activate a plan.
            </p>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- FREE -->
        <div class="bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700 p-6 flex flex-col">
            <h3 class="font-bold text-lg">Basic</h3>
            <p class="text-3xl font-bold mt-2">$0<span class="text-sm text-gray-500 font-medium">/forever</span></p>
            <ul class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300 flex-1">
                <li>✓ 1 Squarespace site</li>
                <li>✓ Search Console verification</li>
                <li>✓ Basic reports</li>
            </ul>
            <?php if ($shopId !== ''): ?>
            <form method="POST" action="api/billing.php" class="mt-5">
                <input type="hidden" name="shop_id" value="<?= htmlspecialchars($shopId) ?>">
                <input type="hidden" name="billing_period" value="free">
                <button type="submit" class="w-full py-2.5 rounded-lg bg-neutral-800 dark:bg-neutral-600 text-white font-semibold">
                    <?= ($currentBilling ?? '') === 'free' ? 'Current plan' : 'Switch to Basic' ?>
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- BOOSTER MONTHLY -->
        <div class="bg-white dark:bg-neutral-800 rounded-2xl border-2 border-cstm-primary p-6 flex flex-col relative shadow-lg">
            <span class="absolute -top-3 left-1/2 -translate-x-1/2 bg-cstm-primary text-white text-xs font-bold px-3 py-1 rounded-full">Most popular</span>
            <h3 class="font-bold text-lg">Organic Booster</h3>
            <p class="text-3xl font-bold mt-2">$20<span class="text-sm text-gray-500 font-medium">/month</span></p>
            <ul class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300 flex-1">
                <li>✓ Everything in Basic</li>
                <li>✓ Full search reports</li>
                <li>✓ Content pings — unlimited</li>
                <li>✓ Priority support</li>
            </ul>
            <?php if ($shopId !== '' && ($monthly['price_id'] ?? '') !== ''): ?>
            <form method="POST" action="api/billing.php" class="mt-5">
                <input type="hidden" name="shop_id" value="<?= htmlspecialchars($shopId) ?>">
                <input type="hidden" name="price_id" value="<?= htmlspecialchars($monthly['price_id']) ?>">
                <input type="hidden" name="billing_period" value="monthly">
                <button type="submit" class="w-full py-2.5 rounded-lg bg-cstm-primary text-white font-semibold">
                    <?= ($currentPlan['billing_period'] ?? '') === 'monthly' ? 'Current plan' : 'Start Monthly' ?>
                </button>
            </form>
            <?php else: ?>
            <button type="button" disabled class="mt-5 w-full py-2.5 rounded-lg bg-gray-300 dark:bg-neutral-600 text-gray-600 dark:text-gray-300 font-semibold cursor-not-allowed">
                Coming soon
            </button>
            <?php endif; ?>
        </div>

        <!-- BOOSTER YEARLY -->
        <div class="bg-white dark:bg-neutral-800 rounded-2xl border border-neutral-200 dark:border-neutral-700 p-6 flex flex-col">
            <h3 class="font-bold text-lg">Organic Booster <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full align-middle">🎁 3 months free</span></h3>
            <p class="text-3xl font-bold mt-2">$14<span class="text-sm text-gray-500 font-medium">/month</span></p>
            <p class="text-xs text-gray-400">billed $168 yearly</p>
            <ul class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-300 flex-1">
                <li>✓ Everything in Monthly</li>
                <li>✓ Same features, yearly billing</li>
                <li>✓ Best value for growing stores</li>
            </ul>
            <?php if ($shopId !== '' && ($yearly['price_id'] ?? '') !== ''): ?>
            <form method="POST" action="api/billing.php" class="mt-5">
                <input type="hidden" name="shop_id" value="<?= htmlspecialchars($shopId) ?>">
                <input type="hidden" name="price_id" value="<?= htmlspecialchars($yearly['price_id']) ?>">
                <input type="hidden" name="billing_period" value="yearly">
                <button type="submit" class="w-full py-2.5 rounded-lg bg-cstm-primary text-white font-semibold">
                    <?= ($currentPlan['billing_period'] ?? '') === 'yearly' ? 'Current plan' : 'Start Yearly' ?>
                </button>
            </form>
            <?php else: ?>
            <button type="button" disabled class="mt-5 w-full py-2.5 rounded-lg bg-gray-300 dark:bg-neutral-600 text-gray-600 dark:text-gray-300 font-semibold cursor-not-allowed">
                Coming soon
            </button>
            <?php endif; ?>
        </div>
    </div>

    <p class="text-center text-xs text-gray-400 mt-8">
        Payments are processed securely by Stripe. Cancel anytime from your dashboard.
    </p>
</div>

<?php require_once __DIR__ . '/partials/layouts/layoutBottom.php'; ?>
