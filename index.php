<?php
// index.php — Squarespace GSC App landing
declare(strict_types=1);

$title = 'Google Search Console — Squarespace';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/credentials.php';

$googleConnected = isset($_SESSION['google_connected']) && $_SESSION['google_connected'] === true;
$shopId = $_SESSION['shop_id'] ?? null;

$siteUrl = null;
if ($shopId) {
    $stmt = $pdo->prepare("SELECT site_url, site_display_name FROM SquarespaceSite WHERE shop_id = ? LIMIT 1");
    $stmt->execute([$shopId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $siteUrl = $row['site_url'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://unpkg.com/@iconify/iconify"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-neutral-100 dark:bg-neutral-900 text-neutral-800 dark:text-neutral-200 min-h-screen flex flex-col">
    <nav class="border-b border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-2">
                    <iconify-icon icon="heroicons:globe-alt-solid" class="text-cstm-primary text-xl"></iconify-icon>
                    <span class="font-bold text-xl">GSC — Squarespace</span>
                </div>
                <div class="flex items-center gap-4">
                    <a href="setup-wizard.php?step=1" class="text-sm font-medium hover:text-cstm-primary">Setup</a>
                    <a href="dashboard.php" class="text-sm font-medium hover:text-cstm-primary">Dashboard</a>
                    <a href="settings.php" class="text-sm font-medium hover:text-cstm-primary">Settings</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-1 max-w-7xl mx-auto px-4 py-12">
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-4">Google Search Console for Squarespace</h1>
            <p class="text-lg text-neutral-600 dark:text-neutral-400 mb-8">
                Connect your Squarespace site to Google Search Console and track search performance.
            </p>

            <?php if (!$googleConnected): ?>
            <a href="setup-wizard.php?step=1" class="px-8 py-3 bg-cstm-primary text-white rounded-lg font-semibold text-lg hover:bg-blue-600 transition-colors">
                Connect Your Squarespace Site
            </a>
            <?php else: ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-6 py-4 rounded-lg inline-block">
                <p class="font-bold">Connected ✅</p>
                <p class="text-sm">Site: <?= htmlspecialchars($siteUrl ?? 'Not set') ?></p>
                <a href="dashboard.php" class="mt-2 text-cstm-primary hover:underline">Go to Dashboard</a>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="border-t border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800 py-6 mt-auto">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-sm text-neutral-500 dark:text-neutral-400">&copy; 2026 GSC Squarespace App</p>
        </div>
    </footer>
</body>
</html>
