<?php
// pricing.php — Pricing page
declare(strict_types=1);

$title = 'Pricing';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';
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

    <main class="max-w-6xl mx-auto px-6 py-12">
        <h1 class="text-2xl font-bold mb-8">Pricing Plans</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <h3 class="font-bold text-lg">Free</h3>
                <p class="text-2xl font-bold text-cstm-primary">$0</p>
                <p class="text-neutral-600 dark:text-neutral-400">/month</p>
                <ul class="mt-6 space-y-2 text-sm text-neutral-600 dark:text-neutral-400">
                    <li>1 Squarespace site</li>
                    <li>Basic GSC reports</li>
                    <li>7-day data retention</li>
                </ul>
                <button class="mt-6 w-full py-2 border border-cstm-primary text-cstm-primary rounded hover:bg-cstm-primary hover:text-white">Get Started</button>
            </div>

            <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border-2 border-cstm-primary relative">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-cstm-primary text-white px-2 py-1 rounded-full text-xs">Popular</div>
                <h3 class="font-bold text-lg">Pro</h3>
                <p class="text-2xl font-bold text-cstm-primary">$19</p>
                <p class="text-neutral-600 dark:text-neutral-400">/month</p>
                <ul class="mt-6 space-y-2 text-sm text-neutral-600 dark:text-neutral-400">
                    <li>5 Squarespace sites</li>
                    <li>Advanced GSC reports</li>
                    <li>90-day data retention</li>
                    <li>Weekly digest emails</li>
                </ul>
                <button class="mt-6 w-full py-2 bg-cstm-primary text-white rounded hover:bg-blue-600">Get Started</button>
            </div>

            <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <h3 class="font-bold text-lg">Agency</h3>
                <p class="text-2xl font-bold text-cstm-primary">$99</p>
                <p class="text-neutral-600 dark:text-neutral-400">/month</p>
                <ul class="mt-6 space-y-2 text-sm text-neutral-600 dark:text-neutral-400">
                    <li>Unlimited sites</li>
                    <li>All features</li>
                    <li>1-year data retention</li>
                    <li>White-label reports</li>
                </ul>
                <button class="mt-6 w-full py-2 border border-cstm-primary text-cstm-primary rounded hover:bg-cstm-primary hover:text-white">Contact Sales</button>
            </div>
        </div>
    </main>
</body>
</html>
