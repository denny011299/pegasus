-- Migration: 2026_07_23_100000_add_is_main_warehouse_to_warehouse_types_table
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'warehouse_types' AND COLUMN_NAME = 'is_main_warehouse') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'warehouse_types') = 1,
  'ALTER TABLE `warehouse_types`
     ADD COLUMN `is_main_warehouse` TINYINT NOT NULL DEFAULT 0 COMMENT ''1 = tipe gudang utama (hanya 1)'' AFTER `warehouse_type_name`,
     ADD INDEX `warehouse_types_is_main_warehouse_index` (`is_main_warehouse`)',
  'SELECT ''skip warehouse_types.is_main_warehouse'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_23_100000_add_is_main_warehouse_to_warehouse_types_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_23_100000_add_is_main_warehouse_to_warehouse_types_table');

SELECT '2026_07_23_100000_add_is_main_warehouse_to_warehouse_types_table OK' AS result;
