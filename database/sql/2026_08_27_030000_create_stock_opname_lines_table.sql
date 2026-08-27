-- Migration: 2026_08_27_030000_create_stock_opname_lines_table
-- Idempotent (CREATE TABLE IF NOT EXISTS). Alternatif: php artisan migrate
--
-- Tabel detail Stock Opname Produk versi baru: SATU BARIS PER SATUAN, angka betulan.
-- stock_opname_details versi lama TIDAK disentuh dan TIDAK dimigrasikan.
--
--  sol_counted_qty NULL       = satuan tidak dihitung (bukan 0, bukan token "-")
--  sol_*_name                 = snapshot identitas, diisi saat dokumen keluar dari draft
--  sol_system_qty_final       = stok sistem saat dokumen DIPUTUSKAN (bukan saat dibuat)
--  selisih                    = TIDAK PERNAH DISIMPAN, selalu diturunkan saat ditampilkan
--
-- Tanpa foreign key, konsisten dengan seluruh repo ini (0 dari 76 migration memakai FK):
-- relasi sengaja longgar supaya menghapus/mengganti nama satuan/produk tidak bisa mengubah,
-- mengosongkan, apalagi menghapus berantai dokumen yang sudah diputuskan.

CREATE TABLE IF NOT EXISTS `stock_opname_lines` (
  `sol_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sto_id` INT NOT NULL,
  `product_id` INT NULL,
  `product_variant_id` INT NULL,
  `unit_id` INT NULL,
  `sol_counted_qty` INT NULL COMMENT 'NULL = satuan ini tidak dihitung',
  `sol_notes` TEXT NULL,
  `sol_product_name` VARCHAR(255) NULL,
  `sol_variant_name` VARCHAR(255) NULL,
  `sol_variant_sku` VARCHAR(100) NULL,
  `sol_unit_short_name` VARCHAR(50) NULL COMMENT 'token yang dicetak: "DOS", "pcs"',
  `sol_unit_name` VARCHAR(100) NULL,
  `sol_system_qty_final` INT NULL,
  `status` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`sol_id`),
  UNIQUE KEY `stock_opname_lines_line_unique` (`sto_id`, `product_variant_id`, `unit_id`),
  KEY `stock_opname_lines_sto_id_index` (`sto_id`)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SET @db := DATABASE();

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_27_030000_create_stock_opname_lines_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_27_030000_create_stock_opname_lines_table');

SELECT '2026_08_27_030000_create_stock_opname_lines_table OK' AS result;
