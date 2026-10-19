-- ============================================================
-- 01-squarespace-payload-queue.sql   (Squarespace)
--
-- Schema for the Encharge email log queue, for the Squarespace app.
-- Same structure as BigCommerce/Wix payload queue, adapted for Squarespace.
--
-- Safe to run more than once: every statement checks information_schema
-- first, so a column that is already present is skipped rather than
-- erroring.
--
-- Run it against this app's own database:
--   mysql -u USER -p squarespace_googlesearchconsole < 01-squarespace-payload-queue.sql
--
-- ============================================================

/* ------------------------------------------------------------
   1. SquarespaceSite: main site table
------------------------------------------------------------ */

SET @s := IF((SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'SquarespaceSite') = 0,
  'CREATE TABLE SquarespaceSite (
    id               INT NOT NULL AUTO_INCREMENT,
    shop_id      VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT "Squarespace site ID (acts as shop_id)",
    site_id          VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT "Squarespace internal site ID",
    site_url         VARCHAR(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT "Site URL",
    site_display_name VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT "Store/display name",
    domain           VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT "Custom domain",
    event_type       VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT "install | update | uninstall",
    app_id           VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT "Squarespace App ID",
    access_token     TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT "Squarespace API access token",
    access_token_expires_at DATETIME DEFAULT NULL COMMENT "Token expiry",
    refresh_token    TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT "Google OAuth refresh token",
    owner_email      VARCHAR(320) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT "Site owner email",
    raw_decoded      JSON DEFAULT NULL COMMENT "Decoded webhook payload",
    shop_data        JSON DEFAULT NULL COMMENT "Squarespace site data",
    identity         JSON DEFAULT NULL COMMENT "Identity data",
    is_active        TINYINT(1) DEFAULT 1 COMMENT "1 = active, 0 = inactive",
    lead_flag        TINYINT(1) NOT NULL DEFAULT 0 COMMENT "0=Pending, 1=Inserted, 2=Failed",
    post_encharge    TINYINT NOT NULL DEFAULT 0 COMMENT "0 = not post, 1 = post data",
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_instance (shop_id),
    KEY idx_domain (domain),
    KEY idx_created (created_at DESC)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
  'SELECT ''SquarespaceSite already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

/* ------------------------------------------------------------
   2. gsc_domain_verifications: domain verification tracking
------------------------------------------------------------ */

SET @s := IF((SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gsc_domain_verifications') = 0,
  'CREATE TABLE gsc_domain_verifications (
    id                    INT NOT NULL AUTO_INCREMENT,
    shop_id           VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT "Squarespace site_id / shop_id",
    site_url              VARCHAR(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT "Site URL to verify",
    verification_status   VARCHAR(32) DEFAULT "pending" COMMENT "pending | verified | failed",
    meta_token            VARCHAR(255) DEFAULT NULL COMMENT "Google verification meta token",
    meta_tag              TEXT DEFAULT NULL COMMENT "Full meta tag HTML",
    verification_verified_at DATETIME DEFAULT NULL COMMENT "When verified",
    verification_checked_at  DATETIME DEFAULT NULL COMMENT "Last check time",
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_instance (shop_id),
    KEY idx_status (verification_status)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
  'SELECT ''gsc_domain_verifications already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

/* ------------------------------------------------------------
   3. Squarespace_credentials: encrypted security keys
------------------------------------------------------------ */

SET @s := IF((SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'Squarespace_credentials') = 0,
  'CREATE TABLE Squarespace_credentials (
    id           INT NOT NULL AUTO_INCREMENT,
    key_name     VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT "Setting key name",
    value        TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT "Encrypted value",
    encrypted    TINYINT(1) NOT NULL DEFAULT 0 COMMENT "0 = plaintext, 1 = encrypted",
    type         VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT "Type: stripe|google|squarespace",
    created_at   DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
    updated_at   DATETIME(3) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY key_name (key_name)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
  'SELECT ''Squarespace_credentials already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

/* ------------------------------------------------------------
   4. admin_settings: global admin panel settings
------------------------------------------------------------ */

SET @s := IF((SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_settings') = 0,
  'CREATE TABLE admin_settings (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT "Unique ID for the setting.",
    key_name   VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT "Setting key name",
    value      TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT "Setting value",
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT "Created timestamp.",
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT "Last updated.",
    PRIMARY KEY (id),
    UNIQUE KEY key_name (key_name)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Stores global admin panel settings."',
  'SELECT ''admin_settings already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

/* ------------------------------------------------------------
   5. gsc_page1_seen: page 1 breakthrough tracking
------------------------------------------------------------ */

CREATE TABLE IF NOT EXISTS gsc_page1_seen (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    shop_id VARCHAR(191) NOT NULL,
    query_hash  CHAR(32) NOT NULL,
    query       VARCHAR(500) NOT NULL DEFAULT '',
    position    DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_inst_query (shop_id, query_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ------------------------------------------------------------
   6. Check after running
------------------------------------------------------------ */

SHOW TABLES LIKE 'SquarespaceSite';
SHOW TABLES LIKE 'gsc_domain_verifications';
SHOW TABLES LIKE 'Squarespace_credentials';
SHOW TABLES LIKE 'admin_settings';
SHOW TABLES LIKE 'gsc_page1_seen';
