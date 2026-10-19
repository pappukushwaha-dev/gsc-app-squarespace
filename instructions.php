<?php
// instructions.php — Setup instructions page
declare(strict_types=1);

$title = 'Setup Instructions';

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
            <a href="setup-wizard.php?step=1" class="text-sm hover:text-cstm-primary">Start Setup</a>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-6 py-12">
        <h1 class="text-2xl font-bold mb-8">Setup Instructions</h1>

        <ol class="space-y-8">
            <li class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <h3 class="font-bold text-cstm-primary mb-2">Step 1: Enter Site URL</h3>
                <p class="text-neutral-600 dark:text-neutral-400 mb-3">
                    Go to <code class="bg-neutral-100 dark:bg-neutral-900 px-2 py-1 rounded">setup-wizard.php?step=1</code> and enter your Squarespace website URL. We verify it's a Squarespace site and extract your site_id.
                </p>
            </li>

            <li class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <h3 class="font-bold text-cstm-primary mb-2">Step 2: Connect Google</h3>
                <p class="text-neutral-600 dark:text-neutral-400 mb-3">
                    Once your site is verified, click "Continue with Google" to authenticate via OAuth. Your Google access and refresh tokens are stored encrypted.
                </p>
            </li>

            <li class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <h3 class="font-bold text-cstm-primary mb-2">Step 3: Verify Domain</h3>
                <p class="text-neutral-600 dark:text-neutral-400 mb-3">
                    Click "Create Property Automatically" to create a URL-prefix property in Google Search Console, then "Verify Domain Automatically" to verify ownership.
                </p>
            </li>

            <li class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
                <h3 class="font-bold text-cstm-primary mb-2">Step 4: Submit Sitemap</h3>
                <p class="text-neutral-600 dark:text-neutral-400 mb-3">
                    Submit your sitemap URL (usually <code class="bg-neutral-100 dark:bg-neutral-900 px-2 py-1 rounded">https://yoursite.com/sitemap.xml</code>) to start tracking performance.
                </p>
            </li>
        </ol>
    </main>
</body>
</html>
