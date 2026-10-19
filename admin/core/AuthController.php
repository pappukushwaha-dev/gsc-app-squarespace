<?php
// admin/core/AuthController.php — Auth controller for admin
declare(strict_types=1);

require_once __DIR__ . '/SessionManager.php';

class AdminAuthController
{
    public static function isAuthenticated(): bool
    {
        return AdminSessionManager::isAdminLoggedIn();
    }

    public static function requireAuth(): void
    {
        if (!self::isAuthenticated()) {
            header('Location: sign-in.php');
            exit;
        }
    }
}
