<?php
// current-plan.php — Current Subscription Plan
declare(strict_types=1);
$title = "Current Plan";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/credentials.php";
if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) { header("Location: sign-in.php"); exit; }
$instanceId = $_SESSION["instanceid"] ?? null;
$subscription = null;
if ($instanceId) {
    $stmt = $pdo->prepare("SELECT plan_name, status, started_at, expires_at FROM app_subscriptions WHERE instance_id = ? ORDER BY started_at DESC LIMIT 1");
    $stmt->execute([$instanceId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<?php include __DIR__ . "/partials/layouts/layoutTopnew.php"; ?>
<div class="max-w-2xl mx-auto">
    <div class="mb-6"><h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Current Plan</h1><p class="text-neutral-600 dark:text-neutral-400">View your Squarespace GSC subscription details.</p></div>
    <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
        <?php if (!$subscription): ?>
        <div class="text-center py-8"><p class="text-neutral-500 dark:text-neutral-400">No active subscription found.</p><a href="pricing.php" class="btn btn-cstm-primary mt-4 inline-block px-6 py-2">View Pricing</a></div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div><p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Plan</p><p class="text-xl font-bold text-neutral-800 dark:text-white"><?= htmlspecialchars($subscription["plan_name"] ?? "-") ?></p></div>
            <div><p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Status</p><p class="text-sm <?= ($subscription["status"] ?? "") === "active" ? "text-green-600 dark:text-green-400" : "text-red-600 dark:text-red-400" ?> font-medium"><?= ucfirst(htmlspecialchars($subscription["status"] ?? "unknown")) ?></p></div>
            <div><p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Started At</p><p class="text-neutral-800 dark:text-neutral-200 text-sm"><?= htmlspecialchars($subscription["started_at"] ?? "-") ?></p></div>
            <div><p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Expires At</p><p class="text-neutral-800 dark:text-neutral-200 text-sm"><?= htmlspecialchars($subscription["expires_at"] ?? "-") ?></p></div>
        </div>
        <div class="mt-6 flex justify-end"><a href="dashboard.php" class="btn btn-outline-primary px-6 py-2">Back to Dashboard</a></div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . "/partials/layouts/layoutBottom.php"; ?>

