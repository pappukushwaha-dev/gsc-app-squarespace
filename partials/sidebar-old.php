<?php
// =====================================================
// SHOP + INSTANCE (SHOPIFY SAFE)
// =====================================================
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$shopIdForApi =
  $_GET['shop_id']
  ?? $_GET['shop_id']
  ?? $_SESSION['shop_id']
  ?? $_SESSION['shop_id']
  ?? '';

if (!$shopIdForApi) {
  header("Location: auth/install.php");
  exit;
}

$_SESSION['shop_id'] = $shopIdForApi;
$_SESSION['shop_id']  = $shopIdForApi;

// Squarespace: billing/upgrade goes to our internal pricing page (instance-scoped).
$billingUrl = APP_URL . 'pricing.php?shop_id=' . urlencode($shopIdForApi);

require_once __DIR__ . '/../includes/sidebar_data.php';
?>


<aside class="sidebar cstm-sidebar">
  <button type="button" class="sidebar-close-btn !mt-4">
    <iconify-icon icon="radix-icons:cross-2"></iconify-icon>
  </button>

  <div>
    <a href="dashboard.php?shop_id=<?= urlencode($shopIdForApi) ?>" class="sidebar-logo">
      <img src="<?= htmlspecialchars($logoPath) ?>" alt="site logo" class="light-logo">
      <img src="<?= htmlspecialchars($darkLogoPath) ?>" alt="site logo" class="dark-logo">
      <img src="<?= htmlspecialchars($faviconPath) ?>" alt="site logo" class="logo-icon">
    </a>
  </div>

  <div class="sidebar-menu-area flex flex-col justify-start">
    <ul class="sidebar-menu cstm-sidebar-menu" id="sidebar-menu">
      <li>
        <a href="dashboard.php?shop_id=<?= urlencode($shopIdForApi) ?>">
          <iconify-icon icon="solar:widget-4-outline" class="menu-icon"></iconify-icon>
          <span>Dashboard</span>
        </a>
      </li>

      <li class="dropdown">
        <a href="javascript:void(0)" class="transition submenu-toggle">
          <iconify-icon icon="solar:chart-square-outline" class="menu-icon"></iconify-icon>
          <span class="flex-1">Analytics</span>
        </a>

        <ul class="sidebar-submenu max-h-0 overflow-hidden pl-3 transition-all duration-300 ease-in-out">

          <li>
            <a href="gsc-report.php?shop=<?= urlencode($shop) ?>&tab=overview" data-tab="overview"
              class="sidebar-tab-link block py-2 text-sm hover:text-blue-500 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <span>Overview</span>
            </a>
          </li>

          <li>
            <a href="gsc-report.php?tab=qp" data-tab="intent_commercial"
              class="sidebar-tab-link block py-2 text-sm hover:text-blue-500 flex items-center gap-2">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  d="M3 3h7l11 11-7 7L3 10V3z" />
                <circle cx="7.5" cy="7.5" r="1.5" fill="currentColor" />
              </svg>
              <span>Commercial</span>
            </a>
          </li>

          <li>
            <a href="gsc-report.php?shop=<?= urlencode($shop) ?>&tab=dates" data-tab="dates"
              class="sidebar-tab-link block py-2 text-sm hover:text-blue-500 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              <span>Brand vs Non Brand</span>
            </a>
          </li>
          <li>
            <a href="gsc-report.php?shop=<?= urlencode($shop) ?>&tab=qp" data-tab="qp"
              class="sidebar-tab-link block py-2 text-sm hover:text-blue-500 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
              </svg>
              <span>Top Page</span>
            </a>
          </li>

          <li>
            <a href="gsc-report.php?tab=qp" data-tab="intent_informational"
              class="sidebar-tab-link block py-2 text-sm hover:text-blue-500 flex items-center gap-2">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                  d="M4 5a2 2 0 012-2h12v16H6a2 2 0 01-2-2V5z" />
                <path stroke-width="2" stroke-linecap="round" d="M8 3v16" />
              </svg>
              <span>Informational</span>
            </a>
          </li>

          <li>
            <a href="gsc-report.php?shop=<?= urlencode($shop) ?>&tab=pages" data-tab="pages"
              class="sidebar-tab-link block py-2 text-sm hover:text-blue-500 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <span>Pages</span>
            </a>
          </li>

          <li>
            <a href="gsc-report.php?shop=<?= urlencode($shop) ?>&tab=countries" data-tab="countries"
              class="sidebar-tab-link block py-2 text-sm hover:text-blue-500 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Countries</span>
            </a>
          </li>

          <li>
            <a href="gsc-report.php?shop=<?= urlencode($shop) ?>&tab=devices" data-tab="devices"
              class="sidebar-tab-link block py-2 text-sm hover:text-blue-500 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              <span>Devices</span>
            </a>
          </li>

          <li>
            <a href="gsc-report.php?shop=<?= urlencode($shop) ?>&tab=appearance" data-tab="appearance"
              class="sidebar-tab-link block py-2 text-sm hover:text-blue-500 flex items-center gap-2">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
              </svg>
              <span>Search Appearance</span>
            </a>
          </li>

          <li>
            <a href="gsc-report.php?tab=qp" data-tab="intent_transactional"
              class="sidebar-tab-link block py-2 text-sm hover:text-blue-500 flex items-center gap-2">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <circle cx="9" cy="20" r="1.5" />
                <circle cx="17" cy="20" r="1.5" />
                <path
                  d="M5 6h2l2 9h8l2-6H9"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round" />
              </svg>
              <span>Transactional</span>
            </a>
          </li>

          <li>
            <a href="gsc-report.php?tab=qp" data-tab="intent_navigational"
              class="sidebar-tab-link block py-2 text-sm hover:text-blue-500 flex items-center gap-2">
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <!-- Outer circle -->
                <circle cx="12" cy="12" r="9" stroke-width="2" />
                <!-- Compass needle -->
                <path
                  d="M14.8 9.2L13 13l-3.8 1.8L11 11l3.8-1.8z"
                  stroke-width="2"
                  stroke-linejoin="round"
                  stroke-linecap="round" />
              </svg>
              <span>Navigational</span>
            </a>
          </li>

        </ul>

      </li>

      <li class="dropdown">
        <a href="javascript:void(0)" class="transition submenu-toggle">
          <iconify-icon icon="solar:folder-outline" class="menu-icon"></iconify-icon>
          <span class="flex-1">Help Center</span>
        </a>

        <ul class="sidebar-submenu max-h-0 overflow-hidden pl-3 transition-all duration-300 ease-in-out">
          <li>
            <a href="faq.php?shop_id=<?= urlencode($shopIdForApi) ?>" class="block py-2 text-sm hover:text-blue-500">
              <iconify-icon icon="solar:question-circle-outline" class="menu-icon"></iconify-icon>
              <span>FAQs</span></a>
          </li>
          <li>
            <a href="instructions.php?shop_id=<?= urlencode($shopIdForApi) ?>" class="block py-2 text-sm hover:text-blue-500">
              <iconify-icon icon="solar:document-text-outline" class="menu-icon"></iconify-icon>
              <span>Instructions</span></a>
          </li>
          <li>
            <a href="manage-tickets.php?shop_id=<?= urlencode($shopIdForApi) ?>" class="block py-2 text-sm hover:text-blue-500">
              <iconify-icon icon="mdi:ticket-confirmation-outline" class="menu-icon"></iconify-icon>
              <span>Manage Tickets</span>
            </a>
          </li>
        </ul>
      </li>

            <li class="dropdown">
        <a href="javascript:void(0)" class="transition submenu-toggle">
          <iconify-icon icon="icon-park-outline:setting-two" class="menu-icon"></iconify-icon>
          <span class="flex-1">Setting</span>
        </a>
        <ul class="sidebar-submenu max-h-0 overflow-hidden pl-3 transition-all duration-300 ease-in-out">
          <li>
            <a href="my_domains.php?shop_id=<?= urlencode($shopIdForApi) ?>">
              <iconify-icon icon="solar:global-linear" class="menu-icon"></iconify-icon>
              <span>My Domains</span>
            </a>
          </li>

          <li>
            <a href="sitemap.php?shop_id=<?= urlencode($shopIdForApi) ?>">
              <iconify-icon icon="solar:map-point-wave-outline" class="menu-icon"></iconify-icon>
              <span>Sitemap Manager</span>
            </a>
          </li>
        </ul>
      </li>

      
      <div class="my-3 bg-neutral-100 dark:bg-gray-800 border-neutral-200 border p-4 rounded-2xl text-center hidden">
        <p class="text-xl font-semibold text-neutral-900">Trial Plan</p>
        <p class="text-sm opacity-90 mt-1">11 Days left</p>

        <button class="w-full mt-2 h-10 btn btn-cstm-primary flex justify-center items-center">
          Upgrade Plan
        </button>
        <p class="text-sm opacity-90 mt-5"> If already activated</p>
        <button class="w-full mt-2 light h-10 btn btn-cstm-primary flex justify-center items-center">
          CLICK HERE
        </button>
      </div>
      <!-- <li>
        <a href="other-apps.php?shop_id=<?= urlencode($shopIdForApi) ?>">
          <iconify-icon icon="solar:widget-outline" class="menu-icon"></iconify-icon>
          <span>Our Other Apps</span>
        </a>
      </li> -->
      <!-- <li>
        <a href="settings.php?shop_id=<?= urlencode($shopIdForApi) ?>">
          <iconify-icon icon="icon-park-outline:setting-two" class="menu-icon"></iconify-icon>
          <span>Setting</span>
        </a>
      </li> -->

      <!-- Trial Plan Box -->
      <!-- OTHER APPS -->
      <li class="sidebar-menu-group-title">Our Other Apps</li>
      <ul id="otherAppsSidebar">
        <li class="text-sm text-gray-500 px-4">Loading apps...</li>
      </ul>
      <li>
        <a href="other-apps.php">
          <iconify-icon icon="solar:widget-5-outline" class="menu-icon"></iconify-icon>
          <span>More Apps</span>
        </a>
      </li>
    </ul>
    <!-- ===== Clean Plan box (no repetition, compact) ===== -->
    <div id="sidebar-plan-box" class="sidebar-plan-box p-4 my-4 rounded-lg border text-white border-neutral-200  bg-cstm-primary-70 drk-bg-cstm-primary-gradient-dark  dark:border-neutral-600 shadow-sm" role="region" aria-labelledby="plan-title-label">
      <!-- Loading skeleton -->
      <div id="plan-box-loading" class="animate-pulse space-y-2">
        <div class="h-4 w-1/3 bg-neutral-200 dark:bg-neutral-700 rounded"></div>
        <div class="h-6 w-2/3 bg-neutral-200 dark:bg-neutral-700 rounded mt-2"></div>
        <div class="h-10 w-full bg-neutral-200 dark:bg-neutral-700 rounded mt-4"></div>
      </div>
      <iconify-icon icon="mdi:account-outline" class="user-mobile-icon menu-icon text-xl"></iconify-icon>

      <!-- Content -->
      <div id="plan-box-content" class="justify-between flex-col relative items-start" style="display:none;">
        <div class="flex items-start w-full justify-between">
          <div>
            <div id="plan-title" class="text-xl font-semibold dark:text-white">Plan Name</div>
            <div id="plan-sub" class="text-xs text-neutral-300 dark:text-neutral-400 mt-0.5 uppercase">ACTIVE</div>
            <div id="plan-price" class="text-sm font-medium text-white mt-1"></div>

          </div>

          <!-- small decorative badge (no duplicate status text) -->
          <div id="plan-badge" class="ml-3 text-xs font-medium px-2 py-0.5 rounded-full bg-cyan-600 text-white text-primary-700 hidden" aria-hidden="true">PAID</div>
        </div>

        <!-- prominent countdown -->
        <div class="mt-2">
          <div id="plan-countdown" class="text-2xl font-bold dark:text-neutral-100 leading-tight">—</div>
          <div id="plan-note" class="text-xs dark:text-neutral-400 mt-1 hidden">Note for trial users</div>
        </div>

        <!-- CTA -->
        <div class="mt-2 w-full">
          <a
            id="plan-cta-primary"
            href="<?= htmlspecialchars($billingUrl) ?>"
            class="btn-cstm-primary light w-full inline-block text-center py-2 rounded-md text-white font-normal text-sm">
            UPGRADE
          </a>
        </div>
      </div>

      <!-- Error -->
      <div id="plan-box-error" class="text-xs text-rose-600 mt-2" style="display:none;"></div>
    </div>

    <!-- ===== end plan box ===== -->
    <ul class="mb-4 flex flex-col gap-2 sidebar-bottom-menus">
      <li class="review-btn-block w-full">
        <a href="javascript:void(0)" id="openReviewModal" class="btn bg-success-600 text-white justify-center gap-1 items-center w-full px-3">
          <iconify-icon icon="mdi:star-circle-outline" class="menu-icon text-xl"></iconify-icon>
          <span class="text-base">Leave a Review</span>
        </a>
      </li>
      <!-- <li>
        <button type="button" id="btn-new-ticket" class="btn btn-cstm-primary justify-center gap-1 items-center w-full px-3">
          <iconify-icon icon="mdi:headset" class="text-lg"></iconify-icon>
          <span>Contact Support</span>
        </button>
      </li> -->
      <!-- <li class="review-btn-block w-full">
        <a href="javascript:void(0)" onclick="CancellationFlow.open()" class="btn btn-cstm-primary light justify-center gap-1 items-center w-full px-3">
          <iconify-icon icon="mdi:close-circle-outline" class="menu-icon text-xl"></iconify-icon>
          <span>Cancel Subscription</span>
        </a>
      </li> -->
    </ul>
  </div>
</aside>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const body = document.body;
    const sidebar = document.querySelector(".sidebar");
    const sidebarToggle = document.querySelector(".sidebar-toggle");
    const sidebarMobileToggle = document.querySelector(".sidebar-mobile-toggle");
    const sidebarCloseBtn = document.querySelector(".sidebar-close-btn");

    // Create overlay if missing
    let overlay = document.querySelector(".sidebar-overlay");
    if (!overlay) {
      overlay = document.createElement("div");
      overlay.className = "sidebar-overlay";
      document.body.appendChild(overlay);
    }

    function toggleSidebar() {
      const isOpen = body.classList.toggle("sidebar-open");
      sidebar.setAttribute("aria-expanded", isOpen);
      overlay.classList.toggle("active", isOpen);
    }

    [sidebarToggle, sidebarMobileToggle, sidebarCloseBtn, overlay].forEach(el => {
      if (el) el.addEventListener("click", toggleSidebar);
    });

    function handleResponsiveSidebar() {
      if (window.innerWidth <= 1024) {
        body.classList.remove("sidebar-open");
        overlay.classList.remove("active");
      }
    }

    window.addEventListener("resize", handleResponsiveSidebar);
    handleResponsiveSidebar();
  });
</script>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const shopId = <?= json_encode($shopIdForApi) ?>;
    const apiUrl = '/squarespace/googlesearchconsole/api/subscription-status.php';

    // DOM refs
    const loadingEl = document.getElementById('plan-box-loading');
    const contentEl = document.getElementById('plan-box-content');
    const errorEl = document.getElementById('plan-box-error');

    const titleEl = document.getElementById('plan-title');
    const subEl = document.getElementById('plan-sub');
    const badgeEl = document.getElementById('plan-badge');
    const countdownEl = document.getElementById('plan-countdown');
    const ctaPrimary = document.getElementById('plan-cta-primary');
    const noteEl = document.getElementById('plan-note');

    // ensure overlay exists (sidebar code elsewhere)
    let overlay = document.querySelector(".sidebar-overlay");
    if (!overlay) {
      overlay = document.createElement("div");
      overlay.className = "sidebar-overlay";
      document.body.appendChild(overlay);
    }

    // --- date helpers ---
    function parseServerDate(s) {
      if (!s) return null;
      let raw = String(s).trim();
      // Accept "YYYY-MM-DD HH:MM:SS" and convert to ISO-like string
      if (/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}/.test(raw)) raw = raw.replace(' ', 'T') + 'Z';
      const d = new Date(raw);
      return isNaN(d) ? null : d;
    }

    function timePartsDiff(now, expires) {
      const ms = expires - now; // Allow negative values for expired dates
      const totalMinutes = Math.floor(Math.abs(ms) / 60000);
      return {
        days: Math.floor(totalMinutes / 1440),
        hours: Math.floor((totalMinutes % 1440) / 60),
        minutes: totalMinutes % 60,
        isExpired: ms < 0
      };
    }

    // Always display days / hours / minutes (no years/months breakdown)
    function formatCountdownString(expiresDate, planType, billingPeriod) {
      const now = new Date();
      if (!expiresDate) return "—";

      const diff = timePartsDiff(now, expiresDate);

      // If expired, show "EXPIRED" with time ago
      if (diff.isExpired) {
        if (diff.days > 0) {
          return `EXPIRED ${diff.days}D ${String(diff.hours).padStart(2,'0')}H ${String(diff.minutes).padStart(2,'0')}MIN AGO`;
        }
        if (diff.hours > 0) {
          return `EXPIRED 0D ${String(diff.hours).padStart(2,'0')}H ${String(diff.minutes).padStart(2,'0')}MIN AGO`;
        }
        return `EXPIRED 0D 00H ${String(diff.minutes).padStart(2,'0')}MIN AGO`;
      }

      // Format: "23D 11H 34MIN" (always show days, hours, minutes)
      if (diff.days > 0) {
        return `${diff.days}D ${String(diff.hours).padStart(2,'0')}H ${String(diff.minutes).padStart(2,'0')}MIN`;
      }
      if (diff.hours > 0) {
        return `0D ${String(diff.hours).padStart(2,'0')}H ${String(diff.minutes).padStart(2,'0')}MIN`;
      }
      return `0D 00H ${String(diff.minutes).padStart(2,'0')}MIN`;
    }

    let countdownTimer = null;

    function startCountdownUpdater(expiresOn, planType, billingPeriod) {
      clearInterval(countdownTimer);
      if (!expiresOn) {
        countdownEl.textContent = "—";
        countdownEl.innerHTML = "—";
        return;
      }
      const expires = parseServerDate(expiresOn);
      if (!expires || isNaN(expires.getTime())) {
        countdownEl.textContent = "—";
        countdownEl.innerHTML = "—";
        return;
      }

      function tick() {
        const txt = formatCountdownString(expires, planType, billingPeriod);
        if (countdownEl) {
          countdownEl.textContent = txt;
          // Style expired text differently
          if (txt.includes('EXPIRED')) {
            countdownEl.classList.add('text-red-400', 'dark:text-red-400');
            countdownEl.classList.remove('text-neutral-100', 'dark:text-neutral-100');
          } else {
            countdownEl.classList.remove('text-red-400', 'dark:text-red-400');
            countdownEl.classList.add('text-neutral-100', 'dark:text-neutral-100');
          }
          countdownEl.innerHTML = txt.split(" ").map(p => `<span>${p}</span>`).join("");
        }
      }

      tick();
      const now = new Date();
      const msLeft = expires - now;
      const isExpired = msLeft < 0;

      // Update every minute for expired plans, every second in last day for active plans
      const interval = isExpired ? 60000 : (msLeft <= 86400000 ? 1000 : 60000);

      countdownTimer = setInterval(() => {
        tick();
        // Don't stop updating for expired plans - keep showing time ago
      }, interval);
    }

    function setCTA(planType, status, planName) {
      const billingUrl = <?= json_encode($billingUrl ?? '') ?>;
      const planNameLower = (planName || '').toLowerCase();
      const isBasicOrFree = planNameLower.includes('basic') || planNameLower.includes('free');

      // If it's a paid plan AND not Basic/Free, show "Manage"
      // Otherwise show "Upgrade Plan" or "Choose Plan"
      if (planType === "paid" && !isBasicOrFree) {
        ctaPrimary.textContent = "Manage";
      } else if (planType === "free" || isBasicOrFree) {
        ctaPrimary.textContent = "Upgrade Plan";
      } else {
        ctaPrimary.textContent = "Choose Plan";
      }

      // Internal pricing page — stay inside the Squarespace iframe.
      ctaPrimary.href = billingUrl;
      ctaPrimary.removeAttribute("target");
    }


    // Normalize API response into the old flat shape your UI expects
    function normalizeApiResponse(json) {
      if (!json) return {
        plan_type: 'none'
      };

      // new API shape: { success: true, subscription: { ... }, trial_eligible: false }
      if (json.subscription) {
        const s = json.subscription;

        // normalize billing period once
        const billingNorm = (s.billing_period || s.billingPeriod || '').toLowerCase();
        const planName = (s.plan_name || '').toLowerCase();

        let plan_type = 'none';

        // Check if plan name contains 'basic' or 'free' - treat as free/limited
        const isBasicOrFree = planName.includes('basic') || planName.includes('free') || billingNorm === 'free' || billingNorm === 'limited';

        // Determine plan type - keep as 'paid' even if expired
        if (s.type === 'subscription' && !isBasicOrFree && billingNorm !== 'limited') {
          plan_type = 'paid'; // Keep as paid even if status is 'expired'
        } else if (s.type === 'free_trial') {
          plan_type = 'free';
        } else if (isBasicOrFree || billingNorm === 'limited') {
          plan_type = 'free';
        }


        return {
          plan_type,
          plan_name: s.plan_name || (
            billingNorm === 'limited' ?
            'Free' :
            (plan_type === 'free' ? 'Free Trial' : null)
          ),
          status: (s.status || '').toLowerCase(),
          billing_period: billingNorm,
          expires_on: s.expires_on || s.expiresOn || null,
          raw_subscription: s,
          trial_eligible: !!json.trial_eligible,
          // flag so UI knows this is the "limited access" free plan
          is_limited_access: billingNorm === 'limited'
        };
      }

      // fallback: older flat shape (already compatible)
      const billingNorm = (json.billing_period || json.billingPeriod || '').toLowerCase();
      let plan_type = (json.plan_type || json.planType || 'none').toLowerCase();

      // If coming from old flat structure, also treat billing_period=limited as free tier
      if (billingNorm === 'limited' && plan_type === 'paid') {
        plan_type = 'free';
      }

      return {
        plan_type,
        plan_name: json.plan_name || json.planName || (
          billingNorm === 'limited' ?
          'Free' :
          (plan_type === 'free' ? 'Free Trial' : null)
        ),
        status: (json.status || '').toLowerCase(),
        billing_period: billingNorm,
        expires_on: json.expires_on || json.expiresOn || null,
        raw_subscription: json.raw_event ? {
          billing_period: json.billing_period || json.billingPeriod
        } : null,
        trial_eligible: !!json.trial_eligible,
        is_limited_access: billingNorm === 'limited'
      };
    }

    // Render using the normalized shape
    function renderPlanNormalized(n) {
      loadingEl.style.display = "none";
      errorEl.style.display = "none";
      contentEl.style.display = "flex";

      const type = (n.plan_type || 'none').toLowerCase();
      const status = (n.status || '').toLowerCase();
      const planName = n.plan_name || (type === "free" ? "Free Trial" : "No Plan");
      const billing = n.billing_period || "";
      const expires = n.expires_on || null;
      const raw = n.raw_subscription || null;
      const priceEl = document.getElementById('plan-price');

      // Hide price for paid plans - show countdown instead
      // Only show price for free/limited plans if available
      if (type === "paid" && !n.is_limited_access) {
        priceEl.style.display = 'none';
      } else if (n.raw_subscription && n.raw_subscription.price) {
        const currency = n.raw_subscription.currency || 'USD';
        priceEl.textContent = `${currency} ${n.raw_subscription.price}`;
        priceEl.style.display = '';
      } else {
        priceEl.style.display = 'none';
      }


      // ✅ Detect LIMITED ACCESS plan (used to be limited)
      // Also treat 'free' billing_period and 'Basic' plan name as limited access
      // BUT: Grow plan should NEVER be limited access, even if billing_period is 'limited'
      const planNameLower = (n.plan_name || '').toLowerCase();
      const isGrowPlan = planNameLower.includes('grow');
      const isBasicOrFreePlan = (planNameLower.includes('basic') || planNameLower.includes('free') || billing === 'free') && !isGrowPlan;

      const isLimitedAccess = !isGrowPlan && (
        !!n.is_limited_access ||
        (raw && String(raw.billing_period || raw.billingPeriod || '').toLowerCase() === 'limited') ||
        (billing === 'limited') ||
        isBasicOrFreePlan
      );

      titleEl.textContent = planName;
      // Show status - "ACTIVE" for active plans, "EXPIRED" for expired, or status from API
      if (status === "expired") {
        subEl.textContent = "EXPIRED";
        subEl.classList.add('text-red-400', 'dark:text-red-400');
      } else if (type === "paid" && status === "active") {
        subEl.textContent = "ACTIVE";
        subEl.classList.remove('text-red-400', 'dark:text-red-400');
      } else {
        subEl.textContent = status ? status.toUpperCase() : (type === "free" ? "TRIAL" : "NO PLAN");
        subEl.classList.remove('text-red-400', 'dark:text-red-400');
      }

      // ✅ Badge: LIMITED ACCESS / PAID / TRIAL
      if (isLimitedAccess) {
        badgeEl.textContent = "Limited access";
        badgeEl.classList.remove('hidden');
      } else if (type === "paid") {
        badgeEl.textContent = "PAID";
        badgeEl.classList.remove('hidden');
      } else if (type === "free") {
        badgeEl.textContent = "TRIAL";
        badgeEl.classList.remove('hidden');
      } else {
        badgeEl.classList.add('hidden');
      }

      // ✅ Note: show trial note only for real free trials (not limited free)
      noteEl.style.display = (type === "free" && !isLimitedAccess) ? "" : "none";
      noteEl.textContent = (type === "free" && !isLimitedAccess) ?
        "Trial active — upgrade any time." :
        "";

      setCTA(type, status, planName);

      // ✅ LIMITED ACCESS → static label, no countdown
      if (isLimitedAccess) {
        clearInterval(countdownTimer);

        // Hide countdown completely for limited plans
        countdownEl.textContent = "";
        countdownEl.innerHTML = "";
      } else {
        // For trials / paid non-limited — start normal countdown (if expires provided)
        if (expires) {
          // Always show countdown for plans with expiry (including expired ones)
          startCountdownUpdater(expires, type, billing);
        } else {
          // No expiry date - show "Recurring" for active paid plans
          if (type === "paid" && status === "active") {
            countdownEl.textContent = "Recurring";
            countdownEl.innerHTML = "Recurring";
          } else {
            countdownEl.textContent = "—";
            countdownEl.innerHTML = "—";
          }
        }
      }

      window.currentPlan = {
        type,
        status,
        billing,
        trialExists: type === "free",
        expires_on: expires || null,
        raw: raw
      };

      document.dispatchEvent(new CustomEvent("planLoaded", {
        detail: window.currentPlan
      }));
    }

    function fetchPlan() {
      loadingEl.style.display = "";
      contentEl.style.display = "none";
      errorEl.style.display = "none";

      // pass both forms of parameter for compatibility
      const q = apiUrl + "?shop_id=" + encodeURIComponent(shopId || "") + "&shopId=" + encodeURIComponent(shopId || "");
      fetch(q, {
          credentials: 'same-origin'
        })
        .then(resp => {
          if (!resp.ok) throw new Error('Network response not ok: ' + resp.status);
          return resp.json();
        })
        .then(json => {
          const normalized = normalizeApiResponse(json || {});
          renderPlanNormalized(normalized);
        })
        .catch(err => {
          loadingEl.style.display = "none";
          contentEl.style.display = "none";
          errorEl.style.display = "";
          errorEl.textContent = "Unable to load plan info";
          console.error("Plan fetch failed", err);
        });
    }

    // initial fetch + periodic refresh
    fetchPlan();
    const refreshInterval = setInterval(fetchPlan, 5 * 60 * 1000);
    window.addEventListener('beforeunload', () => {
      clearInterval(refreshInterval);
      clearInterval(countdownTimer);
    });

    // backward-compatible page access check
    window.checkPageAccess = function(requiredPlan = "paid") {
      const plan = window.currentPlan;
      const overlayElement = document.getElementById("page-lock-overlay");
      if (!overlayElement) return;
      const btn = document.getElementById("page-lock-btn");
      if (!plan) {
        overlayElement.style.display = 'flex';
        if (btn) {
          btn.textContent = "View Plans";
          btn.href = "current-plan.php?shop_id=<?= urlencode($shopIdForApi) ?>";
        }
        return;
      }
      if (requiredPlan === "paid" && plan.type !== "paid") {
        overlayElement.style.display = "flex";
        if (btn) {
          btn.textContent = plan.type === "free" ? "Upgrade Plan" : "Activate Trial";
          btn.href = "current-plan.php?shop=<?= urlencode($shop) ?>&shop_id=" + encodeURIComponent(shopId || "");
        }
        return;
      }
      overlayElement.style.display = "none";
    };

  }); // DOMContentLoaded
</script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    // Only attach handler if we're NOT on manage-tickets page (which has its own handler)
    const currentPath = window.location.pathname || window.location.href;
    if (currentPath.includes('manage-tickets') || currentPath.includes('manage-tickets.php')) {
      return; // Let manage-tickets.php handle its own button
    }

    const sidebarBtn = document.getElementById('btn-new-ticket');

    if (sidebarBtn) {
      sidebarBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (window.TicketPopup && typeof window.TicketPopup.open === 'function') {
          window.TicketPopup.open();
        } else {
          console.warn('TicketPopup not available');
        }
      });
    }
  });
</script>
<script>
  document.addEventListener('DOMContentLoaded', function() {

    const sidebar = document.getElementById('otherAppsSidebar');
    const shopId = '<?php echo $_SESSION["shop_id"] ?? ""; ?>';

    if (!sidebar) {
      console.error('otherAppsSidebar not found');
      return;
    }

    if (!shopId) {
      sidebar.innerHTML = '<li class="text-sm text-gray-500 px-4">Instance ID missing</li>';
      return;
    }

    fetch(`api/index-apps.php?shop_id=${shopId}`)
      .then(res => res.json())
      .then(res => {

        if (!res.success || !Array.isArray(res.data)) {
          sidebar.innerHTML = '<li class="text-sm text-gray-500 px-4">No apps available.</li>';
          return;
        }

        sidebar.innerHTML = '';

        res.data.forEach(app => {

          if (!app.button_link) return; // 🔥 skip broken rows

          sidebar.innerHTML += `
                    <li>
                        <a href="${app.button_link}"
                           target="_blank"
                           class="flex items-center gap-3 px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md">

                            <img src="${app.image_url || ''}"
                                 class="w-5 h-5 object-contain"
                                 alt="">

                            <span>${app.title}</span>
                        </a>
                    </li>
                `;
        });
      })
      .catch(err => {
        console.error(err);
        sidebar.innerHTML =
          '<li class="text-sm text-red-500 px-4">Failed to load apps.</li>';
      });
  });
</script>
<?php include __DIR__ . '/review-popup.php'; ?>
<?php include __DIR__ . '/cancel-popup.php'; ?>
<?php include __DIR__ . '/trial-popup.php'; ?>