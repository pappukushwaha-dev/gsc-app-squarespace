<?php
// admin/other-app-tracking.php — Other App Tracking

declare(strict_types=1);

$title = "Other App Tracking";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/credentials.php";

if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) {
    header("Location: ../sign-in.php");
    exit;
}

$clicks = [];
$stmt = $pdo->prepare("
    SELECT id, platform, click_count, created_at
    FROM other_app_clicks
    ORDER BY created_at DESC
    LIMIT 100
");
$stmt->execute();
$clicks = $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
?>
<?php include __DIR__ . "/../partials/layouts/layoutTop.php"; ?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Other App Tracking</h1>
    <p class="text-neutral-600 dark:text-neutral-400">Track clicks on other platform apps.</p>
</div>

<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white">App Clicks</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-neutral-200 dark:border-neutral-700">
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Platform</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Click Count</th>
                    <th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clicks)): ?>
                <tr>
                    <td colspan="3" class="px-6 py-8 text-center text-neutral-500 dark:text-neutral-400">No clicks recorded.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($clicks as $click): ?>
                <tr class="border-b border-neutral-100 dark:border-neutral-700">
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($click["platform"] ?? "") ?></td>
                    <td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200 font-medium"><?= (int)($click["click_count"] ?? 0) ?></td>
                    <td class="px-6 py-4 text-sm text-neutral-600 dark:text-neutral-400"><?= htmlspecialchars($click["created_at"] ?? "") ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . "/../partials/layouts/layoutBottom.php"; ?>

