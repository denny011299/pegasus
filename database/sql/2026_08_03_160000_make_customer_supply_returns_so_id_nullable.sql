-- Migration: 2026_08_03_160000_make_customer_supply_returns_so_id_nullable
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_supply_returns'
     AND COLUMN_NAME = 'so_id' AND IS_NULLABLE = 'NO') = 1,
  'ALTER TABLE `customer_supply_returns` MODIFY `so_id` INT NULL',
  'SELECT ''skip customer_supply_returns.so_id nullable'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_03_160000_make_customer_supply_returns_so_id_nullable',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_03_160000_make_customer_supply_returns_so_id_nullable');

SELECT '2026_08_03_160000_make_customer_supply_returns_so_id_nullable OK' AS result;
