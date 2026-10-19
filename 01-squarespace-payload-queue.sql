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
   1. encharge_email_logs: the email queue
------------------------------------------------------------ */

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND COLUMN_NAME = 'instance_id') = 0,
  'ALTER TABLE encharge_email_logs ADD COLUMN instance_id VARCHAR(191) NOT NULL DEFAULT '' '' AFTER email',
  'SELECT ''encharge_email_logs.instance_id already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND COLUMN_NAME = 'email_type') = 0,
  'ALTER TABLE encharge_email_logs ADD COLUMN email_type VARCHAR(64) NOT NULL DEFAULT '' '' AFTER subject',
  'SELECT ''encharge_email_logs.email_type already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND COLUMN_NAME = 'website_url') = 0,
  'ALTER TABLE encharge_email_logs ADD COLUMN website_url VARCHAR(1000) NOT NULL DEFAULT '' '' AFTER website_id',
  'SELECT ''encharge_email_logs.website_url already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND COLUMN_NAME = 'payload') = 0,
  'ALTER TABLE encharge_email_logs ADD COLUMN payload JSON NULL AFTER flow_name',
  'SELECT ''encharge_email_logs.payload already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND COLUMN_NAME = 'is_payload_generated') = 0,
  'ALTER TABLE encharge_email_logs ADD COLUMN is_payload_generated VARCHAR(16) NOT NULL DEFAULT ''no'' AFTER payload',
  'SELECT ''encharge_email_logs.is_payload_generated already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND COLUMN_NAME = 'payload_attempts') = 0,
  'ALTER TABLE encharge_email_logs ADD COLUMN payload_attempts INT UNSIGNED NOT NULL DEFAULT 0 AFTER is_payload_generated',
  'SELECT ''encharge_email_logs.payload_attempts already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND COLUMN_NAME = 'payload_error') = 0,
  'ALTER TABLE encharge_email_logs ADD COLUMN payload_error TEXT NULL AFTER payload_attempts',
  'SELECT ''encharge_email_logs.payload_error already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND COLUMN_NAME = 'payload_done_at') = 0,
  'ALTER TABLE encharge_email_logs ADD COLUMN payload_done_at DATETIME NULL AFTER payload_error',
  'SELECT ''encharge_email_logs.payload_done_at already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

/* ------------------------------------------------------------
   2. SquarespaceSite digest_last_sent_at column (for weekly digest)
------------------------------------------------------------ */

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'SquarespaceSite'
    AND COLUMN_NAME = 'digest_last_sent_at') = 0,
  'ALTER TABLE SquarespaceSite ADD COLUMN digest_last_sent_at DATETIME NULL AFTER post_encharge',
  'SELECT ''SquarespaceSite.digest_last_sent_at already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

/* ------------------------------------------------------------
   3. Check after running
------------------------------------------------------------ */

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'encharge_email_logs'
  AND COLUMN_NAME IN ('instance_id','email_type','website_url','payload',
                      'is_payload_generated','payload_attempts','payload_error','payload_done_at')
ORDER BY ORDINAL_POSITION;

SELECT COLUMN_NAME FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'SquarespaceSite'
  AND COLUMN_NAME = 'digest_last_sent_at';
