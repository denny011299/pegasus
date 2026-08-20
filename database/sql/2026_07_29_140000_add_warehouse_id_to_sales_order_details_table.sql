-- sales_order_details.warehouse_id — gudang eceran per baris pengiriman.
-- Idempotent + backfill dari header.

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_order_details' AND COLUMN_NAME = 'warehouse_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_order_details') = 1,
  'ALTER TABLE `sales_order_details`
     ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL AFTER `unit_id`,
     ADD INDEX `sales_order_details_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''sales_order_details.warehouse_id sudah ada'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `sales_order_details` sod
INNER JOIN `sales_orders` so ON so.so_id = sod.so_id
INNER JOIN `product_variants` pv ON pv.product_variant_id = sod.product_variant_id
SET sod.warehouse_id = so.retail_warehouse_id
WHERE sod.warehouse_id IS NULL
  AND so.retail_warehouse_id IS NOT NULL
  AND pv.retail_unit > 0
  AND sod.unit_id = pv.retail_unit;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_29_140000_add_warehouse_id_to_sales_order_details_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_29_140000_add_warehouse_id_to_sales_order_details_table');
