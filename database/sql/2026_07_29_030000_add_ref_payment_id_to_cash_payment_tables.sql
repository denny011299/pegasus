-- Migration: 2026_07_29_030000_add_ref_payment_id_to_cash_payment_tables
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cash_armadas' AND COLUMN_NAME = 'ref_payment_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cash_armadas') = 1,
  'ALTER TABLE `cash_armadas`
     ADD COLUMN `ref_payment_id` VARCHAR(100) NULL COMMENT ''Referensi pembayaran dari sistem eksternal'' AFTER `cr_id`,
     ADD UNIQUE INDEX `cash_armadas_ref_payment_id_unique` (`ref_payment_id`)',
  'SELECT ''skip cash_armadas.ref_payment_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cash_sales' AND COLUMN_NAME = 'ref_payment_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cash_sales') = 1,
  'ALTER TABLE `cash_sales`
     ADD COLUMN `ref_payment_id` VARCHAR(100) NULL COMMENT ''Referensi pembayaran dari sistem eksternal'' AFTER `cs_id`,
     ADD UNIQUE INDEX `cash_sales_ref_payment_id_unique` (`ref_payment_id`)',
  'SELECT ''skip cash_sales.ref_payment_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_29_030000_add_ref_payment_id_to_cash_payment_tables',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_29_030000_add_ref_payment_id_to_cash_payment_tables');

SELECT '2026_07_29_030000_add_ref_payment_id_to_cash_payment_tables OK' AS result;
