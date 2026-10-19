<?php
// admin/core/AuthController.php — Admin authentication (DB-backed)
// AuthController is what the ported pages call; AdminAuthController is
// this app's original alias — both share the same session.

declare(strict_types=1);

require_once __DIR__ . '/SessionManager.php';

class AuthController
{
    /** Attempts to log in an admin user (email + password against admin_users). */
    public static function login(string $email, string $password): array
    {
        global $pdo;

        $query = "SELECT id, username, password FROM admin_users WHERE email = ? AND status = 'active'";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")
                ->execute([$user['id']]);

            SessionManager::createAdminSession((int) $user['id'], (string) $user['username']);
            return ['success' => true, 'message' => 'Login successful. Redirecting...'];
        }

        error_log("Failed admin login attempt for email: {$email}");
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }

    public static function logout(): void
    {
        SessionManager::destroyAdminSession();
    }

    public static function isAuthenticated(): bool
    {
        return SessionManager::isAdminLoggedIn();
    }
}

/** Legacy alias — the earlier pages call this class name. */
if (!class_exists('AdminAuthController')) {
    class AdminAuthController
    {
        public static function isAuthenticated(): bool
        {
            return AuthController::isAuthenticated();
        }

        public static function requireAuth(): void
        {
            if (!self::isAuthenticated()) {
                header('Location: sign-in.php');
                exit;
            }
        }
    }
}
