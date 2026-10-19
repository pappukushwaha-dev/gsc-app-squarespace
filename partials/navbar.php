<?php
// partials/navbar.php — Navigation bar
?>
<nav class="border-b border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center gap-2">
                <iconify-icon icon="heroicons:globe-alt-solid" class="text-cstm-primary text-xl"></iconify-icon>
                <span class="font-bold text-xl">GSC — Squarespace</span>
            </div>
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="text-sm font-medium hover:text-cstm-primary">Dashboard</a>
                <a href="settings.php" class="text-sm font-medium hover:text-cstm-primary">Settings</a>
                <a href="logout.php" class="text-sm font-medium text-red-600 hover:text-red-800">Logout</a>
            </div>
        </div>
    </div>
</nav>
