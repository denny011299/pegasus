-- Migration: 2026_07_26_020200_add_notices_to_sync_executions_table
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sync_executions' AND COLUMN_NAME = 'notices') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sync_executions') = 1,
  'ALTER TABLE `sync_executions` ADD COLUMN `notices` JSON NULL AFTER `errors`',
  'SELECT ''skip sync_executions.notices'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_26_020200_add_notices_to_sync_executions_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_26_020200_add_notices_to_sync_executions_table');

SELECT '2026_07_26_020200_add_notices_to_sync_executions_table OK' AS result;
