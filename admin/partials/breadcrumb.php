<?php
// admin/partials/breadcrumb.php — Breadcrumb navigation
?>
<nav class="flex items-center space-x-2 text-sm text-neutral-500 dark:text-neutral-400 mb-4">
    <a href="index.php" class="hover:text-cstm-primary">Dashboard</a>
    <span>/</span>
    <span><?= $breadcrumbTitle ?? 'Page' ?></span>
</nav>
