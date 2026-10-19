<?php
// wizard-instructions.php — Setup Wizard Instructions
declare(strict_types=1);
$title = "Setup Instructions";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/credentials.php";
?>
<?php include __DIR__ . "/partials/layouts/layoutTopnew.php"; ?>
<div class="max-w-3xl mx-auto">
    <div class="mb-6"><h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Setup Instructions</h1><p class="text-neutral-600 dark:text-neutral-400">Step-by-step guide to connect your Squarespace site to Google Search Console.</p></div>
    <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
        <div class="space-y-6">
            <div><h2 class="text-lg font-semibold text-neutral-800 dark:text-white mb-2">Step 1: Verify Your Squarespace Site</h2><ol class="list-decimal list-inside space-y-2 text-neutral-700 dark:text-neutral-300"><li>Enter your Squarespace website URL.</li><li>We verify it is a Squarespace site by checking the page HTML.</li><li>Your site_id is saved as your instance ID.</li></ol></div>
            <div><h2 class="text-lg font-semibold text-neutral-800 dark:text-white mb-2">Step 2: Connect with Google</h2><ol class="list-decimal list-inside space-y-2 text-neutral-700 dark:text-neutral-300"><li>Click "Connect with Google" to start OAuth.</li><li>Authorize Google Search Console access.</li><li>We save your access and refresh tokens.</li></ol></div>
            <div><h2 class="text-lg font-semibold text-neutral-800 dark:text-white mb-2">Step 3: Verify Domain</h2><ol class="list-decimal list-inside space-y-2 text-neutral-700 dark:text-neutral-300"><li>Create a URL-prefix property in GSC.</li><li>Copy the verification meta tag.</li><li>Add it to Squarespace: Settings → Advanced → Code Injection → Header.</li><li>Click "Verify Domain Automatically".</li></ol></div>
            <div><h2 class="text-lg font-semibold text-neutral-800 dark:text-white mb-2">Step 4: Submit Sitemap</h2><ol class="list-decimal list-inside space-y-2 text-neutral-700 dark:text-neutral-300"><li>Default sitemap: yoursite.com/sitemap.xml</li><li>Submit via the dashboard.</li><li>Start tracking clicks and impressions.</li></ol></div>
        </div>
    </div>
</div>
<?php include __DIR__ . "/partials/layouts/layoutBottom.php"; ?>

