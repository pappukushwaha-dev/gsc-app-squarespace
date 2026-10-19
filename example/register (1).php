<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!empty($_SESSION['userId'])) {
    header('Location: index.php');
    exit;
}

// OAuth login (Google / LinkedIn) — buttons render via partials/oauth-buttons.php,
// which self-detects configured providers. Ensure $pdo + BASE_URL are in scope for
// it; keep the heavy oauth.php chain off this public page.
require_once __DIR__ . '/includes/config.php';   // $pdo, BASE_URL
$oauthFlow = 'register';

$title = 'Register to Squarespace';
$description = 'Create your Squarespace JSON Schema App account to automate schema markup, boost SEO, and enable rich results like ratings, FAQs, and product details.';

?>
<!DOCTYPE html>
<html lang="en">
<?php include './partials/head.php'; ?>

<body class="dark:bg-neutral-800 bg-neutral-100 dark:text-white">
    <!-- Google Tag Manager (noscript) -->
  <?php if (!empty($adminData['gtm_id'])): ?>
<noscript>
    <iframe
        src="https://www.googletagmanager.com/ns.html?id=<?= urlencode($adminData['gtm_id']) ?>"
        height="0"
        width="0"
        style="display:none;visibility:hidden">
    </iframe>
</noscript>
<?php endif; ?>
    <!-- End Google Tag Manager (noscript) -->
    <section class="bg-white dark:bg-dark-2 grid grid-cols-12 min-h-[100vh]">



        <div class="col-span-12 lg:col-span-4 py-8 px-6 md:px-16 flex flex-col justify-center">
            <div class="lg:max-w-[464px] mx-auto w-full">

                <div class="text-center lg:text-left">
                    <a href="index.php" class="mb-2.5 max-w-[220px] inline-block">
                        <img src="<?= htmlspecialchars($logoPath) ?>" class="max-w-[200px]" alt="Logo">
                    </a>
                    <div id="registerHeading">
                        <h1 class="mb-1 text-2xl font-bold">Create an Account</h1>
                        <p class="mb-6 text-neutral-500">
                            Sign up to start using your dashboard.
                        </p>
                    </div>

                </div>

                <form id="registerForm" class="space-y-4">

                    <!-- Platform -->
                    <input type="hidden" id="platform" value="squarespace">



                    <!-- Website -->
                    <div>
                        <label for="platform" class="block mb-1 text-sm font-semibold text-neutral-600">
                            Website URL
                        </label>
                        <input id="website" type="url" placeholder="Website URL (https://example.com)" class="form-control rounded-lg"
                            required>
                    </div>
                    <div>
                        <label for="platform" class="block mb-1 text-sm font-semibold text-neutral-600">
                            Email Address
                        </label>
                        <input id="email" type="email" placeholder="Email Address" class="form-control rounded-lg" required>
                    </div>
      
                    <div class="flex items-start gap-2 mt-4">
                        <input type="checkbox" id="agreeTerms" class="mt-1">
                        <label for="agreeTerms" class="text-sm text-neutral-600">
                            I agree Json-Ld Schema
                            <a href="#" class="text-cstm-primary underline">Terms of Service</a>,
                            <a href="#" class="text-cstm-primary underline">Cancellation & Refund Policy</a>
                            and
                            <a href="#" class="text-cstm-primary underline">Privacy Policy</a>
                        </label>
                    </div>

                    <p id="termsError" class="text-sm text-red-600 mt-2 hidden">
                        You must agree to the terms before creating an account.
                    </p>

                    <button id="registerBtn" class="btn btn-cstm-primary w-full rounded-xl py-4 mt-5 flex items-center justify-center">
                        Create Account
                    </button>

                    <?php include __DIR__ . '/partials/oauth-buttons.php'; ?>

                    <p class="text-center text-sm text-neutral-500">
                        Already have an account?
                        <a href="sign-in.php" class="font-bold hover:underline">Sign In</a>
                    </p>
                </form>

                <!-- OTP Form -->
                <!-- <div id="otpFormContainer" class="hidden lg:max-w-[464px] mx-auto w-full">
                    <div class="text-center lg:text-left">
                        <h4 class="mb-2 mt-3 text-2xl font-bold">Verify Your Code</h4>
                        <p class="mb-4 text-neutral-500">
                            We sent a 6-digit code to <b id="otpEmail"></b>
                        </p>
                    </div>

                    <form id="otpForm" class="space-y-4">
                        <div class="flex justify-center gap-3 mb-4" id="otp-wrapper">
                            <input type="text" maxlength="1" inputmode="numeric" class="otp-box w-12 h-14 text-center text-xl font-semibold border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" />
                            <input type="text" maxlength="1" inputmode="numeric" class="otp-box w-12 h-14 text-center text-xl font-semibold border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" />
                            <input type="text" maxlength="1" inputmode="numeric" class="otp-box w-12 h-14 text-center text-xl font-semibold border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" />
                            <input type="text" maxlength="1" inputmode="numeric" class="otp-box w-12 h-14 text-center text-xl font-semibold border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" />
                            <input type="text" maxlength="1" inputmode="numeric" class="otp-box w-12 h-14 text-center text-xl font-semibold border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" />
                            <input type="text" maxlength="1" inputmode="numeric" class="otp-box w-12 h-14 text-center text-xl font-semibold border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500" />
                        </div>
                        <div id="otpError" class="text-sm text-red-600 text-center font-medium mt-2 mb-3 hidden">
                        </div>

                        <button type="submit" id="otpSubmitBtn" class="btn btn-cstm-primary rounded-xl py-4 mt-2 block mx-auto">
                            Verify OTP
                        </button>
                        <div class="text-center mt-3">
                            <p id="otpTimer" class="text-sm text-neutral-500">
                                Resend OTP in <span id="otpCountdown">60</span>s
                            </p>

                            <button id="resendOtpBtn"
                                class="text-sm font-semibold text-cstm-success hidden">
                                Resend OTP
                            </button>
                        </div>

                    </form>
                </div> -->


            </div>
        </div>
        <?php include_once __DIR__ . '/partials/auth_side.php'; ?>
    </section>

    <script>
        function autoFormatURL(input) {
            let value = input.value.trim();
            if (value !== "") {
                // If it doesn't start with http:// or https://, add https://
                if (!/^https?:\/\//i.test(value)) {
                    input.value = "https://" + value;
                }
            }
        }

        // 1. Check when the user finishes typing/leaves the field
        document.getElementById('website').addEventListener('change', function() {
            autoFormatURL(this);
        });



        function normalizeURL(input) {
            let value = input.value.trim();
            if (!value) return;
            if (!/^https?:\/\//i.test(value)) {
                value = "https://" + value;
            }
            try {
                const url = new URL(value);
                if (url.pathname === "/") {
                    url.pathname = "";
                }
                input.value = url.toString().replace(/\/$/, '');
            } catch {}
        }

        const websiteirr = document.getElementById('website');

        websiteirr.addEventListener('input', function() {
            normalizeURL(this);
        });

        websiteirr.addEventListener('change', function() {
            normalizeURL(this);
        });

        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const agreeCheckbox = document.getElementById('agreeTerms');
            const termsError = document.getElementById('termsError');

            if (!agreeCheckbox.checked) {
                termsError.classList.remove('hidden');
                return;
            } else {
                termsError.classList.add('hidden');
            }

            const websiteField = document.getElementById('website');
            autoFormatURL(websiteField);
            const btn = document.getElementById('registerBtn');
            btn.disabled = true;
            btn.innerText = 'Creating...';

            const payload = {
                platform: document.getElementById('platform').value,
                website: websiteField.value.trim(),
                email: document.getElementById('email').value.trim(),
            };

            if (!payload.website.startsWith('http')) {
                Popup.error('Please enter a valid website URL');
                btnReset();
                return;
            }

            try {
                const res = await fetch('api/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await res.json();

                if (!data.ok) {
                    Popup.error(data.message);
                    btnReset();
                    return;
                }

                /* ================= NON-SQUARESPACE (Wix/Other) ================= */
                if (data.installUrl) {
                    // Hide the cancel button for this popup
                    const cancelBtn = document.getElementById('popupCancelBtn');
                    if (cancelBtn) cancelBtn.style.display = 'none';


                    Popup.confirm({
                        title: 'Install the Wix App',
                        body: `
                <div class="w-full card border border-gray-200 dark:border-neutral-600
                            rounded-xl overflow-hidden flex items-start p-4
                            hover:shadow-md transition-shadow dark:bg-neutral-800">

                    <!-- ICON -->
                    <div class="flex-shrink-0 mr-4 h-16 w-16 bg-gray-50 rounded-full
                                flex items-center justify-center border border-gray-200">
                        <img src="./assets/images/apps/json.png"
                            alt="App Icon"
                            class="h-10 w-10 object-contain">
                    </div>

                    <!-- CONTENT -->
                    <div class="flex-grow">
                        <div class="flex items-start justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 leading-tight">
                            Schema (JSON-LD) App
                            </h3>

                            <span class="text-xs font-medium text-cstm-primary
                                        bg-cstm-primary-20 px-2 py-0.5
                                        rounded-full ml-4 shrink-0">
                                Free
                            </span>
                        </div>

                        <p class="text-sm text-gray-600 mt-1 mb-3">
                            Schema (JSON-LD) helps Google and AI tools clearly understand your business details such as domain, location, reviews, pricing, offers, and customer experience. These rich results improve click-through rates, strengthen brand visibility in search, and drive more organic traffic and sales without additional ad spend.
                        </p>
                    </div>
                </div>
                        `,
                        onConfirm: () => {
                            window.open(data.installUrl, '_blank');
                            location.reload();
                        }
                    });

                    return;
                }


                // const otpFormContainer = document.getElementById('otpFormContainer');
                // const registerForm = document.getElementById('registerForm');

                // registerForm.classList.add('hidden');
                // document.getElementById('registerHeading').classList.add('hidden');

                // otpFormContainer.classList.remove('hidden');
                // startOtpTimer();

                // document.getElementById('otpEmail').textContent = data.email || payload.email;

                // document.querySelector('.otp-box').focus();
                // btnReset();
                /* ================= MAGIC LINK FLOW ================= */
                if (data.ok) {
                    try {
                        const magicRes = await fetch('api/send-magic-link.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                email: payload.email
                            })
                        });

                        const magicData = await magicRes.json();

                        if (!magicData.success) {
                            Popup.error(magicData.message || 'Failed to send verification link.');
                            btnReset();
                            return;
                        }

                        document.getElementById('registerForm').classList.add('hidden');
                        document.getElementById('registerHeading').innerHTML = `
                               <div class="w-full max-w-md bg-white rounded-2xl p-8 text-center animate-fadeIn">
                        <div class="inline-flex items-center justify-center w-16 h-16 mb-6 rounded-full bg-purple-100 text-purple-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                            <h1 class="mb-3 text-3xl font-semibold text-gray-900 tracking-tight">Check your email</h1>
                            <p class="text-base text-gray-500 mb-4">We sent a magic verification link to:</p>
                                <div class="overflow-x-auto"> <div class="inline-block bg-gray-100 text-gray-800 px-4 py-2 rounded-lg font-medium mb-6">${payload.email}</div></div>
                             <p class="text-base text-gray-400 mb-6">Please click the link to verify your account.</p>
                        <a href="#" id="resendMagicLinkBtn" class="btn btn-cstm-primary w-full rounded-xl py-4 mt-5 flex items-center justify-center font-medium mb-3">
                            Resend Email
                        </a>
                        <a href="/register.php" class="btn btn-cstm-muted w-full rounded-xl py-4 flex items-center justify-center font-medium">Back to registration</a>
                        <p class="text-sm text-gray-400 mt-6">
                            Didn’t receive anything? Check your spam folder.
                        </p>
                        </div>
        `;

                    const resendBtn = document.getElementById('resendMagicLinkBtn');

                    resendBtn.addEventListener('click', async (e) => {

                        e.preventDefault();

                        resendBtn.disabled = true;
                        resendBtn.innerText = 'Sending...';

                        try {

                            const resendRes = await fetch('api/send-magic-link.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    email: payload.email,
                                    user_id: data.user_id,
                                    platform_name: data.platform_name || ''
                                })
                            });

                            const resendData = await resendRes.json();

                            if (!resendData.success) {

                                Popup.error(resendData.message || 'Failed to resend email');

                                resendBtn.disabled = false;
                                resendBtn.innerText = 'Resend Email';

                                return;
                            }

                            Popup.success('Verification email sent again');

                            resendBtn.innerText = 'Email Sent';

                            setTimeout(() => {
                                resendBtn.disabled = false;
                                resendBtn.innerText = 'Resend Email';
                            }, 3000);

                        } catch (err) {

                            Popup.error('Unable to resend email');

                            resendBtn.disabled = false;
                            resendBtn.innerText = 'Resend Email';
                        }
                    });

                        Popup.success('Verification link sent.');
                        btnReset();
                        return;

                    } catch (e) {
                        Popup.error('Unable to send verification link.');
                        btnReset();
                    }
                }

            } catch (err) {
                Popup.error('Network error. Please try again.');
                btnReset();
            }

            function btnReset() {
                btn.disabled = false;
                btn.innerText = 'Create Account';
            }
        });
    </script>



    <?php include './partials/script.php'; ?>
    <?php include './includes/popup-helper.php'; ?>

    <!-- <script src="<?= ASSET_URL ?>/js/register-otp.js"></script> -->
</body>

</html>