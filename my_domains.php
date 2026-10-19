<?php
// my_domains.php — My domains page
declare(strict_types=1);

$title = 'My Domains';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';

$instanceId = $_SESSION['instanceid'] ?? null;
$domains = [];

if ($instanceId) {
    $stmt = $pdo->prepare("SELECT site_url, site_display_name, verification_status FROM gsc_domain_verifications WHERE instance_id = ?");
    $stmt->execute([$instanceId]);
    $domains = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-neutral-100 dark:bg-neutral-900 text-neutral-800 dark:text-neutral-200 min-h-screen">
    <nav class="border-b border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
        <div class="max-w-7xl mx-auto px-4 h-16 flex justify-between items-center">
            <a href="index.php" class="font-bold text-xl">GSC — Squarespace</a>
            <a href="index.php" class="text-sm hover:text-cstm-primary">Back to Home</a>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-6 py-12">
        <h1 class="text-2xl font-bold mb-8">My Domains</h1>

        <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-neutral-200 dark:border-neutral-700">
                        <th class="px-4 py-3 font-semibold text-neutral-500 dark:text-neutral-400">Domain</th>
                        <th class="px-4 py-3 font-semibold text-neutral-500 dark:text-neutral-400">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($domains)): ?>
                    <tr>
                        <td colspan="2" class="px-4 py-8 text-center text-neutral-500 dark:text-neutral-400">
                            No domains found. <a href="setup-wizard.php?step=1" class="text-cstm-primary hover:underline">Add your first site</a>.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($domains as $domain): ?>
                    <tr class="border-b border-neutral-100 dark:border-neutral-700">
                        <td class="px-4 py-3 text-neutral-800 dark:text-neutral-200"><?= htmlspecialchars($domain['site_url'] ?? '-') ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded-full text-xs
                                <?= $domain['verification_status'] === 'verified' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' ?>
                            ">
                                <?= ucfirst($domain['verification_status'] ?? 'pending') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
