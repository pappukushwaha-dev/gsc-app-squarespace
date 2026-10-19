<?php
// partials/reports/trends.php — Trends card
?>
<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white">Search Trends</h2>
    </div>
    <div class="px-6 py-4">
        <div class="h-64 bg-neutral-100 dark:bg-neutral-900 rounded-lg flex items-center justify-center">
            <span class="text-neutral-500 dark:text-neutral-400 text-sm">
                Trend chart will appear here once data is collected via cron.<br>
                <span class="block text-xs mt-2">Requires Google connection + verified domain.</span>
            </span>
        </div>
        <?php if (!$googleConnected || $verificationStatus !== 'verified'): ?>
        <p class="mt-4 text-xs text-neutral-500 dark:text-neutral-400 text-center">
            Connect and verify your domain to see search trends.
        </p>
        <?php endif; ?>
    </div>
</div>
