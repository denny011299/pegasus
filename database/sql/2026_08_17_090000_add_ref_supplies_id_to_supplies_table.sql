-- Migration: 2026_08_17_090000_add_ref_supplies_id_to_supplies_table
-- Copied from add_ref_supplies_id_to_supplies.sql (undated kept).

-- Rujukan eksternal (sistem PMO) untuk bahan mentah/kemasan, sama pola dengan
-- units.ref_unit_id / products.ref_product_id. Aman diulang.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'ref_supplies_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies') = 1,
  'ALTER TABLE `supplies`
     ADD COLUMN `ref_supplies_id` INT NULL COMMENT ''supplies_id pada sistem PMO'' AFTER `supplies_id`,
     ADD UNIQUE INDEX `supplies_ref_supplies_id_unique` (`ref_supplies_id`)',
  'SELECT ''skip supplies ref_supplies_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_17_090000_add_ref_supplies_id_to_supplies_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_17_090000_add_ref_supplies_id_to_supplies_table');

SELECT '2026_08_17_090000_add_ref_supplies_id_to_supplies_table OK' AS result;
