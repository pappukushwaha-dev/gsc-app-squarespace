<?php
// sign-in.php — Sign in page
declare(strict_types=1);

$title = 'Sign In';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/config.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Simple auth check — in production, verify against users table
    if ($email !== '' && $password !== '') {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_email'] = $email;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    <script src="https://unpkg.com/@iconify/iconify"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-neutral-100 dark:bg-neutral-900 text-neutral-800 dark:text-neutral-200 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full px-6 py-12">
        <div class="bg-white dark:bg-neutral-800 rounded-lg shadow-lg border border-neutral-200 dark:border-neutral-700 p-8">
            <div class="text-center mb-8">
                <iconify-icon icon="heroicons:globe-alt-solid" class="text-cstm-primary text-4xl mb-3"></iconify-icon>
                <h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Sign In</h1>
            </div>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-cstm-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Password</label>
                    <input type="password" name="password" required class="w-full px-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-cstm-primary">
                </div>
                <button type="submit" class="w-full py-3 bg-cstm-primary text-white rounded-lg font-semibold hover:bg-blue-600 transition-colors mt-4">
                    Sign In
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-neutral-600 dark:text-neutral-400">
                Don't have an account?
                <a href="setup-wizard.php?step=1" class="text-cstm-primary hover:underline">Get Started</a>
            </p>
        </div>
    </div>
</body>
</html>
