<?php
// sitemap.php — Sitemap submit page
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['shop_id'])) {
    header('Location: setup-wizard.php?step=1');
    exit;
}

header('Location: setup-wizard.php?step=3');
exit;
