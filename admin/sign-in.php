<?php
// admin/sign-in.php — Admin sign in (DB-backed, admin_users table)
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/core/SessionManager.php';
require_once __DIR__ . '/core/AuthController.php';

SessionManager::startDatabaseSession();

if (AuthController::isAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        $result = AuthController::login($email, $password);
        if ($result['success']) {
            header('Location: index.php');
            exit;
        }
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Sign In</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-neutral-100 dark:bg-neutral-900 text-neutral-800 dark:text-neutral-200 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full px-6 py-12">
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow-lg border border-neutral-200 dark:border-neutral-700 p-8">
            <h1 class="text-2xl font-bold mb-8 text-center">Admin Sign In</h1>
            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 border rounded dark:bg-neutral-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 border rounded dark:bg-neutral-700 dark:text-white">
                </div>
                <button type="submit" class="w-full py-3 bg-cstm-primary text-white rounded font-semibold hover:bg-blue-600">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
