<?php
// verify_domain.php — the domain-verification UI lives in the setup
// wizard (step 2: verify meta tag -> Verify Domain). Any traffic to
// this legacy path is forwarded there.

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$shopId = (string) ($_GET['shop_id'] ?? $_SESSION['shop_id'] ?? '');

header('Location: ' . APP_BASE . '/setup-wizard.php?shop_id=' . urlencode($shopId) . '&step=2');
exit;
