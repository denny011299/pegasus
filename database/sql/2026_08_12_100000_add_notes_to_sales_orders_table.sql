-- Migration: 2026_08_12_100000_add_notes_to_sales_orders_table
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'notes') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders` ADD COLUMN `notes` TEXT NULL COMMENT ''Catatan shipment, diisi lewat POST /shipments/shipped (body.notes)'' AFTER `ref_shipment_id`',
  'SELECT ''skip sales_orders.notes'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_12_100000_add_notes_to_sales_orders_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_12_100000_add_notes_to_sales_orders_table');

SELECT '2026_08_12_100000_add_notes_to_sales_orders_table OK' AS result;
