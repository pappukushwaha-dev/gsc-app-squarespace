<?php
// rich-results-report.php — Rich Results Report
declare(strict_types=1);
$title = "Rich Results Report";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/credentials.php";
if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) { header("Location: sign-in.php"); exit; }
?>
<?php include __DIR__ . "/partials/layouts/layoutTopnew.php"; ?>
<div class="mb-6"><h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Rich Results Report</h1><p class="text-neutral-600 dark:text-neutral-400">View rich results performance for your Squarespace site.</p></div>
<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
    <div class="alert alert-info p-4 rounded mb-6">Rich results data will appear here once your site is verified in Google Search Console.</div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-neutral-50 dark:bg-neutral-700/50 p-4 rounded"><p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Rich Results</p><p class="text-2xl font-bold text-neutral-800 dark:text-white">0</p></div>
        <div class="bg-neutral-50 dark:bg-neutral-700/50 p-4 rounded"><p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Impressions</p><p class="text-2xl font-bold text-neutral-800 dark:text-white">0</p></div>
        <div class="bg-neutral-50 dark:bg-neutral-700/50 p-4 rounded"><p class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">CTR</p><p class="text-2xl font-bold text-neutral-800 dark:text-white">0.00%</p></div>
    </div>
</div>
<?php include __DIR__ . "/partials/layouts/layoutBottom.php"; ?>

