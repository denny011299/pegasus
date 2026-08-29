-- Migration: 2026_08_27_010000_add_is_old_version_to_stock_opname_tables
-- Idempotent (cek kolom dulu). Alternatif: php artisan migrate
--
-- Pemisah versi skema Stock Opname, langkah 1 dari rancang ulang 2026-08-27.
-- DEFAULT SENGAJA 1 (true): semua dokumen yang sudah ada memang versi lama, jadi ini murni
-- ADD COLUMN -- tanpa backfill, tanpa UPDATE, tanpa peluang salah melabeli data historis.
-- Konsekuensinya alur insert baru WAJIB menulis is_old_version = 0 secara eksplisit.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames' AND COLUMN_NAME = 'is_old_version') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames') = 1,
  'ALTER TABLE `stock_opnames`
     ADD COLUMN `is_old_version` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_draft`',
  'SELECT ''skip stock_opnames.is_old_version'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans' AND COLUMN_NAME = 'is_old_version') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans') = 1,
  'ALTER TABLE `stock_opname_bahans`
     ADD COLUMN `is_old_version` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_draft`',
  'SELECT ''skip stock_opname_bahans.is_old_version'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_27_010000_add_is_old_version_to_stock_opname_tables',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_27_010000_add_is_old_version_to_stock_opname_tables');

SELECT '2026_08_27_010000_add_is_old_version_to_stock_opname_tables OK' AS result;
