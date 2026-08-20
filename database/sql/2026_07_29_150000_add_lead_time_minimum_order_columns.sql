-- Migration: 2026_07_29_150000_add_lead_time_minimum_order_columns
-- Idempotent. (subset of undated lead_time_minimum_order.sql matching this migration)

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants' AND COLUMN_NAME = 'lead_time_days') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants') = 1,
  'ALTER TABLE `supplies_variants` ADD COLUMN `lead_time_days` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `supplies_variant_price`',
  'SELECT ''skip supplies_variants.lead_time_days'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants' AND COLUMN_NAME = 'safety_stock') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants') = 1,
  'ALTER TABLE `supplies_variants` ADD COLUMN `safety_stock` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `lead_time_days`',
  'SELECT ''skip supplies_variants.safety_stock'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'lead_time_days') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants') = 1,
  'ALTER TABLE `product_variants` ADD COLUMN `lead_time_days` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `product_variant_alert`',
  'SELECT ''skip product_variants.lead_time_days'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_29_150000_add_lead_time_minimum_order_columns',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_29_150000_add_lead_time_minimum_order_columns');

SELECT '2026_07_29_150000_add_lead_time_minimum_order_columns OK' AS result;
