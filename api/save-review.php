<?php
/**
 * Save one review for the review popup (partials/review-popup.php).
 * 2026-11-24: shared logic in /var/www/html/_shared/review_daily.php - every review is a new row,
 * one review per store per 24 hours, status says "reviewed_today" before the merchant picks a score.
 * Backup of the old file: /var/backups/gsc-review-daily-allplatforms-20261124/before.tgz
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once '/var/www/html/_shared/review_daily.php';
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
// sq_auth.php (auto_prepend) already refuses a body naming another store; the store is the session's own.
$body = json_decode((string) file_get_contents('php://input'), true);
$body = is_array($body) ? $body : [];
$storeId = (string) ($_SESSION['shop_id'] ?? '');
$req = gsc_review_requested_id($body, 'shop_id');
if ($storeId === '' || ($req !== '' && $req !== $storeId)) {
    gsc_review_json(['success' => false, 'message' => 'Your session has expired. Please reopen the app and try again.'], 401);
}
if ($storeId === '') {
    gsc_review_json(['success' => false, 'message' => 'Your session has expired. Please reopen the app and try again.'], 401);
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    gsc_review_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}
try {
    $q = $pdo->prepare("SELECT owner_email FROM SquarespaceSite WHERE shop_id = :id AND is_active = 1 LIMIT 1");
    $q->execute([':id' => $storeId]);
    $email = $q->fetchColumn();
    if ($email === false) {
        gsc_review_json(['success' => false, 'message' => 'No site found for this store.'], 404);
    }
    gsc_review_save($pdo, ['key' => 'shop_id', 'store_id' => $storeId, 'email' => $email, 'auto_comment' => 'squarespace review app'], $body);
} catch (Throwable $e) {
    error_log('[save-review] ' . $e->getMessage());
    gsc_review_json(['success' => false, 'message' => 'Could not save your feedback. Please try again.'], 500);
}
