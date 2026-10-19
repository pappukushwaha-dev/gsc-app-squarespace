<?php
// faq.php — FAQ page
declare(strict_types=1);

$title = 'FAQ';

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

    <main class="max-w-3xl mx-auto px-6 py-12">
        <h1 class="text-2xl font-bold mb-8">Frequently Asked Questions</h1>

        <div class="space-y-6">
            <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <h3 class="font-bold mb-2">How do I connect my Squarespace site?</h3>
                <p class="text-neutral-600 dark:text-neutral-400">
                    Enter your Squarespace site URL in the setup wizard. We verify it's a Squarespace site, then you connect your Google account to link Search Console.
                </p>
            </div>

            <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <h3 class="font-bold mb-2">What is site_id?</h3>
                <p class="text-neutral-600 dark:text-neutral-400">
                    Your Squarespace site_id acts as your instance ID across the dashboard — just like Wix, Shopify, and other platforms.
                </p>
            </div>

            <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <h3 class="font-bold mb-2">Do I need to add meta tag manually?</h3>
                <p class="text-neutral-600 dark:text-neutral-400">
                    You can auto-verify via our "Verify Domain Automatically" button, or manually add the Google verification meta tag in Squarespace: Settings → Advanced → Code Injection → Header.
                </p>
            </div>

            <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <h3 class="font-bold mb-2">Is Stripe payment required?</h3>
                <p class="text-neutral-600 dark:text-neutral-400">
                    Stripe is used for subscription payments. You can configure Stripe keys in Admin → Settings → Stripe tab.
                </p>
            </div>
        </div>
    </main>
</body>
</html>
