<?php
// partials/reports/rich_results_summary.php — Rich Results Summary card
?>
<div class="px-6 py-4">
    <table class="w-full text-left text-sm">
        <thead>
            <tr class="border-b border-neutral-200 dark:border-neutral-700">
                <th class="px-4 py-3 font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Rich Result Type</th>
                <th class="px-4 py-3 font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Impressions</th>
                <th class="px-4 py-3 font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Clicks</th>
                <th class="px-4 py-3 font-semibold text-neutral-500 dark:text-neutral-400 uppercase">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $richResults = [
                ['Breadcrumb', 0, 0, 'Not detected'],
                ['FAQ', 0, 0, 'Not detected'],
                ['Product', 0, 0, 'Not detected'],
                ['Organization', 0, 0, 'Not detected'],
            ];
            foreach ($richResults as [$type, $imp, $clicks, $status]): ?>
            <tr class="border-b border-neutral-100 dark:border-neutral-700">
                <td class="px-4 py-3 text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($type) ?></td>
                <td class="px-4 py-3 text-neutral-800 dark:text-neutral-200"><?= number_format($imp) ?></td>
                <td class="px-4 py-3 text-neutral-800 dark:text-neutral-200"><?= number_format($clicks) ?></td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs
                        <?= $status === 'Detected' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-400' : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400' ?>
                    ">
                        <?= htmlspecialchars($status) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p class="mt-4 text-xs text-neutral-500 dark:text-neutral-400">
        Rich results detected from schema markup on your Squarespace site.
    </p>
</div>
