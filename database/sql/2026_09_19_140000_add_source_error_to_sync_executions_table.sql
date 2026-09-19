-- Migration: 2026_09_19_140000_add_source_error_to_sync_executions_table
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sync_executions' AND COLUMN_NAME = 'source_error') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sync_executions') = 1,
  'ALTER TABLE `sync_executions` ADD COLUMN `source_error` TEXT NULL AFTER `notices`',
  'SELECT ''skip sync_executions.source_error'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_19_140000_add_source_error_to_sync_executions_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_19_140000_add_source_error_to_sync_executions_table');

SELECT '2026_09_19_140000_add_source_error_to_sync_executions_table OK' AS result;
