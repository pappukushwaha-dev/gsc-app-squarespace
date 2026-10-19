<?php
// includes/SessionManager.php — Database-backed session management
// Family (ecwid) version, plus an AdminSessionManager alias so the
// admin pages can call either class name.

declare(strict_types=1);

if (!class_exists('SessionManager')) {
    class SessionManager
    {
        /** Configures PHP to use the database for session storage. */
        public static function startDatabaseSession(): void
        {
            global $pdo;

            if (session_status() === PHP_SESSION_NONE) {
                session_set_save_handler(
                    function ($path, $name) { return true; }, // open
                    function () { return true; },             // close
                    function ($id) use ($pdo) {               // read
                        $query = "SELECT data FROM sessions WHERE id = ? AND timestamp > ?";
                        $stmt = $pdo->prepare($query);
                        $stmt->execute([$id, time() - (int) ini_get('session.gc_maxlifetime')]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        return $row ? (string) $row['data'] : '';
                    },
                    function ($id, $data) use ($pdo) {        // write
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                        $timestamp = time();
                        $query = "INSERT INTO sessions (id, ip_address, user_agent, data, timestamp)
                                  VALUES (?, ?, ?, ?, ?)
                                  ON DUPLICATE KEY UPDATE ip_address = ?, user_agent = ?, data = ?, timestamp = ?";
                        $stmt = $pdo->prepare($query);
                        return $stmt->execute([
                            $id, $ip_address, $user_agent, $data, $timestamp,
                            $ip_address, $user_agent, $data, $timestamp,
                        ]);
                    },
                    function ($id) use ($pdo) {               // destroy
                        $pdo->prepare("DELETE FROM sessions WHERE id = ?")->execute([$id]);
                        return true;
                    },
                    function ($lifetime) use ($pdo) {         // gc
                        $pdo->prepare("DELETE FROM sessions WHERE timestamp < ?")
                            ->execute([time() - (int) $lifetime]);
                        return true;
                    }
                );

                session_start();
            }
        }

        /** Creates a new admin session after successful authentication. */
        public static function createAdminSession(int $adminId, string $username): void
        {
            session_regenerate_id(true); // prevent session fixation
            $_SESSION['admin_logged_in']   = true;
            $_SESSION['admin_id']          = $adminId;
            $_SESSION['admin_username']    = $username;
            $_SESSION['session_start_time'] = time();
            $_SESSION['last_activity']     = time();
        }

        /** Destroys the current admin session and logs the user out. */
        public static function destroyAdminSession(): void
        {
            global $pdo;

            $sessionId = session_id();
            $_SESSION = [];

            if ($sessionId && $pdo) {
                try {
                    $pdo->prepare("DELETE FROM sessions WHERE id = ?")->execute([$sessionId]);
                } catch (Throwable $e) {
                    error_log("Failed to delete session from database: " . $e->getMessage());
                }
            }

            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);

            session_destroy();

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION = [];
        }

        /** Checks if an admin is logged in, with a 30-minute inactivity cap. */
        public static function isAdminLoggedIn(): bool
        {
            if (session_status() === PHP_SESSION_NONE) {
                self::startDatabaseSession();
            }

            if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
                return false;
            }

            $max_lifetime = 1800; // 30 minutes
            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $max_lifetime)) {
                self::destroyAdminSession();
                return false;
            }

            $_SESSION['last_activity'] = time();
            return true;
        }
    }

    /** Legacy alias — the admin pages call either class name. */
    if (!class_exists('AdminSessionManager')) {
        class AdminSessionManager extends SessionManager
        {
        }
    }
}
