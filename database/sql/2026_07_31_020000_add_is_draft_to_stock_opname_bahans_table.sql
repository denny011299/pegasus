-- Migration: 2026_07_31_020000_add_is_draft_to_stock_opname_bahans_table
-- Idempotent (cek kolom dulu). Alternatif: php artisan migrate
--
-- BLOCKING pada deploy baru: StockOpnameBahan::insertStockOpnameBahan()/
-- updateStockOpnameBahan() menulis is_draft tanpa Schema::hasColumn() guard --
-- tanpa kolom ini, SETIAP pembuatan Stock Opname Bahan crash 500 ("Unknown
-- column 'is_draft'"), bukan cuma alur draft-nya. Jalankan migration ini
-- SEBELUM database dipakai.
--
-- Sama seperti add_is_draft_to_stock_opnames_table: flag terpisah dari
-- `status`, bukan nilai baru di kolomnya.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans' AND COLUMN_NAME = 'is_draft') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans') = 1,
  'ALTER TABLE `stock_opname_bahans`
     ADD COLUMN `is_draft` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`',
  'SELECT ''skip stock_opname_bahans.is_draft'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Catat di migrations (supaya artisan migrate tidak bentrok)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_31_020000_add_is_draft_to_stock_opname_bahans_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_31_020000_add_is_draft_to_stock_opname_bahans_table');

SELECT '2026_07_31_020000_add_is_draft_to_stock_opname_bahans_table OK' AS result;
