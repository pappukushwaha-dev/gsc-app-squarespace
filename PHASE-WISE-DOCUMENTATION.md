# Squarespace GSC App — Phase-Wise Documentation

## Overview

This document describes the Squarespace Google Search Console (GSC) app structure, file inventory, comparison with other platforms (Wix, Shopify, Ecwid, WordPress), and the complete setup wizard flow. Use this as the reference for phased development and deployment.

---

## 1. File Inventory — Squarespace (gsc-app-squarespace)

### Total: ~116 PHP files (excluding vendor/)

### Root-level files (30):
| File | Purpose |
|------|---------|
| `.gitignore` | Git ignore rules |
| `.htaccess` | Apache config |
| `01-squarespace-other-app-clicks.sql` | Other app clicks tracking table |
| `01-squarespace-payload-queue.sql` | Email payload queue table |
| `composer.json` | PHP dependencies (google/apiclient, vlucas/phpdotenv) |
| `connect_google.php` | Starts Google OAuth flow |
| `dashboard.php` | Main dashboard with GSC stats |
| `faq.php` | FAQ page |
| `googlesearchconsole.js` | Frontend JavaScript |
| `google_oauth_callback.php` | Google OAuth callback handler |
| `index.php` | Landing page |
| `instructions.php` | Instructions page |
| `logout.php` | Logout handler |
| `manage-tickets.php` | Support ticket management |
| `mcp-config.php` | MCP server config |
| `mcp-settings.php` | MCP settings |
| `my_domains.php` | User domains list |
| `other-apps.php` | Other apps page |
| `pricing.php` | Pricing page |
| `settings.php` | User settings |
| `setup-wizard.php` | Setup wizard entry point (3 steps) |
| `sign-in.php` | Sign-in page |
| `sitemap.php` | Sitemap management |
| `squarespace_schema.sql` | Database schema |
| `step-one.php` | Step 1: Squarespace site URL entry + verification |
| `step-three.php` | Step 3: Sitemap submission |
| `step-two.php` | Step 2: Domain verification + GSC property creation |
| `verify_domain.php` | Standalone domain verify endpoint |

### Admin files (18 page files + 17 core + 9 partials):
| File | Purpose |
|------|---------|
| `admin/active-stores.php` | Active stores list |
| `admin/app-add.php` | Add new app/store |
| `admin/app-edit.php` | Edit app/store |
| `admin/cancel-plan.php` | Cancel subscription |
| `admin/faq.php` | Admin FAQ |
| `admin/index.php` | Admin dashboard |
| `admin/instructions.php` | Admin instructions |
| `admin/logout.php` | Admin logout |
| `admin/our-apps.php` | Our apps listing |
| `admin/reviews.php` | Reviews management |
| `admin/settings.php` | Admin settings |
| `admin/sign-in.php` | Admin sign-in |
| `admin/store-view.php` | View single store |
| `admin/stores.php` | All stores list |
| `admin/test-stores.php` | Test stores |
| `admin/tickets.php` | Support tickets |
| `admin/unauthorized-stores.php` | Unauthorized stores |
| `admin/view-mail.php` | View emails |

### Admin Core (17 files):
`AuthController.php`, `CancelPlanManager.php`, `ChangePassword.php`, `EmailHelper.php`, `Faq.php`, `HeaderData.php`, `InstructionsManager.php`, `OtherAppsManager.php`, `ReviewManager.php`, `SessionManager.php`, `Stats.php`, `StoreApi.php`, `StoreManager.php`, `TicketManager.php`, `UpdateGoogleSettings.php`, `Update_stripe_Integration.php`, plus `email/` (delete-category, delete-template, save-category, save-template, send-test)

### API (4 files):
`api/google/create_property.php`, `api/google/verify_domain.php`, `api/google/check_property_status.php`, `api/google/submit_sitemap.php`

### Includes (17 files):
`config.php`, `credentials.php`, `db.php`, `helpers.php`, `mailer.php`, `plan_guard.php`, `schema_check.php`, `SessionManager.php`, `AuthController.php`, plus `google/` (disconnect, generate_meta, get_account, get_squarespace_site, load_token, maybe_start_backfill, refresh_token, save_token, token_manager)

### Partials (6 + layouts + reports):
`footer.php`, `head.php`, `instruction-popup.php`, `navbar.php`, `script.php`, `sidebar.php`, `ticket-submit-popup.php`, `layouts/layoutTop.php`, `layouts/layoutBottom.php`, `layouts/layoutTopnew.php`, `reports/kpis.php`, `reports/rich_results_summary.php`, `reports/top_pages.php`, `reports/traffic_sources.php`, `reports/trends.php`

### Scripts (5):
`cron_first_report.php`, `cron_page1_check.php`, `cron_payload_queue.php`, `cron_weekly_digest.php`, `refresh_all_tokens.php`

### Webhook (3):
`app-installed.php`, `app-uninstall.php`, `payment.php`

### Example (3):
`example/account_provision.php`, `example/register.php`, `example/register (1).php`

---

## 2. Comparison with Other Platforms

### PHP File Count (excluding vendor/)
| Platform | PHP Files | Status |
|----------|-----------|--------|
| Squarespace | 116 | Incomplete |
| Ecwid | 225 | Complete |
| Shopify | 256 | Complete |
| Wix | 277 | Complete |

Squarespace is missing approximately 100-160 files compared to Wix/Shopify/Ecwid.

### Missing Admin Files in Squarespace (present in Shopify/Wix):
1. `admin/email-template-add.php`
2. `admin/email-template-edit.php`
3. `admin/email-templates.php`
4. `admin/inactive-stores.php`
5. `admin/index_old.php`
6. `admin/mail-logs.php`
7. `admin/other-app-tracking.php`
8. `admin/paid-stores-old.php`
9. `admin/paid-stores.php`
10. `admin/stores-active.php`
11. `admin/stores-inactive.php`
12. `admin/stores-old.php`
13. `admin/storesold.php`
14. `admin/ticket-view.php`
15. `admin/ticket-detail.php`

### Missing Root Files in Squarespace (present in Shopify/Wix):
1. `action-center.php`
2. `current-plan.php`
3. `customer-reviews.php`
4. `magic-login.php`
5. `pricing-block.php`
6. `pricingOld.php`
7. `regex-report.php`
8. `reset-apply.php`
9. `rich-results-report.php`
10. `settings-old.php`
11. `test.php`
12. `thank-you.php`
13. `upgrade.php`
14. `why-first-report.php`
15. `wizard-instructions.php`

---

## 3. Setup Wizard Flow — Squarespace

The flow follows the same pattern as Wix/Shopify but with Squarespace-specific site verification.

### Step 0: Entry (setup-wizard.php)
- Checks `$_SESSION['instanceid']` (Squarespace site_id acting as instance_id)
- If missing -> redirects to `step-one.php`
- If present -> loads subscription status + site URL from `SquarespaceSite` table
- Displays 3-step progress bar

### Step 1: Squarespace Site Verification (step-one.php)
**User action:** Enter Squarespace website URL
**Flow:**
1. User submits URL via POST form
2. URL is normalized (lowercase, add http:// if missing, strip www)
3. App fetches homepage HTML via `file_get_contents()`
4. Checks for "squarespace" in HTML (case-insensitive regex)
5. Extracts site ID from `<meta name="generator" content="...squarespace...">` or falls back to host
6. If not Squarespace -> error message
7. If Squarespace -> saves/updates `SquarespaceSite` table with `instance_id`, `site_id`, `site_url`, `site_display_name`, `domain`
8. Stores `instanceid`, `instance_id`, `squarespace_site_id`, `squarespace_site_url` in session
9. Shows "Connect with Google" button

**Issue:** The "Connect with Google" button currently links to `setup-wizard.php?step=1` instead of `connect_google.php`. It should link to `connect_google.php`.

### connect_google.php (Google OAuth Start)
- Reads `GOOGLE_CLIENT_ID`, `GOOGLE_REDIRECT_URI`, `GSC_SCOPES` from config/env
- Sets `state` parameter to `$_SESSION['instanceid']`
- Opens Google OAuth URL in popup window
- On popup close -> redirects to `$returnTo` (default: dashboard.php)

### google_oauth_callback.php (OAuth Callback)
- Reads `state` = instanceId from URL
- Exchanges `code` for access/refresh tokens using Google API Client
- Loads encrypted `client_secret` from `google_settings` table
- Decrypts using `ENCRYPTION_KEY` (AES-256-CBC)
- Saves tokens to `google_accounts` table
- Redirects to `setup-wizard.php?step=2`

### Step 2: Domain Verification (step-two.php)
- Loads site URL from `SquarespaceSite` table
- Loads/creates `gsc_domain_verifications` record
- Server-side check via `api/google/check_property_status.php`
**Buttons:**
1. "Create Property Automatically" -> POST to `api/google/create_property.php` (PUT to Google Webmasters API)
2. "Verify Domain Automatically" -> POST to `api/google/verify_domain.php` (POST to Google Verification API)
3. Shows meta token to copy/paste into Squarespace Code Injection -> Header

### Step 3: Sitemap Submission (step-three.php)
- Requires Google connection + domain verification
- Default sitemap URL: `{site_url}sitemap.xml` (Squarespace auto-generates)
- POST to `api/google/submit_sitemap.php` (PUT to Google Webmasters sitemaps API)
- Redirects to dashboard

---

## 4. Google Search Console API Integration

| Operation | Endpoint | Method | File |
|-----------|----------|--------|------|
| Create property | `https://www.googleapis.com/webmasters/v3/sites/{url}` | PUT | `api/google/create_property.php` |
| Verify domain | `https://www.googleapis.com/webmasters/v3/webmasters/verifications` | POST | `api/google/verify_domain.php` |
| Check status | `https://www.googleapis.com/webmasters/v3/sites` | GET | `api/google/check_property_status.php` |
| Submit sitemap | `https://www.googleapis.com/webmasters/v3/sites/{url}/sitemaps/{sitemap}` | PUT | `api/google/submit_sitemap.php` |

Token management: `includes/google/token_manager.php` handles refresh/access token logic.

---

## 5. Database Schema (squarespace_schema.sql)

### Tables:
| Table | Purpose |
|-------|---------|
| `SquarespaceSite` | Main site table — `instance_id` = Squarespace site_id (acts as instance_id equivalent) |
| `gsc_domain_verifications` | GSC domain verification tracking (meta_token, verification_status) |
| `Squarespace_credentials` | Encrypted security keys (Stripe, Google, Squarespace) |
| `admin_settings` | Global admin panel settings |
| `gsc_page1_seen` | Page 1 breakthrough tracking |

### Key columns in SquarespaceSite:
- `instance_id` VARCHAR(191) UNIQUE — Squarespace site ID
- `site_id` VARCHAR(191) — Squarespace internal site ID
- `site_url` VARCHAR(2048) — Site URL
- `site_display_name` VARCHAR(191) — Display name
- `domain` VARCHAR(255) — Custom domain
- `is_active` TINYINT(1) — Active flag
- `access_token` TEXT — Squarespace API token
- `refresh_token` TEXT — Google OAuth refresh token
- `raw_decoded` JSON — Decoded webhook payload
- `shop_data` JSON — Squarespace site data

---

## 6. Phase-Wise Development Plan

### Phase 1: Foundation & File Parity
- [ ] Add missing admin files (email-templates, mail-logs, paid-stores, stores-active/inactive, ticket-view, etc.)
- [ ] Add missing root files (current-plan, customer-reviews, action-center, wizard-instructions, thank-you, upgrade, etc.)
- [ ] Add missing admin core classes (PaidStores, StoreExport, TicketsAdmin, etc.)
- [ ] Create `.env.example` file
- [ ] Run `composer install` to generate vendor/

### Phase 2: Setup Wizard Flow Fixes
- [ ] Fix step-one.php "Connect with Google" button -> link to `connect_google.php`
- [ ] Ensure "Continue with Google" is disabled until Squarespace site is verified (already implemented via session check)
- [ ] Add Stripe payment gateway integration (same as WordPress/Ecwid pattern)
- [ ] Add Google meta tag injection instructions for Squarespace Code Injection (Settings -> Advanced -> Code Injection -> Header)

### Phase 3: Admin Panel Parity
- [ ] Match Shopify/Wix admin panel exactly (same layout, same features)
- [ ] Add email template management (add, edit, list, send-test)
- [ ] Add paid/inactive stores management
- [ ] Add mail logs
- [ ] Add ticket detail view

### Phase 4: Testing & Deployment
- [ ] Push to GitHub: `https://github.com/pappukushwaha-dev/gsc-app-squarespace.git`
- [ ] Pull on server, import SQL, configure `.env`
- [ ] Test full flow: site entry -> Google connect -> property create -> verify -> sitemap -> dashboard
- [ ] Verify Stripe payments work
- [ ] Verify webhook endpoints (app-installed, app-uninstall, payment)

---

## 7. Git Push Commands

```powershell
cd D:\WorkSpace\GoogleSearchConsole\squarespace\gsc-app-squarespace
git init
git branch -M main
git add .
git commit -m "Initial commit: Squarespace GSC app"
git remote add origin https://github.com/pappukushwaha-dev/gsc-app-squarespace.git
git push -u origin main
```

## 8. Server Deployment

```bash
git clone https://github.com/pappukushwaha-dev/gsc-app-squarespace.git .
cp .env.example .env
# Edit .env with DB credentials, Google OAuth keys, Stripe keys, ENCRYPTION_KEY
mysql -u USER -p DB_NAME < squarespace_schema.sql
mysql -u USER -p DB_NAME < 01-squarespace-payload-queue.sql
mysql -u USER -p DB_NAME < 01-squarespace-other-app-clicks.sql
# Configure web server to point to this directory
# Visit: https://your-domain.com/gsc-app-squarespace/setup-wizard.php?step=1
```

---

## Summary

The Squarespace GSC app has the core GSC integration working (OAuth, property creation, domain verification, sitemap submission) but is missing ~100+ files compared to Wix/Shopify/Ecwid for full admin panel parity. The setup wizard flow is correct but needs the "Connect with Google" button fix in step-one.php. Next step is Phase 1: add missing files and create `.env.example`.
