<?php
// includes/AuthController.php — Authentication controller
// Same pattern as Wix/Shopify includes/AuthController.php

declare(strict_types=1);

if (!class_exists('AuthController')) {
    class AuthController
    {
        public static function isAuthenticated(): bool
        {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            return !empty($_SESSION['user_id']) || !empty($_SESSION['admin_logged_in']);
        }
    }
}

echo 'auth_controller_loaded';
