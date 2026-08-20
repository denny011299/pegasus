-- Migration: 2026_07_29_160000_add_received_unit_id_to_stock_transfer_details_table
-- Copied from stock_transfer_received_unit.sql (undated kept).

-- Simpan satuan hasil penerimaan tanpa mengubah satuan kirim.

SET @db := DATABASE();

SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'stock_transfer_details'
      AND COLUMN_NAME = 'received_unit_id'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `stock_transfer_details` ADD COLUMN `received_unit_id` INT UNSIGNED NULL AFTER `unit_id`',
    'SELECT ''stock_transfer_details.received_unit_id already exists'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_29_160000_add_received_unit_id_to_stock_transfer_details_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_29_160000_add_received_unit_id_to_stock_transfer_details_table');

SELECT '2026_07_29_160000_add_received_unit_id_to_stock_transfer_details_table OK' AS result;
