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
