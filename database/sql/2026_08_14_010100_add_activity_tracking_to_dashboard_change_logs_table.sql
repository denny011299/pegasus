-- Migration: 2026_08_14_010100_add_activity_tracking_to_dashboard_change_logs_table
-- Idempotent (cek kolom dulu). Alternatif: php artisan migrate
--
-- GitHub #53: dashboard Changelog perlu mencatat kapan staf membuka sebuah
-- menu dan berapa lama, bukan cuma mutasi data. `activity_type` membedakan
-- baris "open" dari baris "change" (perilaku lama). `duration_seconds`
-- diisi belakangan, jadi nullable.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'dashboard_change_logs' AND COLUMN_NAME = 'activity_type') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'dashboard_change_logs') = 1,
  'ALTER TABLE `dashboard_change_logs`
     ADD COLUMN `activity_type` VARCHAR(20) NOT NULL DEFAULT ''change'' AFTER `module_key`',
  'SELECT ''skip dashboard_change_logs.activity_type'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'dashboard_change_logs' AND COLUMN_NAME = 'duration_seconds') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'dashboard_change_logs') = 1,
  'ALTER TABLE `dashboard_change_logs`
     ADD COLUMN `duration_seconds` INT UNSIGNED NULL DEFAULT NULL AFTER `meta`',
  'SELECT ''skip dashboard_change_logs.duration_seconds'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Catat di migrations (supaya artisan migrate tidak bentrok)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_14_010100_add_activity_tracking_to_dashboard_change_logs_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_14_010100_add_activity_tracking_to_dashboard_change_logs_table');

SELECT '2026_08_14_010100_add_activity_tracking_to_dashboard_change_logs_table OK' AS result;
