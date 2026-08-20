-- Migration: 2026_08_17_090100_make_customer_return_details_warehouse_id_nullable
-- Copied from make_customer_return_details_warehouse_id_nullable.sql (undated kept).

-- warehouse_id jadi nullable pada kedua tabel detail pengembalian pelanggan — dibutuhkan
-- endpoint External API baru POST /shipments/returns (GitHub #58), yang membuat baris
-- pengembalian tanpa gudang tujuan (diisi staf lewat halaman admin sebelum ACC). Aman diulang.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_supply_return_details'
     AND COLUMN_NAME = 'warehouse_id' AND IS_NULLABLE = 'NO') = 1,
  'ALTER TABLE `customer_supply_return_details` MODIFY `warehouse_id` INT NULL',
  'SELECT ''skip csr_detail warehouse_id nullable'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_return_details'
     AND COLUMN_NAME = 'warehouse_id' AND IS_NULLABLE = 'NO') = 1,
  'ALTER TABLE `customer_product_return_details` MODIFY `warehouse_id` INT NULL',
  'SELECT ''skip cpr_detail warehouse_id nullable'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_17_090100_make_customer_return_details_warehouse_id_nullable',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_17_090100_make_customer_return_details_warehouse_id_nullable');

SELECT '2026_08_17_090100_make_customer_return_details_warehouse_id_nullable OK' AS result;
