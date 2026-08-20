-- Migration: 2026_07_29_170000_convert_supplies_safety_stock_to_integer
-- Idempotent MODIFY.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants' AND COLUMN_NAME = 'safety_stock') = 1,
  'ALTER TABLE `supplies_variants` MODIFY `safety_stock` INT UNSIGNED NOT NULL DEFAULT 0',
  'SELECT ''skip supplies_variants.safety_stock modify'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_29_170000_convert_supplies_safety_stock_to_integer',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_29_170000_convert_supplies_safety_stock_to_integer');

SELECT '2026_07_29_170000_convert_supplies_safety_stock_to_integer OK' AS result;
