<?php
// partials/reports/top_pages.php — Top Pages card
?>
<div class="bg-white dark:bg-neutral-800 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700">
        <h2 class="text-lg font-semibold text-neutral-800 dark:text-white">Top Pages</h2>
    </div>
    <div class="px-6 py-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-neutral-200 dark:border-neutral-700">
                        <th class="px-4 py-3 font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Page</th>
                        <th class="px-4 py-3 font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Clicks</th>
                        <th class="px-4 py-3 font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Impressions</th>
                        <th class="px-4 py-3 font-semibold text-neutral-500 dark:text-neutral-400 uppercase">CTR</th>
                        <th class="px-4 py-3 font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Position</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($googleConnected && $verificationStatus === 'verified'): ?>
                    <?php
                    // Placeholder — real data comes from GSC API via cron
                    $topPages = [
                        ['/' => ['clicks' => 0, 'impressions' => 0, 'ctr' => '0.00', 'position' => '0.00']],
                        ['/about' => ['clicks' => 0, 'impressions' => 0, 'ctr' => '0.00', 'position' => '0.00']],
                        ['/contact' => ['clicks' => 0, 'impressions' => 0, 'ctr' => '0.00', 'position' => '0.00']],
                    ];
                    foreach ($topPages as $page => $data): ?>
                    <tr class="border-b border-neutral-100 dark:border-neutral-700">
                        <td class="px-4 py-3 text-neutral-800 dark:text-neutral-200 font-mono"><?= htmlspecialchars($page) ?></td>
                        <td class="px-4 py-3 text-neutral-800 dark:text-neutral-200"><?= number_format($data['clicks']) ?></td>
                        <td class="px-4 py-3 text-neutral-800 dark:text-neutral-200"><?= number_format($data['impressions']) ?></td>
                        <td class="px-4 py-3 text-neutral-800 dark:text-neutral-200"><?= $data['ctr'] ?>%</td>
                        <td class="px-4 py-3 text-neutral-800 dark:text-neutral-200"><?= $data['position'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-neutral-500 dark:text-neutral-400">
                            Connect and verify your domain to see top pages data.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
