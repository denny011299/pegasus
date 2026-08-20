-- Migration: 2026_08_11_130000_add_ref_shipment_id_and_scheduled_status_to_sales_orders
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'ref_shipment_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders`
     ADD COLUMN `ref_shipment_id` VARCHAR(100) NULL COMMENT ''Referensi shipment dari sistem eksternal (POST /shipments/scheduled)'' AFTER `so_ref_number`,
     ADD UNIQUE INDEX `sales_orders_ref_shipment_id_unique` (`ref_shipment_id`)',
  'SELECT ''skip sales_orders.ref_shipment_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders` MODIFY `status` TINYINT NOT NULL DEFAULT 1 COMMENT ''1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong)''',
  'SELECT ''skip sales_orders.status comment'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_11_130000_add_ref_shipment_id_and_scheduled_status_to_sales_orders',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_11_130000_add_ref_shipment_id_and_scheduled_status_to_sales_orders');

SELECT '2026_08_11_130000_add_ref_shipment_id_and_scheduled_status_to_sales_orders OK' AS result;
