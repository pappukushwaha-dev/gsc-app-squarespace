<?php
// regex-report.php — Regex Report
declare(strict_types=1);
$title = "Regex Report";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/credentials.php";
if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) { header("Location: sign-in.php"); exit; }
?>
<?php include __DIR__ . "/partials/layouts/layoutTopnew.php"; ?>
<div class="mb-6"><h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Regex Report</h1><p class="text-neutral-600 dark:text-neutral-400">Filter GSC data using regex patterns.</p></div>
<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
    <form method="GET" class="space-y-4">
        <div><label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Regex Pattern</label><input type="text" name="pattern" placeholder="e.g. ^how to" class="w-full px-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-cstm-primary"></div>
        <button type="submit" class="btn btn-cstm-primary px-6 py-2">Apply Filter</button>
    </form>
    <div class="mt-6 alert alert-info p-4 rounded">Regex filtered results will appear here.</div>
</div>
<?php include __DIR__ . "/partials/layouts/layoutBottom.php"; ?>

