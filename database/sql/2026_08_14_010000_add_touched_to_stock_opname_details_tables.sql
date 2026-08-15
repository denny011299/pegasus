-- Migration: 2026_08_14_010000_add_touched_to_stock_opname_details_tables
-- Idempotent (cek kolom dulu). Alternatif: php artisan migrate
--
-- GitHub #53: PDF Stock Opname perlu membedakan baris yang benar-benar diisi
-- staf dari baris yang cuma ikut tersimpan dengan real qty auto = stok sistem.
-- Kolom ini murni tampilan/PDF, tidak menyentuh logika approval/stok.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_details' AND COLUMN_NAME = 'stod_touched') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_details') = 1,
  'ALTER TABLE `stock_opname_details`
     ADD COLUMN `stod_touched` TINYINT(1) NOT NULL DEFAULT 0 AFTER `stod_notes`',
  'SELECT ''skip stock_opname_details.stod_touched'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_detail_bahans' AND COLUMN_NAME = 'stobd_touched') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_detail_bahans') = 1,
  'ALTER TABLE `stock_opname_detail_bahans`
     ADD COLUMN `stobd_touched` TINYINT(1) NOT NULL DEFAULT 0 AFTER `stobd_notes`',
  'SELECT ''skip stock_opname_detail_bahans.stobd_touched'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Catat di migrations (supaya artisan migrate tidak bentrok)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_14_010000_add_touched_to_stock_opname_details_tables',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_14_010000_add_touched_to_stock_opname_details_tables');

SELECT '2026_08_14_010000_add_touched_to_stock_opname_details_tables OK' AS result;
