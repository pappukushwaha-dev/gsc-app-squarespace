<?php
// admin/email-template-add.php — Add Email Template

declare(strict_types=1);

$title = "Add Email Template";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/credentials.php";

if (!isset($_SESSION["user_id"]) && !isset($_SESSION["admin_logged_in"])) {
    header("Location: ../sign-in.php");
    exit;
}

$message = "";
$messageType = "info";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $subject = trim($_POST["subject"] ?? "");
    $category = trim($_POST["category"] ?? "general");
    $body = trim($_POST["body"] ?? "");

    if ($name === "" || $subject === "" || $body === "") {
        $message = "All fields are required.";
        $messageType = "error";
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO email_templates (name, subject, category, body, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$name, $subject, $category, $body]);
        $message = "Email template created successfully.";
        $messageType = "success";
    }
}
?>
<?php include __DIR__ . "/../partials/layouts/layoutTop.php"; ?>

<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-neutral-800 dark:text-white">Add Email Template</h1>
        <p class="text-neutral-600 dark:text-neutral-400">Create a new email template for Squarespace GSC notifications.</p>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> p-4 rounded mb-6"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="bg-white dark:bg-neutral-800 p-6 rounded-lg border border-neutral-200 dark:border-neutral-700">
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Template Name</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-cstm-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Subject</label>
                <input type="text" name="subject" required class="w-full px-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-cstm-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Category</label>
                <select name="category" class="w-full px-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-cstm-primary">
                    <option value="general">General</option>
                    <option value="welcome">Welcome</option>
                    <option value="verification">Verification</option>
                    <option value="billing">Billing</option>
                    <option value="support">Support</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Email Body</label>
                <textarea name="body" rows="10" required class="w-full px-4 py-2 border border-neutral-300 dark:border-neutral-600 rounded dark:bg-neutral-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-cstm-primary"></textarea>
            </div>
            <button type="submit" class="btn btn-cstm-primary px-6 py-2">Save Template</button>
            <a href="email-templates.php" class="btn btn-outline-primary px-6 py-2 ml-2">Cancel</a>
        </form>
    </div>
</div>

<style>
.alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
.alert-success { background: #ecfdf5; border: 1px solid #86efac; color: #15803d; }
.alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }
.alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
.btn-outline-primary { background: #fff; border: 1px solid #3b5bfd; color: #3b5bfd; }
.btn-outline-primary:hover { background: #f0f5ff; }
</style>

<?php include __DIR__ . "/../partials/layouts/layoutBottom.php"; ?>

