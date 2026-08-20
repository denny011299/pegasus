-- Migration: 2026_08_08_120000_add_external_ref_id_to_staffs_table
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'staffs' AND COLUMN_NAME = 'external_ref_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'staffs') = 1,
  'ALTER TABLE `staffs`
     ADD COLUMN `external_ref_id` VARCHAR(191) NULL AFTER `staff_code`,
     ADD UNIQUE INDEX `staffs_external_ref_id_unique` (`external_ref_id`)',
  'SELECT ''skip staffs.external_ref_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_08_120000_add_external_ref_id_to_staffs_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_08_120000_add_external_ref_id_to_staffs_table');

SELECT '2026_08_08_120000_add_external_ref_id_to_staffs_table OK' AS result;
