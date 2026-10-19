<?php
// thank-you.php — Thank You Page
declare(strict_types=1);
$title = "Thank You";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/credentials.php";
?>
<?php include __DIR__ . "/partials/layouts/layoutTopnew.php"; ?>
<div class="max-w-md mx-auto py-12">
    <div class="text-center">
        <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-full flex items-center justify-center mx-auto mb-6"><iconify-icon icon="heroicons:check" class="w-8 h-8"></iconify-icon></div>
        <h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Thank You!</h1>
        <p class="text-neutral-600 dark:text-neutral-400 mt-2">Your Squarespace site is now connected to Google Search Console.</p>
    </div>
    <div class="mt-8 text-center"><a href="dashboard.php" class="btn btn-cstm-primary px-8 py-2">Go to Dashboard</a></div>
</div>
<?php include __DIR__ . "/partials/layouts/layoutBottom.php"; ?>

