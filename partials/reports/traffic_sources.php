<?php
// partials/reports/traffic_sources.php — Traffic Sources card
?>
<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white">Traffic Sources</h2>
    </div>
    <div class="px-6 py-4">
        <div class="space-y-4">
            <?php
            $sources = [
                ['Google Organic', 0, '0%'],
                ['Google Images', 0, '0%'],
                ['Direct', 0, '0%'],
            ];
            foreach ($sources as [$name, $value, $pct]): ?>
            <div class="flex items-center justify-between">
                <span class="text-sm text-neutral-700 dark:text-neutral-300"><?= htmlspecialchars($name) ?></span>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-neutral-800 dark:text-white"><?= number_format($value) ?></span>
                    <span class="text-xs text-neutral-500 dark:text-neutral-400"><?= $pct ?></span>
                </div>
            </div>
            <div class="h-0.5 bg-neutral-200 dark:bg-neutral-700"></div>
            <?php endforeach; ?>
        </div>
        <?php if (!$googleConnected || $verificationStatus !== 'verified'): ?>
        <p class="mt-4 text-xs text-neutral-500 dark:text-neutral-400 text-center">
            Connect and verify your domain to see traffic sources data.
        </p>
        <?php endif; ?>
    </div>
</div>
