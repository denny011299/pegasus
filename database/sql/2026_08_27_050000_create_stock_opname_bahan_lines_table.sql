-- Migration: 2026_08_27_050000_create_stock_opname_bahan_lines_table
-- Idempotent (CREATE TABLE IF NOT EXISTS). Alternatif: php artisan migrate
--
-- Tabel detail Stock Opname BAHAN versi baru. Kembaran persis
-- 2026_08_27_030000_create_stock_opname_lines_table (Produk) untuk Supplies.

CREATE TABLE IF NOT EXISTS `stock_opname_bahan_lines` (
  `sobl_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stob_id` INT NOT NULL,
  `supplies_id` INT NULL,
  `unit_id` INT NULL,
  `sobl_counted_qty` INT NULL COMMENT 'NULL = satuan ini tidak dihitung',
  `sobl_notes` TEXT NULL,
  `sobl_supplies_name` VARCHAR(255) NULL,
  `sobl_unit_short_name` VARCHAR(50) NULL COMMENT 'token yang dicetak: "DOS", "pcs"',
  `sobl_unit_name` VARCHAR(100) NULL,
  `sobl_system_qty_final` INT NULL,
  `status` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`sobl_id`),
  UNIQUE KEY `stock_opname_bahan_lines_line_unique` (`stob_id`, `supplies_id`, `unit_id`),
  KEY `stock_opname_bahan_lines_stob_id_index` (`stob_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @db := DATABASE();

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_27_050000_create_stock_opname_bahan_lines_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_27_050000_create_stock_opname_bahan_lines_table');

SELECT '2026_08_27_050000_create_stock_opname_bahan_lines_table OK' AS result;
