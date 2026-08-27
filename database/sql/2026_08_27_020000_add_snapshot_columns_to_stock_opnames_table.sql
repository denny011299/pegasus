-- Migration: 2026_08_27_020000_add_snapshot_columns_to_stock_opnames_table
-- Idempotent (cek kolom dulu). Alternatif: php artisan migrate
--
-- Snapshot header dokumen versi baru. sto_staff_name diisi saat dokumen keluar dari draft,
-- sto_acc_name + sto_decided_at saat diputuskan. Dokumen lama membiarkannya NULL selamanya.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames' AND COLUMN_NAME = 'sto_staff_name') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames') = 1,
  'ALTER TABLE `stock_opnames`
     ADD COLUMN `sto_staff_name` VARCHAR(255) NULL AFTER `staff_id`',
  'SELECT ''skip stock_opnames.sto_staff_name'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames' AND COLUMN_NAME = 'sto_acc_name') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames') = 1,
  'ALTER TABLE `stock_opnames`
     ADD COLUMN `sto_acc_name` VARCHAR(255) NULL AFTER `acc_by`',
  'SELECT ''skip stock_opnames.sto_acc_name'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames' AND COLUMN_NAME = 'sto_decided_at') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames') = 1,
  'ALTER TABLE `stock_opnames`
     ADD COLUMN `sto_decided_at` TIMESTAMP NULL AFTER `sto_acc_name`',
  'SELECT ''skip stock_opnames.sto_decided_at'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_27_020000_add_snapshot_columns_to_stock_opnames_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_27_020000_add_snapshot_columns_to_stock_opnames_table');

SELECT '2026_08_27_020000_add_snapshot_columns_to_stock_opnames_table OK' AS result;
