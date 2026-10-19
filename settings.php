<?php
// settings.php — Settings page (redirects to admin/settings.php)
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php');
    exit;
}

header('Location: admin/settings.php');
exit;
