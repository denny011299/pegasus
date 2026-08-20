-- product_variants.retail_unit — satuan eceran default per variant (unit_id). Nullable.
-- Idempotent.

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'retail_unit') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants') = 1,
  'ALTER TABLE `product_variants` ADD COLUMN `retail_unit` INT NULL AFTER `product_variant_stock`',
  'SELECT ''product_variants.retail_unit sudah ada'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_27_002744_add_retail_unit_to_product_variants_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_27_002744_add_retail_unit_to_product_variants_table');
