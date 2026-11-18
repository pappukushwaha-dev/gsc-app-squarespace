<?php
// Old Active Stores stub. The sidebar's Active Stores page is stores-active.php.
require_once __DIR__ . '/../includes/config.php';
header('Location: ' . APP_BASE . '/admin/stores-active.php' . (empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']));
exit;
