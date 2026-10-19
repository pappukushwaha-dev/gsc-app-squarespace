<?php
// partials/head.php — Common head section
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
        .text-cstm-primary { color: #3b5bfd; }
        .bg-cstm-primary { background-color: #3b5bfd; }
        .btn-cstm-primary { background-color: #3b5bfd; color: white; }
        .dark { background-color: #111827; color: #f1f5f9; }
    </style>
</head>
<body class="<?= isset($darkMode) && $darkMode ? 'dark' : '' ?>">
