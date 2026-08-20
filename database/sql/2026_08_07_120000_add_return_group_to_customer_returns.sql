-- Migration: 2026_08_07_120000_add_return_group_to_customer_returns
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_supply_returns' AND COLUMN_NAME = 'return_group') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_supply_returns') = 1,
  'ALTER TABLE `customer_supply_returns`
     ADD COLUMN `return_group` VARCHAR(40) NULL AFTER `return_number`,
     ADD INDEX `customer_supply_returns_return_group_index` (`return_group`)',
  'SELECT ''skip customer_supply_returns.return_group'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_returns' AND COLUMN_NAME = 'return_group') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_returns') = 1,
  'ALTER TABLE `customer_product_returns`
     ADD COLUMN `return_group` VARCHAR(40) NULL AFTER `return_number`,
     ADD INDEX `customer_product_returns_return_group_index` (`return_group`)',
  'SELECT ''skip customer_product_returns.return_group'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_07_120000_add_return_group_to_customer_returns',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_07_120000_add_return_group_to_customer_returns');

SELECT '2026_08_07_120000_add_return_group_to_customer_returns OK' AS result;
