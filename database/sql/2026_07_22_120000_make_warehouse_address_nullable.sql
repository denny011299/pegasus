-- Migration: 2026_07_22_120000_make_warehouse_address_nullable
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'warehouses'
     AND COLUMN_NAME = 'warehouse_address' AND IS_NULLABLE = 'NO') = 1,
  'ALTER TABLE `warehouses` MODIFY `warehouse_address` TEXT NULL',
  'SELECT ''skip warehouses.warehouse_address nullable'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_22_120000_make_warehouse_address_nullable',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_22_120000_make_warehouse_address_nullable');

SELECT '2026_07_22_120000_make_warehouse_address_nullable OK' AS result;
