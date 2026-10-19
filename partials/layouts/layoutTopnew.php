<?php
// partials/layouts/layoutTopnew.php — Top layout for Squarespace app
// Same pattern as Wix/Shopify partials/layouts/layoutTopnew.php

$title = $title ?? 'Google Search Console — Squarespace';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://unpkg.com/@iconify/iconify"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .text-cstm-primary { color: #3b5bfd; }
        .bg-cstm-primary { background-color: #3b5bfd; }
        .btn-cstm-primary { background-color: #3b5bfd; color: white; }
        .dark { background-color: #111827; color: #f1f5f9; }
        .dark-bg { background-color: #111827; }
        .text-neutral-800 { color: #1f2937; }
        .dark .text-neutral-800 { color: #f1f5f9; }
        .bg-white { background-color: #fff; }
        .dark .bg-white { background-color: #1f2937; }
        .border-neutral-200 { border-color: #e5e7eb; }
        .dark .border-neutral-200 { border-color: #374151; }
        .bg-neutral-800 { background-color: #1f2937; }
        .text-neutral-500 { color: #6b7280; }
        .dark .text-neutral-500 { color: #9ca3af; }
    </style>
</head>
<body class="<?= isset($darkMode) && $darkMode ? 'dark' : '' ?>">
    <header class="border-b border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center gap-3">
                    <iconify-icon icon="heroicons:globe-alt-solid" class="text-cstm-primary text-xl"></iconify-icon>
                    <span class="font-bold text-xl text-neutral-800 dark:text-white">GSC — Squarespace</span>
                </div>
                <div class="flex items-center gap-4">
                    <a href="dashboard.php" class="text-sm font-medium text-neutral-600 dark:text-neutral-300 hover:text-cstm-primary">Dashboard</a>
                    <a href="settings.php" class="text-sm font-medium text-neutral-600 dark:text-neutral-300 hover:text-cstm-primary">Settings</a>
                    <a href="logout.php" class="text-sm font-medium text-red-600 hover:text-red-800">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
            </div>
        <?php endif; ?>
