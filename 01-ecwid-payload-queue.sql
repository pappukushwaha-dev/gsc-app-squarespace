-- ============================================================
-- 01-ecwid-payload-queue.sql   (Ecwid)
--
-- Schema for the Encharge email log with real data, for the Ecwid app.
--
-- The webhook records that an email was sent; cron_payload_queue.php fills
-- in what it said. These columns are what the two of them and the admin
-- preview read and write.
--
-- Why this is not the Wix file with the name changed:
--   * Wix's log table already had website_url. This one does not, and the
--     webhook writes it, so it has to be added here BEFORE the new
--     webhook goes live. If it is not there the webhook's INSERT fails,
--     no row is ever written, and the only sign of it is a line in
--     webhook/encharge_error_log.txt.
--   * EcwidSite is missing digest_last_sent_at, which
--     scripts/cron_weekly_digest.php needs to apply its 6-day resend gate.
--   * The column types follow the ones this database already uses. Its
--     encharge_email_logs is utf8mb4_0900_ai_ci while EcwidSite is
--     utf8mb4_unicode_ci, and any query that compares the two across
--     tables needs an explicit COLLATE for that reason. Nothing here
--     changes either collation.
--
-- Safe to run more than once: every statement checks information_schema
-- first, so a column that is already present is skipped rather than
-- erroring. The guards are PREPARE/EXECUTE pairs, so every statement
-- "runs" on every pass - the test for a second run is a before/after
-- snapshot of the columns and indexes, not a count of executed lines.
--
-- Run it against this app's own database:
--   mysql -u USER -p ecwid-googlesearchconsole < 01-ecwid-payload-queue.sql
--
-- ============================================================

/* ------------------------------------------------------------
   1. encharge_email_logs: the queue

   instance_id  which site the email was about. The builder is called
                with it, and the admin lists by it. Without it the only
                way back to a site was the owner address, which is what
                the older previews did and why two sites on one address
                saw each other's mail.
   email_type   which email this was, set once per Encharge flow. The
                admin looks the template up by it, because the subject
                is copy and gets edited.
   website_url  which site the email was about, as the flow posted it.
   payload      every field that email rendered, as JSON.
   is_payload_generated
                no | yes | skipped | failed.
   payload_attempts
                raised before a build starts, so a run that dies mid-build
                does not look untouched and retryable forever.
   payload_error / payload_done_at
                why the last attempt failed, and when it succeeded.
------------------------------------------------------------ */

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND COLUMN_NAME = 'instance_id') = 0,
  'ALTER TABLE encharge_email_logs ADD COLUMN instance_id VARCHAR(191) NOT NULL DEFAULT '''' AFTER email',
  'SELECT ''encharge_email_logs.instance_id already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND COLUMN_NAME = 'email_type') = 0,
  'ALTER TABLE encharge_email_logs ADD COLUMN email_type VARCHAR(64) NOT NULL DEFAULT '''' AFTER subject',
  'SELECT ''encharge_email_logs.email_type already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

/* Not present in this database, unlike Wix's. The new webhook INSERTs it,
   so it must exist before that webhook is deployed. */
SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND COLUMN_NAME = 'website_url') = 0,
  'ALTER TABLE encharge_email_logs ADD COLUMN website_url VARCHAR(1000) NOT NULL DEFAULT '''' AFTER website_id',
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
  'ALTER TABLE encharge_email_logs ADD COLUMN payload_error VARCHAR(255) NOT NULL DEFAULT '''' AFTER payload_attempts',
  'SELECT ''encharge_email_logs.payload_error already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND COLUMN_NAME = 'payload_done_at') = 0,
  'ALTER TABLE encharge_email_logs ADD COLUMN payload_done_at DATETIME NULL DEFAULT NULL AFTER payload_error',
  'SELECT ''encharge_email_logs.payload_done_at already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

/* The queue's query is
     WHERE is_payload_generated = 'no' AND payload_attempts < 3
     ORDER BY id ASC LIMIT 20
   so this is the index that answers it. */
SET @s := IF((SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'encharge_email_logs'
    AND INDEX_NAME = 'idx_payload_queue') = 0,
  'ALTER TABLE encharge_email_logs ADD INDEX idx_payload_queue (is_payload_generated, payload_attempts, id)',
  'SELECT ''idx_payload_queue already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;


/* ------------------------------------------------------------
   2. Rows sent before this existed cannot be backfilled usefully.

   They carry no instance_id, and every one of these emails reads the last
   seven days, so rebuilding a two-month-old alert would put this week's
   numbers under an old date. They are marked skipped and the admin says so.

   They also stay visible: the admin's email page still matches a
   pre-instance_id row by the owner address, but only when that address
   belongs to exactly one store. Where two stores share an address the row
   is ambiguous and is withheld from both, rather than shown under both.
------------------------------------------------------------ */

UPDATE encharge_email_logs
SET is_payload_generated = 'skipped',
    payload_error        = 'sent before payload capture'
WHERE (instance_id IS NULL OR instance_id = '')
  AND is_payload_generated = 'no'
  AND payload IS NULL;


/* ------------------------------------------------------------
   3. email_templates: email_type

   The preview matches a template to an email by type first. Matching on
   the subject already failed on Wix: the template is named "Weekly digest
   [only recurring email]" while the flow sends "Weekly digest".
------------------------------------------------------------ */

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'email_templates'
    AND COLUMN_NAME = 'email_type') = 0,
  'ALTER TABLE email_templates ADD COLUMN email_type VARCHAR(64) NOT NULL DEFAULT ''''',
  'SELECT ''email_templates.email_type already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SET @s := IF((SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'email_templates'
    AND INDEX_NAME = 'idx_email_type') = 0,
  'ALTER TABLE email_templates ADD INDEX idx_email_type (email_type)',
  'SELECT ''idx_email_type already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;


/* ------------------------------------------------------------
   3b. EcwidSite.digest_last_sent_at

   scripts/cron_weekly_digest.php reads this column to decide whether a
   store was already sent a digest in the last six days. The cron names
   the ALTER in its own header as a one-time setup step, so check whether
   it was ever applied here:

     SHOW COLUMNS FROM EcwidSite LIKE 'digest_last_sent_at';

   It is missing in this database as measured, which means the digest cron
   has either never run or has been failing with "Unknown column
   's.digest_last_sent_at'". It is not optional: without it the digest
   cannot tell a first send from a repeat, and the digest is the one email
   that goes to every store in a single window.
------------------------------------------------------------ */

SET @s := IF((SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'EcwidSite'
    AND COLUMN_NAME = 'digest_last_sent_at') = 0,
  'ALTER TABLE EcwidSite ADD COLUMN digest_last_sent_at DATETIME NULL DEFAULT NULL',
  'SELECT ''EcwidSite.digest_last_sent_at already present'' AS note');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;


/* ------------------------------------------------------------
   4. Stamp email_type on the templates.

   The value must match KNOWN_TYPES in scripts/cron_payload_queue.php and the
   email_type key the Encharge flow sends, exactly: lowercase, underscore.

   Run the SELECT first. As measured, this database carries only the five
   onboarding templates and none of the four GSC ones - the SELECT will
   show them with an empty email_type and nothing below will match until
   section 5 has brought the four over. If a template is not named the way
   the names below guess, it will be left with an empty email_type and the
   preview will say no template was found for that type - which is the
   signal to add the real name here or set it by hand.
------------------------------------------------------------ */

SELECT id, name, email_type FROM email_templates ORDER BY id;

UPDATE email_templates SET email_type = 'first_report'
WHERE (email_type IS NULL OR email_type = '')
  AND name IN ('First Report', 'First report', 'First Report [only once]');

UPDATE email_templates SET email_type = 'crash_alert'
WHERE (email_type IS NULL OR email_type = '')
  AND name IN ('Crash Alert', 'Crash alert', 'Crash Alert [only once]');

UPDATE email_templates SET email_type = 'page1_breakthrough'
WHERE (email_type IS NULL OR email_type = '')
  AND name IN ('Page 1 Breakthrough', 'Page-1 Breakthrough', 'Page 1 breakthrough',
               'Page-1 breakthrough', 'Page-1 breakthrough - Ecwid');

UPDATE email_templates SET email_type = 'weekly_digest'
WHERE (email_type IS NULL OR email_type = '')
  AND name IN ('Weekly digest', 'Weekly digest [only recurring email]', 'Weekly Digest');


/* ------------------------------------------------------------
   5. The four emails themselves.

   As measured, this database has the onboarding and ticket templates but
   not the four GSC ones: First Report, Crash Alert, Page 1 Breakthrough
   and Weekly Digest. Their bodies are not reproduced here, because a body
   written from memory would render but would not be the email customers
   receive.

   Copy them from the HL database, where they already exist:

     mysqldump -u USER -p --no-create-info --skip-extended-insert \
       --where="email_type IN ('first_report','crash_alert','page1_breakthrough','weekly_digest')" \
       HL_DB email_templates > gsc_templates.sql

   Two things to check in that file before importing it here.

   FIRST: category_id. This table has a foreign key on it:

       CONSTRAINT email_templates_ibfk_1 FOREIGN KEY (category_id)
         REFERENCES email_categories (id)

   so the imported rows must name a category that exists in THIS database.
   The category ids are not the same everywhere - this database has 7, 8,
   10 and 11 while another app may not - and an id that is not present
   here makes the whole INSERT fail with a foreign key error.

   Find a category to use:

     SELECT id, name FROM email_categories ORDER BY id;

   then either edit the category_id in the file, or import into a scratch
   table with no constraint and copy across, which is safer when several
   rows share one category:

     CREATE TABLE gsc_templates_scratch LIKE email_templates;
     ALTER TABLE gsc_templates_scratch DROP FOREIGN KEY email_templates_ibfk_1;
     -- import gsc_templates.sql into the scratch table
     INSERT INTO email_templates (name, category_id, subject, body, email_type)
     SELECT name, <an id from email_categories>, subject, body, email_type
     FROM gsc_templates_scratch;
     DROP TABLE gsc_templates_scratch;

   SECOND: the id column. If the dump names it, importing assigns these
   rows the ids they had in the other database, which can collide with
   rows that already exist here. Either drop the id column from the INSERT
   list, or use the scratch-table form above, which never names it.

   After importing, run section 4 again: it stamps the type on whatever
   arrived and skips anything already stamped. If email_type came across
   in the dump, section 4 leaves those rows alone.

   Without these four rows the feature still works - the webhook stores
   the send, the queue builds the payload and the admin lists it - but
   "View details" shows "No template found for type ..." instead of the
   email.
------------------------------------------------------------ */


/*
   The four emails' subjects are also worth checking once they are here.
   The First Report subject was wrong in the Wix database:

     Your first Search report for {{site_domain}} is ready

   site_domain is not an Encharge field, so the real email sends it empty
   or literal. It should be:

     Your first Search report for {{person.googlesearch_domain_name}} is ready

   Check for it and any other {{site_domain}} with:

     SELECT id, email_type, LEFT(subject, 120) AS subject FROM email_templates
     WHERE subject LIKE '%{{site_domain}}%' OR body LIKE '%{{site_domain}}%';

   and correct it with a targeted REPLACE, which leaves the rest of the
   body untouched:

     UPDATE email_templates
     SET subject = REPLACE(subject, '{{site_domain}}', '{{person.googlesearch_domain_name}}'),
         body    = REPLACE(body,    '{{site_domain}}', '{{person.googlesearch_domain_name}}')
     WHERE email_type IN ('first_report','crash_alert','page1_breakthrough','weekly_digest');
*/


/* ------------------------------------------------------------
   6. gsc_page1_seen

   scripts/cron_page1_check.php tracks the breakthroughs it has already
   reported in this table, and creates it at runtime if it is missing. It
   is missing in this database as measured. It is declared here as well so
   the table is in place before the cron first runs, and so its shape is
   reviewable. The definition is the one in that file's header.
------------------------------------------------------------ */

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


/* ------------------------------------------------------------
   7. Check after running.

   Every column the feature needs should show, and a fresh install should
   have every old row marked skipped:
------------------------------------------------------------ */

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'encharge_email_logs'
  AND COLUMN_NAME IN ('instance_id','email_type','website_url','payload',
                      'is_payload_generated','payload_attempts','payload_error','payload_done_at')
ORDER BY ORDINAL_POSITION;

SELECT is_payload_generated, COUNT(*) AS rows_count
FROM encharge_email_logs GROUP BY is_payload_generated;

SELECT id, email_type, name FROM email_templates
WHERE email_type <> '' ORDER BY email_type;

-- The digest needs this one too:
SELECT COLUMN_NAME FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'EcwidSite'
  AND COLUMN_NAME = 'digest_last_sent_at';
