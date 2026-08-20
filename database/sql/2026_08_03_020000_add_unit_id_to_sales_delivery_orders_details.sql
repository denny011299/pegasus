-- Migration: 2026_08_03_020000_add_unit_id_to_sales_delivery_orders_details
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_delivery_orders_details' AND COLUMN_NAME = 'unit_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_delivery_orders_details') = 1,
  'ALTER TABLE `sales_delivery_orders_details` ADD COLUMN `unit_id` INT UNSIGNED NULL AFTER `sdod_qty`',
  'SELECT ''skip sales_delivery_orders_details.unit_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_03_020000_add_unit_id_to_sales_delivery_orders_details',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_03_020000_add_unit_id_to_sales_delivery_orders_details');

SELECT '2026_08_03_020000_add_unit_id_to_sales_delivery_orders_details OK' AS result;
