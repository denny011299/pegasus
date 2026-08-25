-- Approval QC / Kepala Operasional untuk Stock Transfer (utama → eceran).
-- Aman diulang.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_transfers' AND COLUMN_NAME = 'qc_approved_by') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_transfers') = 1,
  'ALTER TABLE `stock_transfers`
     ADD COLUMN `qc_approved_by` INT UNSIGNED NULL AFTER `acc_by`,
     ADD COLUMN `qc_approved_at` TIMESTAMP NULL AFTER `qc_approved_by`,
     ADD COLUMN `ops_approved_by` INT UNSIGNED NULL AFTER `qc_approved_at`,
     ADD COLUMN `ops_approved_at` TIMESTAMP NULL AFTER `ops_approved_by`',
  'SELECT ''skip stock_transfers approval columns'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_24_003700_add_approval_columns_to_stock_transfers_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_24_003700_add_approval_columns_to_stock_transfers_table');
