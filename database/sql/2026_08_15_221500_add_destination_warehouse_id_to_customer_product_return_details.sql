-- Migration: 2026_08_15_221500_add_destination_warehouse_id_to_customer_product_return_details
-- Copied from add_destination_warehouse_id_to_customer_product_return_details.sql (undated kept).

-- Tujuan gudang eceran (stock transfer) pada detail pengembalian produk jadi.
-- Aman diulang.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_return_details' AND COLUMN_NAME = 'destination_warehouse_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_return_details') = 1,
  'ALTER TABLE `customer_product_return_details`
     ADD COLUMN `destination_warehouse_id` BIGINT UNSIGNED NULL AFTER `warehouse_id`,
     ADD INDEX `cpr_detail_dest_wh_idx` (`destination_warehouse_id`)',
  'SELECT ''skip destination_warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `customer_product_return_details`
SET `destination_warehouse_id` = `warehouse_id`
WHERE `destination_warehouse_id` IS NULL;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_return_details' AND INDEX_NAME = 'cpr_detail_item_unique') > 0,
  'ALTER TABLE `customer_product_return_details` DROP INDEX `cpr_detail_item_unique`',
  'SELECT ''skip drop cpr_detail_item_unique'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_return_details' AND INDEX_NAME = 'cpr_detail_item_dest_unique') = 0,
  'ALTER TABLE `customer_product_return_details`
     ADD UNIQUE KEY `cpr_detail_item_dest_unique` (`return_id`,`product_variant_id`,`unit_id`,`warehouse_id`,`destination_warehouse_id`)',
  'SELECT ''skip add cpr_detail_item_dest_unique'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_15_221500_add_destination_warehouse_id_to_customer_product_return_details',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_15_221500_add_destination_warehouse_id_to_customer_product_return_details');

SELECT '2026_08_15_221500_add_destination_warehouse_id_to_customer_product_return_details OK' AS result;
