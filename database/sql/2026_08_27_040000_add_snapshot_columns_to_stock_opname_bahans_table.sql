-- Migration: 2026_08_27_040000_add_snapshot_columns_to_stock_opname_bahans_table
-- Idempotent (cek kolom dulu). Alternatif: php artisan migrate
--
-- Snapshot header dokumen Stock Opname Bahan versi baru. Kembaran
-- 2026_08_27_020000_add_snapshot_columns_to_stock_opnames_table (Produk).

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans' AND COLUMN_NAME = 'stob_staff_name') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans') = 1,
  'ALTER TABLE `stock_opname_bahans`
     ADD COLUMN `stob_staff_name` VARCHAR(255) NULL AFTER `staff_id`',
  'SELECT ''skip stock_opname_bahans.stob_staff_name'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans' AND COLUMN_NAME = 'stob_acc_name') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans') = 1,
  'ALTER TABLE `stock_opname_bahans`
     ADD COLUMN `stob_acc_name` VARCHAR(255) NULL AFTER `acc_by`',
  'SELECT ''skip stock_opname_bahans.stob_acc_name'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans' AND COLUMN_NAME = 'stob_decided_at') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans') = 1,
  'ALTER TABLE `stock_opname_bahans`
     ADD COLUMN `stob_decided_at` TIMESTAMP NULL AFTER `stob_acc_name`',
  'SELECT ''skip stock_opname_bahans.stob_decided_at'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_27_040000_add_snapshot_columns_to_stock_opname_bahans_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_27_040000_add_snapshot_columns_to_stock_opname_bahans_table');

SELECT '2026_08_27_040000_add_snapshot_columns_to_stock_opname_bahans_table OK' AS result;
