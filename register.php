<?php

declare(strict_types=1);

/**
 * register.php — public registration (wizard step-1 design).
 *
 * The visitor submits email + Squarespace site URL. We fetch the site,
 * verify it runs on Squarespace, create the instance and email a
 * single-use magic link that signs them into the dashboard.
 */

declare(strict_types=1);

$title       = 'Connect your Squarespace site';
$subTitle    = 'Register';
$publicPage  = true; // guests must reach this form without a session

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/account_provision.php';

$errorMessage = '';
$successEmail = '';
$buttonLabel  = 'Verify & Create my dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = strtolower(trim((string) ($_POST['email'] ?? '')));
    $website = trim((string) ($_POST['website'] ?? ''));

    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new AccountProvisionException('Please enter a valid email address');
        }

        $domain   = normalizeDomain($website);
        $verified = squarespaceVerifySite($domain);
        $res      = provisionInstance($pdo, $email, $domain, $verified);

        $token = createMagicToken($pdo, $email, $res['instance_id']);
        if (!sendMagicLink($email, $token)) {
            throw new AccountProvisionException('We could not send the sign-in email right now. Please try again in a minute.');
        }

        $successEmail = $email;
    } catch (AccountProvisionException $e) {
        $errorMessage = $e->getMessage();
    } catch (Throwable $e) {
        error_log('register: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
        $errorMessage = 'Something went wrong on our side. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connect your Squarespace site — Google Search Console</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://unpkg.com/iconify-icon@2/dist/iconify-icon.min.js"></script>
    <style>
        :root {
            --s1-ink:   #0f172a;
            --s1-mute:  #64748b;
            --s1-line:  #e2e8f0;
            --s1-blue:  #3b5bfd;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background:
                radial-gradient(900px 500px at 85% -10%, rgba(59, 91, 253, .10), transparent 60%),
                radial-gradient(700px 420px at -10% 110%, rgba(124, 58, 237, .08), transparent 60%),
                #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 16px;
        }

        .rg-card {
            width: 100%;
            max-width: 940px;
            display: grid;
            grid-template-columns: minmax(0, .82fr) minmax(0, 1.18fr);
            border-radius: 20px;
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--s1-line);
            box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 12px 36px rgba(15, 23, 42, .08);
        }

        /* ---------------- left: live illustration ---------------- */
        .rg-art {
            position: relative;
            overflow: hidden;
            background: linear-gradient(160deg, #f4f7ff 0%, #eef2ff 55%, #f7f5ff 100%);
            border-right: 1px solid var(--s1-line);
            padding: 24px 22px 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 460px;
        }

        .rg-stage {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .rg-ring {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(59, 91, 253, .16);
        }

        .rg-ring.r1 { width: 150px; height: 150px; }
        .rg-ring.r2 { width: 220px; height: 220px; opacity: .6; }
        .rg-ring.r3 { width: 290px; height: 290px; opacity: .3; }

        .rg-orbit {
            position: absolute;
            width: 290px;
            height: 290px;
            border-radius: 50%;
            animation: rgSpin 14s linear infinite;
        }

        .rg-orbit i {
            position: absolute;
            top: -5px;
            left: 50%;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--s1-blue);
            box-shadow: 0 0 0 4px rgba(59, 91, 253, .18);
        }

        @keyframes rgSpin { to { transform: rotate(360deg); } }

        .rg-mark {
            position: relative;
            width: 74px;
            height: 74px;
            border-radius: 50%;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(15, 23, 42, .1), 0 12px 36px rgba(59, 91, 253, .14);
        }

        .rg-mark iconify-icon { font-size: 38px; color: #111827; }

        .rg-chip {
            position: absolute;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, .06);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .07);
        }

        .rg-chip iconify-icon { font-size: 17px; }

        .rg-chip.c1 { top: 10%; left: 8%; }
        .rg-chip.c2 { top: 14%; right: 6%; }
        .rg-chip.c3 { bottom: 18%; left: 4%; }
        .rg-chip.c4 { bottom: 22%; right: 8%; }

        .rg-spark {
            position: absolute;
            color: #c7d2fe;
            font-size: 12px;
        }

        .rg-spark.s1 { top: 24%; left: 24%; }
        .rg-spark.s2 { top: 44%; right: 20%; font-size: 9px; }
        .rg-spark.s3 { bottom: 30%; left: 18%; }

        .rg-trust {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            padding: 12px 14px;
            border-radius: 12px;
            background: rgba(255, 255, 255, .72);
            border: 1px solid rgba(15, 23, 42, .05);
        }

        .rg-trust-ic {
            flex: none;
            width: 32px;
            height: 32px;
            border-radius: 9px;
            background: #eef2ff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .rg-trust h5 { margin: 0 0 2px; font-size: 12.5px; color: var(--s1-blue); }
        .rg-trust p  { margin: 0; font-size: 11.5px; line-height: 1.5; color: var(--s1-mute); }

        /* ---------------- right: form ---------------- */
        .rg-body { padding: 34px 36px; display: flex; flex-direction: column; }

        .rg-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            align-self: flex-start;
            padding: 5px 12px;
            border-radius: 999px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #059669;
            font-size: 11.5px;
            font-weight: 700;
        }

        .rg-h1 { margin: 14px 0 0; font-size: 26px; font-weight: 800; color: var(--s1-ink); letter-spacing: -.02em; }
        .rg-h1 span { color: var(--s1-blue); }
        .rg-lead { margin: 8px 0 0; font-size: 13.5px; line-height: 1.6; color: var(--s1-mute); }

        .rg-field { margin-top: 18px; }
        .rg-label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; letter-spacing: .01em; }
        .rg-input {
            width: 100%;
            height: 46px;
            padding: 0 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            color: var(--s1-ink);
            background: #fff;
            transition: border-color .15s, box-shadow .15s;
        }
        .rg-input:focus { outline: none; border-color: var(--s1-blue); box-shadow: 0 0 0 3px rgba(59, 91, 253, .14); }

        .rg-cta {
            margin-top: 22px;
            width: 100%;
            height: 50px;
            border: 0;
            border-radius: 12px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(100deg, #3b5bfd 0%, #4f46e5 55%, #7c3aed 100%);
            background-size: 200% 100%;
            box-shadow: 0 4px 14px rgba(59, 91, 253, .32);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: transform .15s, box-shadow .15s, filter .15s;
        }
        .rg-cta:hover { transform: translateY(-1px); filter: brightness(1.06); box-shadow: 0 8px 22px rgba(59, 91, 253, .38); }
        .rg-cta:disabled { opacity: .7; cursor: wait; transform: none; }
        .rg-cta .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, .4);
            border-top-color: #fff;
            border-radius: 50%;
            animation: rgSpin .7s linear infinite;
        }
        .rg-cta.loading .spinner { display: inline-block; }

        .rg-secure {
            margin-top: 14px;
            display: flex;
            gap: 8px;
            align-items: center;
            justify-content: center;
            font-size: 11.5px;
            color: #94a3b8;
        }

        .rg-alert {
            margin-top: 16px;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
        }
        .rg-alert.err { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; }

        .rg-success { text-align: center; padding: 18px 4px; }
        .rg-success .ic {
            width: 64px; height: 64px; margin: 0 auto 14px; border-radius: 50%;
            background: #ecfdf5; border: 1px solid #a7f3d0;
            display: flex; align-items: center; justify-content: center;
        }
        .rg-success .ic iconify-icon { font-size: 32px; color: #059669; }
        .rg-success h2 { font-size: 20px; font-weight: 800; color: var(--s1-ink); margin: 0; }
        .rg-success p { font-size: 13.5px; color: var(--s1-mute); line-height: 1.6; margin: 10px 0 0; }
        .rg-success strong { color: var(--s1-ink); }

        .rg-foot {
            margin-top: 18px;
            text-align: center;
            font-size: 12.5px;
            color: var(--s1-mute);
        }
        .rg-foot a { color: var(--s1-blue); font-weight: 600; text-decoration: none; }
        .rg-foot a:hover { text-decoration: underline; }

        @keyframes rgSpin { to { transform: rotate(360deg); } }

        @media (max-width: 860px) {
            .rg-card { grid-template-columns: 1fr; }
            .rg-art { display: none; }
            .rg-body { padding: 26px 22px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .rg-orbit, .rg-cta .spinner { animation: none !important; }
            .rg-cta, .rg-cta:hover { transition: none; }
        }
    </style>
</head>
<body>
    <div class="rg-card">
        <!-- ================= left: live illustration ================= -->
        <div class="rg-art">
            <div class="rg-stage">
                <span class="rg-ring r1"></span>
                <span class="rg-ring r2"></span>
                <span class="rg-ring r3"></span>
                <span class="rg-orbit"><i></i></span>

                <div class="rg-mark">
                    <iconify-icon icon="simple-icons:squarespace"></iconify-icon>
                </div>

                <span class="rg-chip c1"><iconify-icon icon="solar:graph-up-bold" style="color:#3b5bfd;"></iconify-icon></span>
                <span class="rg-chip c2"><iconify-icon icon="solar:magnifer-bold" style="color:#16a34a;"></iconify-icon></span>
                <span class="rg-chip c3"><iconify-icon icon="solar:pie-chart-2-bold" style="color:#d97706;"></iconify-icon></span>
                <span class="rg-chip c4"><iconify-icon icon="solar:shield-check-bold" style="color:#7c3aed;"></iconify-icon></span>

                <iconify-icon class="rg-spark s1" icon="solar:star-bold"></iconify-icon>
                <iconify-icon class="rg-spark s2" icon="solar:star-bold"></iconify-icon>
                <iconify-icon class="rg-spark s3" icon="solar:star-bold"></iconify-icon>
            </div>

            <div class="rg-trust">
                <div class="rg-trust-ic"><iconify-icon icon="solar:shield-check-bold" class="text-cstm-primary"></iconify-icon></div>
                <div>
                    <h5>Secure &amp; Trusted</h5>
                    <p>We verify your Squarespace hosting and set up Google Search Console automatically.</p>
                </div>
            </div>
        </div>

        <!-- ================= right: form ================= -->
        <div class="rg-body">
            <span class="rg-badge">
                <iconify-icon icon="solar:shield-check-bold" class="text-sm"></iconify-icon>
                Squarespace Site Verification
            </span>

            <?php if ($successEmail !== ''): ?>
                <div class="rg-success">
                    <div class="ic"><iconify-icon icon="solar:letter-opened-bold"></iconify-icon></div>
                    <h2>Check your email</h2>
                    <p>
                        We sent a one-time sign-in link to <strong><?= htmlspecialchars($successEmail) ?></strong>.<br>
                        It works once and expires in 30 minutes.
                    </p>
                </div>
            <?php else: ?>
                <?php if ($errorMessage !== ''): ?>
                    <div class="rg-alert err"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>

                <h1 class="rg-h1">Connect your <span>Squarespace</span> site</h1>
                <p class="rg-lead">Enter your email and Squarespace website — we verify it, set up Google Search Console, and sign you in. No password needed.</p>

                <form method="POST" id="rg-form">
                    <div class="rg-field">
                        <label class="rg-label" for="email">Email</label>
                        <input class="rg-input" type="email" id="email" name="email" required
                               value="<?= htmlspecialchars((string) ($_POST['email'] ?? '')) ?>"
                               placeholder="you@example.com">
                    </div>
                    <div class="rg-field">
                        <label class="rg-label" for="website">Squarespace website</label>
                        <input class="rg-input" type="text" id="website" name="website" required
                               value="<?= htmlspecialchars((string) ($_POST['website'] ?? '')) ?>"
                               placeholder="yourstore.com">
                    </div>

                    <button type="submit" class="rg-cta" id="rg-submit">
                        <span class="spinner"></span>
                        <span class="label"><?= htmlspecialchars($buttonLabel) ?></span>
                        <iconify-icon icon="solar:arrow-right-linear" class="text-base"></iconify-icon>
                    </button>
                </form>

                <div class="rg-secure">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>We only read your site's public pages to verify Squarespace hosting</span>
                </div>

                <div class="rg-foot">
                    Already registered?
                    <a href="sign-in.php">Sign in with email</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.getElementById('rg-form')?.addEventListener('submit', function () {
            const btn = document.getElementById('rg-submit');
            if (!btn || btn.disabled) return;
            btn.disabled = true;
            btn.classList.add('loading');
            btn.querySelector('.label').textContent = 'Verifying your site...';
        });
    </script>
</body>
</html>
