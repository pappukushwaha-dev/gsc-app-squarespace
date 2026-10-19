<?php
// admin/logout.php — Admin logout
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = [];
session_destroy();

header('Location: sign-in.php');
exit;
