-- Migration: 2026_07_22_122200_add_last_active_warehouse_id_to_staffs_table
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'staffs' AND COLUMN_NAME = 'last_active_warehouse_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'staffs') = 1,
  'ALTER TABLE `staffs`
     ADD COLUMN `last_active_warehouse_id` BIGINT UNSIGNED NULL AFTER `role_id`,
     ADD INDEX `staffs_last_active_warehouse_id_index` (`last_active_warehouse_id`)',
  'SELECT ''skip staffs.last_active_warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_22_122200_add_last_active_warehouse_id_to_staffs_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_22_122200_add_last_active_warehouse_id_to_staffs_table');

SELECT '2026_07_22_122200_add_last_active_warehouse_id_to_staffs_table OK' AS result;
