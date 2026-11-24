<?php
/**
 * Setup wizard step gate (S3-05, 2026-11-24).
 *
 * Returns the first step the store has NOT finished:
 *   1 = Google account not connected
 *   2 = domain not verified
 *   3 = ready for the sitemap step
 * or null when it cannot be determined (DB error) - the caller then leaves
 * the requested step alone rather than bouncing a working store.
 *
 * "Connected" is the exact test step-one.php uses on this platform, so the
 * gate can never disagree with what step 1 shows (no redirect loops).
 */
require_once __DIR__ . '/google/get_account.php';

function gscWizardFirstPendingStep(PDO $pdo, string $storeId): ?int
{
    $storeId = trim($storeId);
    if ($storeId === '') {
        return 1;
    }
    try {
        $acc = getGoogleAccountByShop($storeId);
        $connected = $acc
            && (!empty($acc['access_token']) || !empty($acc['refresh_token']));
        if (!$connected) {
            return 1;
        }
        $st = $pdo->prepare("SELECT verification_status FROM gsc_domain_verifications WHERE shop_id = ? LIMIT 1");
        $st->execute([$storeId]);
        return ($st->fetchColumn() === 'verified') ? 3 : 2;
    } catch (Throwable $e) {
        error_log('[wizard_gate] ' . $e->getMessage());
        return null;
    }
}
