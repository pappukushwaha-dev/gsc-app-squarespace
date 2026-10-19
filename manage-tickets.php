<?php
// manage-tickets.php — Support tickets page
declare(strict_types=1);

$title = 'Support Tickets';

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
        <h1 class="text-2xl font-bold mb-8">Support Tickets</h1>
        <p class="text-neutral-600 dark:text-neutral-400 mb-8">Submit or view your support tickets.</p>

        <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
            <h3 class="font-bold mb-4">Submit New Ticket</h3>
            <form class="space-y-4 max-w-md">
                <div>
                    <label class="block text-sm font-medium mb-1">Subject</label>
                    <input type="text" class="w-full px-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Message</label>
                    <textarea rows="4" class="w-full px-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white"></textarea>
                </div>
                <button type="submit" class="w-full py-2 bg-cstm-primary text-white rounded hover:bg-blue-600">Submit Ticket</button>
            </form>
        </div>
    </main>
</body>
</html>
