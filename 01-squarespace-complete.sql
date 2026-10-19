-- ============================================================
-- 01-squarespace-complete.sql   (Squarespace)
-- COMPLETE database schema for Squarespace GSC App
-- Safe to run multiple times (IF NOT EXISTS guards)
-- Run: mysql -u USER -p squarespace-googlesearchconsole < 01-squarespace-complete.sql
-- ============================================================

CREATE TABLE IF NOT EXISTS SquarespaceSite (
    id               INT NOT NULL AUTO_INCREMENT,
    instance_id      VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    site_id          VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    site_url         VARCHAR(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    site_display_name VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    domain           VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    event_type       VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    app_id           VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    access_token     TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    access_token_expires_at DATETIME DEFAULT NULL,
    refresh_token    TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    owner_email      VARCHAR(320) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    raw_decoded      JSON DEFAULT NULL,
    shop_data        JSON DEFAULT NULL,
    identity         JSON DEFAULT NULL,
    is_active        TINYINT(1) DEFAULT 1,
    lead_flag        TINYINT(1) NOT NULL DEFAULT 0,
    post_encharge    TINYINT NOT NULL DEFAULT 0,
    digest_last_sent_at DATETIME NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_instance (instance_id),
    KEY idx_domain (domain),
    KEY idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gsc_domain_verifications (
    id                    INT NOT NULL AUTO_INCREMENT,
    instance_id           VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    site_url              VARCHAR(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    verification_status   VARCHAR(32) DEFAULT "pending",
    meta_token            VARCHAR(255) DEFAULT NULL,
    meta_tag              TEXT DEFAULT NULL,
    verification_verified_at DATETIME DEFAULT NULL,
    verification_checked_at  DATETIME DEFAULT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_instance (instance_id),
    KEY idx_status (verification_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Squarespace_credentials (
    id           INT NOT NULL AUTO_INCREMENT,
    key_name     VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    value        TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    encrypted    TINYINT(1) NOT NULL DEFAULT 0,
    type         VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    created_at   DATETIME(3) DEFAULT CURRENT_TIMESTAMP(3),
    updated_at   DATETIME(3) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY key_name (key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_settings (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    key_name   VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    value      TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY key_name (key_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT="Stores global admin panel settings.";

CREATE TABLE IF NOT EXISTS gsc_page1_seen (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    instance_id VARCHAR(191) NOT NULL,
    query_hash  CHAR(32) NOT NULL,
    query       VARCHAR(500) NOT NULL DEFAULT '',
    position    DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    created_at  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_inst_query (instance_id, query_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS app_subscriptions (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    instance_id    VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    plan_name      VARCHAR(100) DEFAULT NULL,
    status         VARCHAR(32) DEFAULT "pending",
    started_at     DATETIME DEFAULT NULL,
    expires_at     DATETIME DEFAULT NULL,
    created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_instance (instance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS google_accounts (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    instance_id     VARCHAR(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
    access_token    TEXT,
    refresh_token   TEXT,
    token_expires_at DATETIME DEFAULT NULL,
    connected       TINYINT(1) DEFAULT 0,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_instance (instance_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_templates (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name        VARCHAR(255) NOT NULL,
    subject     VARCHAR(500) NOT NULL,
    category    VARCHAR(64) DEFAULT "general",
    body        TEXT NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_tickets (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_name VARCHAR(255) DEFAULT NULL,
    email         VARCHAR(320) NOT NULL,
    subject       VARCHAR(500) NOT NULL,
    message       TEXT NOT NULL,
    status        VARCHAR(32) DEFAULT "open",
    platform      VARCHAR(64) DEFAULT "squarespace",
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_status (status),
    KEY idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_reviews (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_name VARCHAR(255) DEFAULT NULL,
    rating       TINYINT UNSIGNED DEFAULT 5,
    comment      TEXT,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS action_center (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    instance_id VARCHAR(191) NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    status      VARCHAR(32) DEFAULT "pending",
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_instance (instance_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS email_logs (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    recipient   VARCHAR(320) NOT NULL,
    subject     VARCHAR(500) NOT NULL,
    status      VARCHAR(32) DEFAULT "sent",
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS encharge_email_logs (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    instance_id         VARCHAR(191) NOT NULL DEFAULT '',
    email               VARCHAR(320) NOT NULL DEFAULT '',
    email_type          VARCHAR(64) NOT NULL DEFAULT '',
    website_id          INT UNSIGNED NOT NULL DEFAULT 0,
    website_url         VARCHAR(1000) NOT NULL DEFAULT '',
    flow_name           VARCHAR(255) DEFAULT NULL,
    payload             JSON NULL,
    is_payload_generated VARCHAR(16) NOT NULL DEFAULT 'no',
    payload_attempts    INT UNSIGNED NOT NULL DEFAULT 0,
    payload_error       TEXT NULL,
    payload_done_at     DATETIME NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_instance (instance_id),
    KEY idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS other_app_clicks (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    instance_id    VARCHAR(191) NOT NULL DEFAULT '',
    email          VARCHAR(320) NOT NULL DEFAULT '',
    other_app_id   INT UNSIGNED NOT NULL DEFAULT 0,
    app_name       VARCHAR(191) NOT NULL DEFAULT '',
    source_platform VARCHAR(20) NOT NULL DEFAULT 'squarespace',
    clicked_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_app_time (other_app_id, clicked_at),
    KEY idx_instance (instance_id),
    KEY idx_clicked_at (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO admin_settings (key_name, value) VALUES
('site_name', 'Google Search Console - Squarespace'),
('support_email', 'support@yourdomain.com'),
('currency', 'USD'),
('timezone', 'Asia/Kolkata'),
('maintenance_mode', '0');

SHOW TABLES;
SELECT 'Database setup complete!' AS status;
