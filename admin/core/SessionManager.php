<?php
// admin/core/SessionManager.php — Session manager for admin
declare(strict_types=1);

require_once __DIR__ . '/../../includes/SessionManager.php';

class AdminSessionManager
{
    public static function isAdminLoggedIn(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return !empty($_SESSION['admin_logged_in']) || !empty($_SESSION['user_id']);
    }

    public static function login(int $userId): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['user_id'] = $userId;
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        session_destroy();
    }
}
