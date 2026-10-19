<?php
// why-first-report.php — Why First Report
declare(strict_types=1);
$title = "Why First Report";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/credentials.php";
?>
<?php include __DIR__ . "/partials/layouts/layoutTopnew.php"; ?>
<div class="max-w-2xl mx-auto">
    <div class="mb-6"><h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Why Your First Report Matters</h1><p class="text-neutral-600 dark:text-neutral-400">Understand the importance of your first Google Search Console report for Squarespace.</p></div>
    <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
        <ul class="list-disc list-inside space-y-3 text-neutral-700 dark:text-neutral-300">
            <li>Establishes your baseline SEO performance.</li>
            <li>Shows which queries bring traffic to your Squarespace site.</li>
            <li>Identifies indexing issues early.</li>
            <li>Helps track improvements after optimization.</li>
        </ul>
    </div>
</div>
<?php include __DIR__ . "/partials/layouts/layoutBottom.php"; ?>

