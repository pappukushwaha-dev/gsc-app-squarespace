<?php
// upgrade.php — Upgrade Plan
declare(strict_types=1);
$title = "Upgrade Plan";
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/includes/config.php";
require_once __DIR__ . "/includes/credentials.php";
if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) { header("Location: sign-in.php"); exit; }
?>
<?php include __DIR__ . "/partials/layouts/layoutTopnew.php"; ?>
<div class="max-w-2xl mx-auto">
    <div class="mb-6"><h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Upgrade Plan</h1><p class="text-neutral-600 dark:text-neutral-400">Upgrade your Squarespace GSC subscription.</p></div>
    <div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="border border-neutral-200 dark:border-neutral-700 rounded-lg p-6"><h3 class="text-lg font-semibold text-neutral-800 dark:text-white">Starter</h3><p class="text-2xl font-bold text-neutral-800 dark:text-white mt-2">$9<span class="text-sm text-neutral-500">/mo</span></p><ul class="mt-4 space-y-2 text-sm text-neutral-600 dark:text-neutral-400"><li>1 Squarespace site</li><li>Basic GSC tracking</li><li>Email support</li></ul><button class="btn btn-outline-primary w-full mt-4 px-6 py-2">Select</button></div>
            <div class="border-2 border-cstm-primary rounded-lg p-6 bg-cstm-primary/5"><h3 class="text-lg font-semibold text-neutral-800 dark:text-white">Pro</h3><p class="text-2xl font-bold text-neutral-800 dark:text-white mt-2">$29<span class="text-sm text-neutral-500">/mo</span></p><ul class="mt-4 space-y-2 text-sm text-neutral-600 dark:text-neutral-400"><li>5 Squarespace sites</li><li>Advanced GSC reports</li><li>Priority support</li></ul><button class="btn btn-cstm-primary w-full mt-4 px-6 py-2">Select</button></div>
            <div class="border border-neutral-200 dark:border-neutral-700 rounded-lg p-6"><h3 class="text-lg font-semibold text-neutral-800 dark:text-white">Enterprise</h3><p class="text-2xl font-bold text-neutral-800 dark:text-white mt-2">$99<span class="text-sm text-neutral-500">/mo</span></p><ul class="mt-4 space-y-2 text-sm text-neutral-600 dark:text-neutral-400"><li>Unlimited sites</li><li>White-label reports</li><li>24/7 dedicated support</li></ul><button class="btn btn-outline-primary w-full mt-4 px-6 py-2">Select</button></div>
        </div>
    </div>
</div>
<?php include __DIR__ . "/partials/layouts/layoutBottom.php"; ?>

