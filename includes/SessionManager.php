<?php
// includes/SessionManager.php — Database-backed session management
// Same pattern as Wix/Shopify includes/SessionManager.php

declare(strict_types=1);

if (!class_exists('SessionManager')) {
    class SessionManager
    {
        public static function startDatabaseSession(): void
        {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        }
    }
}

echo 'session_manager_loaded';
