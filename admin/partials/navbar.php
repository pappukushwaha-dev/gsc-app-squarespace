<?php
// admin/partials/navbar.php — Admin navbar
?>
<nav class="border-b border-neutral-700 bg-neutral-800 sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4 h-16 flex justify-between items-center">
        <div class="flex items-center gap-2">
            <iconify-icon icon="heroicons:globe-alt-solid" class="text-cstm-primary text-xl"></iconify-icon>
            <span class="font-bold text-xl text-white">GSC Admin</span>
        </div>
        <div class="flex items-center gap-4">
            <a href="stores.php" class="text-sm text-neutral-300 hover:text-white">Stores</a>
            <a href="tickets.php" class="text-sm text-neutral-300 hover:text-white">Tickets</a>
            <a href="settings.php" class="text-sm text-neutral-300 hover:text-white">Settings</a>
            <a href="logout.php" class="text-sm text-red-400 hover:text-red-300">Logout</a>
        </div>
    </div>
</nav>
