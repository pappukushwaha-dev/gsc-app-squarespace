<?php
// mcp-settings.php — MCP settings page
declare(strict_types=1);

$title = 'MCP Settings';

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

    <main class="max-w-4xl mx-auto px-6 py-12">
        <h1 class="text-2xl font-bold mb-8">MCP Settings</h1>
        <p class="text-neutral-600 dark:text-neutral-400 mb-8">Configure Model Context Protocol integration.</p>

        <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
            <h3 class="font-bold mb-4">Available Tools</h3>
            <ul class="space-y-2 text-sm text-neutral-600 dark:text-neutral-400">
                <li>Search Analytics</li>
                <li>List Properties</li>
                <li>List Sitemaps</li>
                <li>Growth Opportunities</li>
                <li>Find Cannibalization</li>
            </ul>
        </div>
    </main>
</body>
</html>
