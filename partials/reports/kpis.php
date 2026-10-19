<?php
// partials/reports/kpis.php — Key Performance Indicators card
?>
<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white">Key Metrics</h2>
    </div>
    <div class="px-6 py-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                <div class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Clicks (7 days)</div>
                <div class="text-2xl font-bold text-neutral-800 dark:text-white mt-1"><?= number_format($stats['total_clicks']) ?></div>
            </div>
            <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                <div class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Impressions (7 days)</div>
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1"><?= number_format($stats['total_impressions']) ?></div>
            </div>
            <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                <div class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Avg CTR</div>
                <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1"><?= $stats['avg_ctr'] ?>%</div>
            </div>
            <div class="p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
                <div class="text-xs font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Avg Position</div>
                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1"><?= $stats['avg_position'] ?></div>
            </div>
        </div>
    </div>
</div>
