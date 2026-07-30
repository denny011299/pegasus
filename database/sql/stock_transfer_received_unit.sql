-- Simpan satuan hasil penerimaan tanpa mengubah satuan kirim.
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
