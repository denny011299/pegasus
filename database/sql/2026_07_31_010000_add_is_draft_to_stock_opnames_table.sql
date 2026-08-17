-- Migration: 2026_07_31_010000_add_is_draft_to_stock_opnames_table
-- Idempotent (cek kolom dulu). Alternatif: php artisan migrate
--
-- BLOCKING pada deploy baru: StockOpname::insertStockOpname()/updateStockOpname()
-- menulis is_draft tanpa Schema::hasColumn() guard -- tanpa kolom ini, SETIAP
-- pembuatan Stock Opname (Produk) crash 500 ("Unknown column 'is_draft'"), bukan
-- cuma alur draft-nya. Jalankan migration ini SEBELUM database dipakai.
--
-- Draft disimpan sebagai flag terpisah dari `status`, bukan nilai baru di
-- kolomnya -- lihat migration aslinya di fase2/main untuk alasan lengkap.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames' AND COLUMN_NAME = 'is_draft') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames') = 1,
  'ALTER TABLE `stock_opnames`
     ADD COLUMN `is_draft` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`',
  'SELECT ''skip stock_opnames.is_draft'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Catat di migrations (supaya artisan migrate tidak bentrok)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_31_010000_add_is_draft_to_stock_opnames_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_31_010000_add_is_draft_to_stock_opnames_table');

SELECT '2026_07_31_010000_add_is_draft_to_stock_opnames_table OK' AS result;
