-- Staff QC & Gudang pada dokumen pengembalian (staff_id, bukan nama).
-- Aman diulang.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_supply_returns' AND COLUMN_NAME = 'qc_staff_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_supply_returns') = 1,
  'ALTER TABLE `customer_supply_returns`
     ADD COLUMN `qc_staff_id` INT NULL AFTER `created_by`,
     ADD INDEX `csr_qc_staff_id_index` (`qc_staff_id`)',
  'SELECT ''skip csr qc_staff_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_returns' AND COLUMN_NAME = 'qc_staff_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_returns') = 1,
  'ALTER TABLE `customer_product_returns`
     ADD COLUMN `qc_staff_id` INT NULL AFTER `created_by`,
     ADD INDEX `cpr_qc_staff_id_index` (`qc_staff_id`)',
  'SELECT ''skip cpr qc_staff_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
