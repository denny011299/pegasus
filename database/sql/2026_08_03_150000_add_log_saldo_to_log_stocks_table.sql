-- Migration: 2026_08_03_150000_add_log_saldo_to_log_stocks_table
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'log_stocks' AND COLUMN_NAME = 'log_saldo') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'log_stocks') = 1,
  'ALTER TABLE `log_stocks` ADD COLUMN `log_saldo` DECIMAL(18,4) NULL AFTER `log_jumlah`',
  'SELECT ''skip log_stocks.log_saldo'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_03_150000_add_log_saldo_to_log_stocks_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_03_150000_add_log_saldo_to_log_stocks_table');

SELECT '2026_08_03_150000_add_log_saldo_to_log_stocks_table OK' AS result;
