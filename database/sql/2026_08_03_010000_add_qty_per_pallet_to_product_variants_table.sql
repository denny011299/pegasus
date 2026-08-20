-- Migration: 2026_08_03_010000_add_qty_per_pallet_to_product_variants_table
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'qty_per_pallet') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants') = 1,
  'ALTER TABLE `product_variants` ADD COLUMN `qty_per_pallet` INT UNSIGNED NULL AFTER `unit_id`',
  'SELECT ''skip product_variants.qty_per_pallet'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_03_010000_add_qty_per_pallet_to_product_variants_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_03_010000_add_qty_per_pallet_to_product_variants_table');

SELECT '2026_08_03_010000_add_qty_per_pallet_to_product_variants_table OK' AS result;
