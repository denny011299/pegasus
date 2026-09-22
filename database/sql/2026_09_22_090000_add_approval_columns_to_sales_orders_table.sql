-- Approval QC / Kepala Operasional + reject untuk sales_orders (Pengiriman).
-- Aman diulang.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'qc_approved_by') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders`
     ADD COLUMN `qc_approved_by` INT UNSIGNED NULL AFTER `acc_by`,
     ADD COLUMN `qc_approved_at` TIMESTAMP NULL AFTER `qc_approved_by`,
     ADD COLUMN `ops_approved_by` INT UNSIGNED NULL AFTER `qc_approved_at`,
     ADD COLUMN `ops_approved_at` TIMESTAMP NULL AFTER `ops_approved_by`,
     ADD COLUMN `rejected_by` INT UNSIGNED NULL AFTER `ops_approved_at`,
     ADD COLUMN `rejected_at` TIMESTAMP NULL AFTER `rejected_by`,
     ADD COLUMN `reject_stage` VARCHAR(10) NULL AFTER `rejected_at`,
     ADD COLUMN `reject_reason` TEXT NULL AFTER `reject_stage`',
  'SELECT ''skip sales_orders approval columns'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_22_090000_add_approval_columns_to_sales_orders_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_22_090000_add_approval_columns_to_sales_orders_table');
