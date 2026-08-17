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
