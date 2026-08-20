-- Migration: 2026_08_05_020000_add_source_cgd_id_to_cash_armadas_table
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cash_armadas' AND COLUMN_NAME = 'source_cgd_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cash_armadas') = 1,
  'ALTER TABLE `cash_armadas` ADD COLUMN `source_cgd_id` INT UNSIGNED NULL AFTER `cash_id`',
  'SELECT ''skip cash_armadas.source_cgd_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_05_020000_add_source_cgd_id_to_cash_armadas_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_05_020000_add_source_cgd_id_to_cash_armadas_table');

SELECT '2026_08_05_020000_add_source_cgd_id_to_cash_armadas_table OK' AS result;
