-- Migration: 2026_08_05_010000_add_resolved_by_system_to_productions_table
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'productions' AND COLUMN_NAME = 'resolved_by_system') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'productions') = 1,
  'ALTER TABLE `productions` ADD COLUMN `resolved_by_system` TINYINT(1) NOT NULL DEFAULT 0 AFTER `acc_by`',
  'SELECT ''skip productions.resolved_by_system'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_05_010000_add_resolved_by_system_to_productions_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_05_010000_add_resolved_by_system_to_productions_table');

SELECT '2026_08_05_010000_add_resolved_by_system_to_productions_table OK' AS result;
