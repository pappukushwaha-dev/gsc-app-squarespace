<?php
// test.php — Test Page
declare(strict_types=1);
$title = "Test";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/credentials.php";
$test = ["php_version" => phpversion(), "pdo_available" => class_exists("PDO") ? "yes" : "no", "google_client_available" => class_exists("Google\Client") ? "yes" : "no", "env_loaded" => defined("GOOGLE_CLIENT_ID") ? "yes" : "no"];
?>
<?php include __DIR__ . "/partials/layouts/layoutTopnew.php"; ?>
<div class="max-w-lg mx-auto">
    <div class="mb-6"><h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Test Page</h1><p class="text-neutral-600 dark:text-neutral-400">Environment check for Squarespace GSC app.</p></div>
    <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
        <table class="w-full text-left"><thead><tr class="border-b border-neutral-200 dark:border-neutral-700"><th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Check</th><th class="px-6 py-3 text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Status</th></tr></thead><tbody>
        <?php foreach ($test as $key => $value): ?><tr class="border-b border-neutral-100 dark:border-neutral-700"><td class="px-6 py-4 text-sm text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($key) ?></td><td class="px-6 py-4 text-sm <?= $value === "yes" ? "text-green-600 dark:text-green-400" : "text-red-600 dark:text-red-400" ?> font-medium"><?= htmlspecialchars($value) ?></td></tr>
        <?php endforeach; ?>
    </tbody></table>
    </div>
</div>
<?php include __DIR__ . "/partials/layouts/layoutBottom.php"; ?>

