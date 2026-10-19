<?php
// admin/partials/head.php — Admin head section
$title = $title ?? 'Admin — Squarespace';
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
        .text-cstm-primary { color: #3b5bfd; }
        .bg-cstm-primary { background-color: #3b5bfd; }
        .dark { background-color: #111827; color: #f1f5f9; }
    </style>
</head>
<body class="dark">
    <nav class="border-b border-neutral-700 bg-neutral-800">
        <div class="max-w-7xl mx-auto px-4 h-16 flex justify-between items-center">
            <a href="index.php" class="font-bold text-xl text-white">GSC Admin — Squarespace</a>
            <div class="flex items-center gap-4">
                <a href="stores.php" class="text-sm text-neutral-300 hover:text-white">Stores</a>
                <a href="tickets.php" class="text-sm text-neutral-300 hover:text-white">Tickets</a>
                <a href="settings.php" class="text-sm text-neutral-300 hover:text-white">Settings</a>
                <a href="logout.php" class="text-sm text-red-400 hover:text-red-300">Logout</a>
            </div>
        </div>
    </nav>
    <main class="max-w-7xl mx-auto px-4 py-6">
