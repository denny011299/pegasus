-- =============================================================================
-- PEGASUS Fase 2 — SEMUA perubahan tabel (1 file)
-- Import sekali di phpMyAdmin / MySQL. Aman diulang (IF NOT EXISTS / cek kolom).
-- Tanpa seeder master data. Branch referensi: fase2/main
-- Generated: 2026-08-04 12:54
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;


-- #############################################################################
-- FILE: warehouse_schema.sql
-- #############################################################################

-- =============================================================================
-- PEGASUS - Warehouse / Gudang schema (MySQL / MariaDB)
-- Ganti migration Laravel jika server tidak bisa `php artisan migrate`
-- Urutan WAJIB diikuti dari atas ke bawah.
-- Aman dijalankan ulang (IF NOT EXISTS / cek kolom).
-- =============================================================================

-- -----------------------------------------------------------------------------
-- 1) Tabel warehouse_types
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouse_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_type_name` VARCHAR(250) NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1=active, 0=deleted',
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_types_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2) Tabel warehouses
-- status: 1=Aktif, 2=Non-Aktif, 0=Deleted (soft delete)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_name` VARCHAR(250) NOT NULL,
  `warehouse_type_id` BIGINT UNSIGNED NOT NULL,
  `warehouse_address` TEXT NULL DEFAULT NULL,
  `sidebar_menus` JSON NULL DEFAULT NULL COMMENT 'NULL/[]=allow all; JSON array SubModules whitelist',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1=aktif, 2=non-aktif, 0=deleted',
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouses_status_index` (`status`),
  KEY `warehouses_warehouse_type_id_index` (`warehouse_type_id`),
  CONSTRAINT `warehouses_warehouse_type_id_foreign`
    FOREIGN KEY (`warehouse_type_id`) REFERENCES `warehouse_types` (`id`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pastikan address nullable (jika tabel sudah ada dari versi lama)
ALTER TABLE `warehouses`
  MODIFY COLUMN `warehouse_address` TEXT NULL DEFAULT NULL;

-- Kolom sidebar_menus (whitelist menu sidebar per gudang) — aman dijalankan ulang
SET @col_sidebar_menus := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'warehouses'
    AND COLUMN_NAME = 'sidebar_menus'
);

SET @sql_sidebar_menus := IF(
  @col_sidebar_menus = 0,
  'ALTER TABLE `warehouses`
     ADD COLUMN `sidebar_menus` JSON NULL DEFAULT NULL
     COMMENT ''NULL/[]=allow all; JSON array SubModules whitelist''
     AFTER `warehouse_address`',
  'SELECT ''kolom warehouses.sidebar_menus sudah ada'' AS info'
);

PREPARE stmt_sidebar_menus FROM @sql_sidebar_menus;
EXECUTE stmt_sidebar_menus;
DEALLOCATE PREPARE stmt_sidebar_menus;

-- -----------------------------------------------------------------------------
-- 3) Pivot staff_warehouses (assign gudang ke staf)
-- staff_id = INT (sesuai staffs.staff_id)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_warehouses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `warehouse_id` BIGINT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_warehouses_staff_id_warehouse_id_unique` (`staff_id`, `warehouse_id`),
  KEY `staff_warehouses_staff_id_index` (`staff_id`),
  KEY `staff_warehouses_warehouse_id_index` (`warehouse_id`),
  CONSTRAINT `staff_warehouses_staff_id_foreign`
    FOREIGN KEY (`staff_id`) REFERENCES `staffs` (`staff_id`)
    ON DELETE CASCADE,
  CONSTRAINT `staff_warehouses_warehouse_id_foreign`
    FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`id`)
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4) Kolom last_active_warehouse_id di staffs
-- -----------------------------------------------------------------------------
SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'staffs'
    AND COLUMN_NAME = 'last_active_warehouse_id'
);

SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `staffs`
     ADD COLUMN `last_active_warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `role_id`,
     ADD INDEX `staffs_last_active_warehouse_id_index` (`last_active_warehouse_id`)',
  'SELECT ''kolom last_active_warehouse_id sudah ada'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 5) Catat di tabel migrations Laravel (supaya migrate tidak error ulang)
-- Hapus blok ini jika tidak memakai tabel migrations
-- -----------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_22_013000_create_warehouse_types_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_22_013000_create_warehouse_types_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_22_013100_create_warehouses_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_22_013100_create_warehouses_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_22_120000_make_warehouse_address_nullable', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_22_120000_make_warehouse_address_nullable');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_22_121000_create_staff_warehouses_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_22_121000_create_staff_warehouses_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_22_122200_add_last_active_warehouse_id_to_staffs_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_22_122200_add_last_active_warehouse_id_to_staffs_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_24_170000_add_sidebar_menus_to_warehouses_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_24_170000_add_sidebar_menus_to_warehouses_table');

-- -----------------------------------------------------------------------------
-- 6) Kolom is_main_warehouse di warehouse_types (hanya 1 yang boleh = 1)
-- -----------------------------------------------------------------------------
SET @col_main_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'warehouse_types'
    AND COLUMN_NAME = 'is_main_warehouse'
);

SET @sql_main := IF(
  @col_main_exists = 0,
  'ALTER TABLE `warehouse_types`
     ADD COLUMN `is_main_warehouse` TINYINT NOT NULL DEFAULT 0
       COMMENT ''1 = tipe gudang utama (hanya 1)'' AFTER `warehouse_type_name`,
     ADD INDEX `warehouse_types_is_main_warehouse_index` (`is_main_warehouse`)',
  'SELECT ''kolom is_main_warehouse sudah ada'' AS info'
);

PREPARE stmt_main FROM @sql_main;
EXECUTE stmt_main;
DEALLOCATE PREPARE stmt_main;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_23_100000_add_is_main_warehouse_to_warehouse_types_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_23_100000_add_is_main_warehouse_to_warehouse_types_table');

-- -----------------------------------------------------------------------------
-- 9) product_stocks.warehouse_id (stok per gudang)
-- Full script (backfill + seed + unique): database/sql/product_stocks_warehouse.sql
-- Atau: php artisan migrate
-- -----------------------------------------------------------------------------
-- Lihat file terpisah: database/sql/product_stocks_warehouse.sql


-- #############################################################################
-- FILE: warehouses_sidebar_menus.sql
-- #############################################################################

-- =============================================================================
-- PEGASUS - warehouses.sidebar_menus (whitelist menu sidebar per gudang)
-- Alternatif: php artisan migrate
--   (migration: 2026_07_24_170000_add_sidebar_menus_to_warehouses_table)
-- NULL / [] = semua menu diizinkan (backward compatible)
-- =============================================================================

SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'warehouses'
    AND COLUMN_NAME = 'sidebar_menus'
);

SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `warehouses`
     ADD COLUMN `sidebar_menus` JSON NULL DEFAULT NULL AFTER `warehouse_address`',
  'SELECT ''kolom warehouses.sidebar_menus sudah ada'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_24_170000_add_sidebar_menus_to_warehouses_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_07_24_170000_add_sidebar_menus_to_warehouses_table'
);

SELECT
  (SELECT COUNT(*) FROM warehouses WHERE sidebar_menus IS NULL) AS allow_all_rows,
  (SELECT COUNT(*) FROM warehouses WHERE sidebar_menus IS NOT NULL) AS whitelist_rows;


-- #############################################################################
-- FILE: product_stocks_warehouse.sql
-- #############################################################################

-- =============================================================================
-- PEGASUS - product_stocks.warehouse_id (stok per gudang)
-- Jalankan SETELAH warehouse_types + warehouses sudah ada.
-- Aman dijalankan ulang (cek kolom / index).
-- Alternatif: php artisan migrate
--   (migration: 2026_07_23_190000_add_warehouse_id_to_product_stocks_table)
-- =============================================================================

-- 1) Tambah kolom warehouse_id
SET @col_ps_wh_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'product_stocks'
    AND COLUMN_NAME = 'warehouse_id'
);

SET @sql_ps_wh := IF(
  @col_ps_wh_exists = 0,
  'ALTER TABLE `product_stocks`
     ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`,
     ADD INDEX `product_stocks_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''kolom product_stocks.warehouse_id sudah ada'' AS info'
);

PREPARE stmt_ps_wh FROM @sql_ps_wh;
EXECUTE stmt_ps_wh;
DEALLOCATE PREPARE stmt_ps_wh;

-- 2) Cari gudang utama (tipe is_main_warehouse = 1), fallback gudang aktif pertama
SET @main_wh_id := (
  SELECT w.id
  FROM warehouses w
  INNER JOIN warehouse_types wt ON wt.id = w.warehouse_type_id
  WHERE w.status = 1
    AND wt.status = 1
    AND wt.is_main_warehouse = 1
  ORDER BY w.id
  LIMIT 1
);

SET @main_wh_id := IFNULL(
  @main_wh_id,
  (SELECT id FROM warehouses WHERE status = 1 ORDER BY id LIMIT 1)
);

-- 3) Backfill stok lama → gudang utama
UPDATE product_stocks
SET warehouse_id = @main_wh_id
WHERE warehouse_id IS NULL
  AND @main_wh_id IS NOT NULL;

-- 4) Seed stok 0 ke gudang aktif lain (kombinasi dari stok gudang utama)
INSERT INTO product_stocks (
  product_variant_id,
  product_id,
  unit_id,
  warehouse_id,
  ps_stock,
  status,
  created_at,
  updated_at,
  created_by
)
SELECT
  ps.product_variant_id,
  ps.product_id,
  ps.unit_id,
  w.id AS warehouse_id,
  0 AS ps_stock,
  1 AS status,
  NOW() AS created_at,
  NOW() AS updated_at,
  NULL AS created_by
FROM warehouses w
CROSS JOIN (
  SELECT DISTINCT product_variant_id, product_id, unit_id
  FROM product_stocks
  WHERE status = 1
    AND warehouse_id = @main_wh_id
) ps
WHERE w.status = 1
  AND w.id <> @main_wh_id
  AND @main_wh_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM product_stocks x
    WHERE x.warehouse_id = w.id
      AND x.product_variant_id = ps.product_variant_id
      AND x.unit_id = ps.unit_id
      AND x.status = 1
  );

-- 5) Dedup sebelum unique: lepaskan duplikat non-aktif dari unique key
UPDATE product_stocks ps
INNER JOIN (
  SELECT warehouse_id, product_variant_id, unit_id, MIN(ps_id) AS keep_id
  FROM product_stocks
  WHERE warehouse_id IS NOT NULL
  GROUP BY warehouse_id, product_variant_id, unit_id
  HAVING COUNT(*) > 1
) d ON d.warehouse_id = ps.warehouse_id
   AND d.product_variant_id = ps.product_variant_id
   AND d.unit_id = ps.unit_id
SET ps.status = 0,
    ps.warehouse_id = NULL,
    ps.updated_at = NOW()
WHERE ps.ps_id <> d.keep_id
  AND (ps.status = 0 OR ps.ps_stock = 0 OR ps.ps_id > d.keep_id);

-- Prefer keep baris status=1 / stock tertinggi (ulang pass sederhana):
-- (opsional; aman jika sudah bersih)

-- 6) Unique index (warehouse + variant + unit)
SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'product_stocks'
    AND INDEX_NAME = 'product_stocks_warehouse_variant_unit_unique'
);

SET @sql_idx := IF(
  @idx_exists = 0,
  'ALTER TABLE `product_stocks`
     ADD UNIQUE INDEX `product_stocks_warehouse_variant_unit_unique`
       (`warehouse_id`, `product_variant_id`, `unit_id`)',
  'SELECT ''unique index sudah ada'' AS info'
);

PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- 7) Catat di tabel migrations (supaya artisan migrate tidak conflict)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_23_190000_add_warehouse_id_to_product_stocks_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_07_23_190000_add_warehouse_id_to_product_stocks_table'
);

SELECT
  @main_wh_id AS main_warehouse_id,
  (SELECT COUNT(*) FROM product_stocks WHERE status = 1) AS active_stock_rows,
  (SELECT COUNT(DISTINCT warehouse_id) FROM product_stocks WHERE status = 1 AND warehouse_id IS NOT NULL) AS warehouses_with_stock;


-- #############################################################################
-- FILE: supplies_stocks_warehouse.sql
-- #############################################################################

-- =============================================================================
-- PEGASUS - supplies_stocks.warehouse_id (stok bahan mentah per gudang)
-- Jalankan SETELAH warehouse_types + warehouses sudah ada.
-- Aman dijalankan ulang (cek kolom / index).
-- Alternatif: php artisan migrate
--   (migration: 2026_07_24_150000_add_warehouse_id_to_supplies_stocks_table)
-- =============================================================================

-- 1) Tambah kolom warehouse_id
SET @col_ss_wh_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'supplies_stocks'
    AND COLUMN_NAME = 'warehouse_id'
);

SET @sql_ss_wh := IF(
  @col_ss_wh_exists = 0,
  'ALTER TABLE `supplies_stocks`
     ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`,
     ADD INDEX `supplies_stocks_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''kolom supplies_stocks.warehouse_id sudah ada'' AS info'
);

PREPARE stmt_ss_wh FROM @sql_ss_wh;
EXECUTE stmt_ss_wh;
DEALLOCATE PREPARE stmt_ss_wh;

-- 2) Cari gudang utama (tipe is_main_warehouse = 1), fallback gudang aktif pertama
SET @main_wh_id := (
  SELECT w.id
  FROM warehouses w
  INNER JOIN warehouse_types wt ON wt.id = w.warehouse_type_id
  WHERE w.status = 1
    AND wt.status = 1
    AND wt.is_main_warehouse = 1
  ORDER BY w.id
  LIMIT 1
);

SET @main_wh_id := IFNULL(
  @main_wh_id,
  (SELECT id FROM warehouses WHERE status = 1 ORDER BY id LIMIT 1)
);

-- 3) Backfill stok lama → gudang utama
UPDATE supplies_stocks
SET warehouse_id = @main_wh_id
WHERE warehouse_id IS NULL
  AND @main_wh_id IS NOT NULL;

-- 4) Dedup sebelum unique: nonaktifkan duplikat
UPDATE supplies_stocks ss
INNER JOIN (
  SELECT warehouse_id, supplies_id, unit_id, MIN(ss_id) AS keep_id
  FROM supplies_stocks
  WHERE warehouse_id IS NOT NULL
  GROUP BY warehouse_id, supplies_id, unit_id
  HAVING COUNT(*) > 1
) d ON d.warehouse_id = ss.warehouse_id
   AND d.supplies_id = ss.supplies_id
   AND d.unit_id = ss.unit_id
SET ss.status = 0,
    ss.warehouse_id = NULL,
    ss.updated_at = NOW()
WHERE ss.ss_id <> d.keep_id;

-- 5) Unique index (warehouse + supplies + unit)
SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'supplies_stocks'
    AND INDEX_NAME = 'supplies_stocks_warehouse_supplies_unit_unique'
);

SET @sql_idx := IF(
  @idx_exists = 0,
  'ALTER TABLE `supplies_stocks`
     ADD UNIQUE INDEX `supplies_stocks_warehouse_supplies_unit_unique`
       (`warehouse_id`, `supplies_id`, `unit_id`)',
  'SELECT ''unique index sudah ada'' AS info'
);

PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- 6) Catat di tabel migrations
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_24_150000_add_warehouse_id_to_supplies_stocks_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_07_24_150000_add_warehouse_id_to_supplies_stocks_table'
);

SELECT
  @main_wh_id AS main_warehouse_id,
  (SELECT COUNT(*) FROM supplies_stocks WHERE status = 1) AS active_stock_rows,
  (SELECT COUNT(DISTINCT warehouse_id) FROM supplies_stocks WHERE status = 1 AND warehouse_id IS NOT NULL) AS warehouses_with_stock;


-- #############################################################################
-- FILE: add_safety_stock.sql
-- #############################################################################

-- Safety Stock: kolom + permission
-- Alternatif: php artisan migrate

-- 1) product_variants
SET @col1 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'safety_stock'
);
SET @sql1 := IF(@col1 = 0,
  'ALTER TABLE `product_variants`
     ADD COLUMN `safety_stock` INT NOT NULL DEFAULT 0 AFTER `product_variant_alert`',
  'SELECT ''product_variants.safety_stock sudah ada'' AS info'
);
PREPARE s1 FROM @sql1; EXECUTE s1; DEALLOCATE PREPARE s1;

SET @col1_unit := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'safety_unit_id'
);
SET @sql1_unit := IF(@col1_unit = 0,
  'ALTER TABLE `product_variants`
     ADD COLUMN `safety_unit_id` INT NULL DEFAULT NULL AFTER `safety_stock`',
  'SELECT ''product_variants.safety_unit_id sudah ada'' AS info'
);
PREPARE s1_unit FROM @sql1_unit; EXECUTE s1_unit; DEALLOCATE PREPARE s1_unit;

-- 2) product_stocks
SET @col2 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_stocks' AND COLUMN_NAME = 'ps_safety_stock'
);
SET @sql2 := IF(@col2 = 0,
  'ALTER TABLE `product_stocks`
     ADD COLUMN `ps_safety_stock` INT NOT NULL DEFAULT 0 AFTER `ps_stock`',
  'SELECT ''product_stocks.ps_safety_stock sudah ada'' AS info'
);
PREPARE s2 FROM @sql2; EXECUTE s2; DEALLOCATE PREPARE s2;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_24_140000_add_safety_stock_columns', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_24_140000_add_safety_stock_columns'
);


-- #############################################################################
-- FILE: stock_transfers.sql
-- #############################################################################

-- Stock Transfer tables (manual run jika migrate belum dijalankan)
-- Status: 0=deleted, 1=pending, 2=success, 3=rejected

CREATE TABLE IF NOT EXISTS `stock_transfers` (
  `st_id` int unsigned NOT NULL AUTO_INCREMENT,
  `transfer_code` varchar(30) NOT NULL,
  `transfer_date` date NOT NULL,
  `sender_id` int unsigned NOT NULL,
  `receiver_id` int unsigned DEFAULT NULL,
  `from_warehouse_id` bigint unsigned NOT NULL,
  `to_warehouse_id` bigint unsigned NOT NULL,
  `note` longtext,
  `accept_note` longtext,
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '0=deleted,1=pending,2=success,3=rejected',
  `created_by` int unsigned DEFAULT NULL,
  `acc_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`st_id`),
  UNIQUE KEY `stock_transfers_transfer_code_unique` (`transfer_code`),
  KEY `stock_transfers_from_wh_idx` (`from_warehouse_id`),
  KEY `stock_transfers_to_wh_idx` (`to_warehouse_id`),
  KEY `stock_transfers_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_transfer_details` (
  `std_id` int unsigned NOT NULL AUTO_INCREMENT,
  `st_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `product_variant_id` int unsigned NOT NULL,
  `unit_id` int unsigned NOT NULL,
  `qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `qty_received` decimal(18,4) DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1=active,0=inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`std_id`),
  KEY `stock_transfer_details_st_id_idx` (`st_id`),
  KEY `stock_transfer_details_pv_idx` (`product_variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- #############################################################################
-- FILE: log_stocks_warehouse.sql
-- #############################################################################

-- log_stocks.warehouse_id — Idempotent + backfill Stock Transfer

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'log_stocks' AND COLUMN_NAME = 'warehouse_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'log_stocks') = 1,
  'ALTER TABLE `log_stocks`
     ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL AFTER `staff_id`,
     ADD INDEX `log_stocks_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''log_stocks.warehouse_id sudah ada'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE log_stocks l
INNER JOIN stock_transfers st ON st.transfer_code = l.log_kode
SET l.warehouse_id = st.from_warehouse_id
WHERE l.warehouse_id IS NULL
  AND l.log_kode LIKE 'ST%'
  AND (
    l.log_notes LIKE '%keluar gudang asal%'
    OR l.log_notes LIKE '%kembalikan stok%'
    OR l.log_notes LIKE '%bongkar%'
    OR l.log_notes LIKE '%hasil bongkar%'
    OR l.log_notes LIKE '%koreksi edit%'
  );

UPDATE log_stocks l
INNER JOIN stock_transfers st ON st.transfer_code = l.log_kode
SET l.warehouse_id = st.to_warehouse_id
WHERE l.warehouse_id IS NULL
  AND l.log_kode LIKE 'ST%'
  AND l.log_notes LIKE '%masuk gudang tujuan%';

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_27_000100_add_warehouse_id_to_log_stocks_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_27_000100_add_warehouse_id_to_log_stocks_table');


-- #############################################################################
-- FILE: stock_transfer_received_unit.sql
-- #############################################################################

-- Simpan satuan hasil penerimaan tanpa mengubah satuan kirim.
SET @column_exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'stock_transfer_details'
      AND COLUMN_NAME = 'received_unit_id'
);

SET @sql := IF(
    @column_exists = 0,
    'ALTER TABLE `stock_transfer_details` ADD COLUMN `received_unit_id` INT UNSIGNED NULL AFTER `unit_id`',
    'SELECT ''stock_transfer_details.received_unit_id already exists'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- #############################################################################
-- FILE: product_stocks_alert.sql
-- #############################################################################

-- Peringatan stok per gudang (product_stocks.ps_alert_stock). Idempotent.

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_stocks' AND COLUMN_NAME = 'ps_alert_stock') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_stocks') = 1,
  'ALTER TABLE `product_stocks` ADD COLUMN `ps_alert_stock` INT NOT NULL DEFAULT 0 AFTER `ps_safety_stock`',
  'SELECT ''product_stocks.ps_alert_stock sudah ada'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_27_221000_add_ps_alert_stock_to_product_stocks_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_27_221000_add_ps_alert_stock_to_product_stocks_table');


-- #############################################################################
-- FILE: retail_unit_product_variants.sql
-- #############################################################################

-- product_variants.retail_unit — satuan eceran default per variant (unit_id). Nullable.
-- Idempotent.

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'retail_unit') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants') = 1,
  'ALTER TABLE `product_variants` ADD COLUMN `retail_unit` INT NULL AFTER `product_variant_stock`',
  'SELECT ''product_variants.retail_unit sudah ada'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_27_002744_add_retail_unit_to_product_variants_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_27_002744_add_retail_unit_to_product_variants_table');


-- #############################################################################
-- FILE: retail_warehouse_sales_orders.sql
-- #############################################################################

-- sales_orders.retail_warehouse_id — gudang eceran untuk item satuan eceran.
-- Idempotent.

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'retail_warehouse_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders`
     ADD COLUMN `retail_warehouse_id` BIGINT UNSIGNED NULL AFTER `so_customer`,
     ADD INDEX `sales_orders_retail_warehouse_id_index` (`retail_warehouse_id`)',
  'SELECT ''sales_orders.retail_warehouse_id sudah ada'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_27_002805_add_retail_warehouse_id_to_sales_orders_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_27_002805_add_retail_warehouse_id_to_sales_orders_table');


-- #############################################################################
-- FILE: sales_order_detail_warehouse.sql
-- #############################################################################

-- sales_order_details.warehouse_id — gudang eceran per baris pengiriman.
-- Idempotent + backfill dari header.

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_order_details' AND COLUMN_NAME = 'warehouse_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_order_details') = 1,
  'ALTER TABLE `sales_order_details`
     ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL AFTER `unit_id`,
     ADD INDEX `sales_order_details_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''sales_order_details.warehouse_id sudah ada'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `sales_order_details` sod
INNER JOIN `sales_orders` so ON so.so_id = sod.so_id
INNER JOIN `product_variants` pv ON pv.product_variant_id = sod.product_variant_id
SET sod.warehouse_id = so.retail_warehouse_id
WHERE sod.warehouse_id IS NULL
  AND so.retail_warehouse_id IS NOT NULL
  AND pv.retail_unit > 0
  AND sod.unit_id = pv.retail_unit;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_29_140000_add_warehouse_id_to_sales_order_details_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_29_140000_add_warehouse_id_to_sales_order_details_table');


-- #############################################################################
-- FILE: lead_time_minimum_order.sql
-- #############################################################################

-- Lead time/safety bahan mentah dimiliki tabel supplies.
-- Aman dijalankan ulang dan aman untuk database dengan schema parsial.

SET @db := DATABASE();

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies') = 1
    AND
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'lead_time_days') = 0,
    'ALTER TABLE `supplies` ADD COLUMN `lead_time_days` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `supplies_alert`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies') = 1
    AND
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'safety_stock') = 0,
    'ALTER TABLE `supplies` ADD COLUMN `safety_stock` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `lead_time_days`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'lead_time_days') = 1
    AND
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants' AND COLUMN_NAME = 'lead_time_days') = 1,
    'UPDATE `supplies` s
     INNER JOIN (
         SELECT `supplies_id`, MAX(COALESCE(`lead_time_days`, 0)) AS `lead_time_days`
         FROM `supplies_variants`
         GROUP BY `supplies_id`
     ) sv ON sv.`supplies_id` = s.`supplies_id`
     SET s.`lead_time_days` = GREATEST(COALESCE(s.`lead_time_days`, 0), sv.`lead_time_days`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'safety_stock') = 1
    AND
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants' AND COLUMN_NAME = 'safety_stock') = 1,
    'UPDATE `supplies` s
     INNER JOIN (
         SELECT `supplies_id`, MAX(COALESCE(`safety_stock`, 0)) AS `safety_stock`
         FROM `supplies_variants`
         GROUP BY `supplies_id`
     ) sv ON sv.`supplies_id` = s.`supplies_id`
     SET s.`safety_stock` = GREATEST(COALESCE(s.`safety_stock`, 0), sv.`safety_stock`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'lead_time_days') = 1,
    'UPDATE `supplies` SET `lead_time_days` = GREATEST(COALESCE(`lead_time_days`, 0), 0)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'lead_time_days') = 1,
    'ALTER TABLE `supplies` MODIFY COLUMN `lead_time_days` INT UNSIGNED NOT NULL DEFAULT 0',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'safety_stock') = 1,
    'UPDATE `supplies` SET `safety_stock` = GREATEST(COALESCE(`safety_stock`, 0), 0)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'safety_stock') = 1,
    'ALTER TABLE `supplies` MODIFY COLUMN `safety_stock` INT UNSIGNED NOT NULL DEFAULT 0',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants' AND COLUMN_NAME = 'safety_stock') = 1,
    'ALTER TABLE `supplies_variants` DROP COLUMN `safety_stock`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants' AND COLUMN_NAME = 'lead_time_days') = 1,
    'ALTER TABLE `supplies_variants` DROP COLUMN `lead_time_days`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'safety_stock') = 0,
    'ALTER TABLE `product_variants` ADD COLUMN `safety_stock` INT NOT NULL DEFAULT 0 AFTER `product_variant_alert`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'safety_unit_id') = 0,
    'ALTER TABLE `product_variants` ADD COLUMN `safety_unit_id` INT NULL DEFAULT NULL AFTER `safety_stock`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'lead_time_days') = 0,
    'ALTER TABLE `product_variants` ADD COLUMN `lead_time_days` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `product_variant_alert`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- #############################################################################
-- FILE: customer_supply_returns.sql
-- #############################################################################

-- Pengembalian bahan/kemasan customer dari Sales Order diterima.
-- Idempotent untuk MySQL/MariaDB; jalankan pada database Pegasus.

CREATE TABLE IF NOT EXISTS `customer_supply_returns` (
  `return_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_number` VARCHAR(40) NOT NULL,
  `so_id` INT NULL,
  `customer_id` INT NOT NULL,
  `return_date` DATE NOT NULL,
  `ref_number` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `proof_path` VARCHAR(255) NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '0=deleted, 1=pending, 2=accepted, 3=declined',
  `created_by` INT NULL,
  `acc_by` INT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`return_id`),
  UNIQUE KEY `csr_return_number_unique` (`return_number`),
  KEY `csr_so_id_index` (`so_id`),
  KEY `csr_customer_id_index` (`customer_id`),
  KEY `csr_return_date_index` (`return_date`),
  KEY `csr_status_index` (`status`),
  KEY `csr_created_by_index` (`created_by`),
  KEY `csr_acc_by_index` (`acc_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_supply_return_details` (
  `return_detail_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_id` INT NOT NULL,
  `supplies_id` INT NOT NULL,
  `unit_id` INT NOT NULL,
  `warehouse_id` INT NOT NULL,
  `qty` BIGINT UNSIGNED NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`return_detail_id`),
  UNIQUE KEY `csr_detail_item_unique` (`return_id`,`supplies_id`,`unit_id`,`warehouse_id`),
  KEY `csr_detail_return_id_index` (`return_id`),
  KEY `csr_detail_supplies_id_index` (`supplies_id`),
  KEY `csr_detail_unit_id_index` (`unit_id`),
  KEY `csr_detail_warehouse_id_index` (`warehouse_id`),
  KEY `csr_detail_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- FILE: customer_product_returns.sql
-- #############################################################################

-- Pengembalian produk jadi dari armada (tanpa referensi SO).
-- Idempotent untuk MySQL/MariaDB.

CREATE TABLE IF NOT EXISTS `customer_product_returns` (
  `return_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_number` VARCHAR(40) NOT NULL,
  `customer_id` INT NOT NULL,
  `return_date` DATE NOT NULL,
  `ref_number` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `proof_path` VARCHAR(255) NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '0=deleted, 1=pending, 2=accepted, 3=declined',
  `created_by` INT NULL,
  `acc_by` INT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`return_id`),
  UNIQUE KEY `cpr_return_number_unique` (`return_number`),
  KEY `cpr_customer_id_index` (`customer_id`),
  KEY `cpr_return_date_index` (`return_date`),
  KEY `cpr_status_index` (`status`),
  KEY `cpr_created_by_index` (`created_by`),
  KEY `cpr_acc_by_index` (`acc_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_product_return_details` (
  `return_detail_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_id` INT NOT NULL,
  `product_variant_id` INT NOT NULL,
  `unit_id` INT NOT NULL,
  `warehouse_id` INT NOT NULL,
  `qty` BIGINT UNSIGNED NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`return_detail_id`),
  UNIQUE KEY `cpr_detail_item_unique` (`return_id`,`product_variant_id`,`unit_id`,`warehouse_id`),
  KEY `cpr_detail_return_id_index` (`return_id`),
  KEY `cpr_detail_product_variant_id_index` (`product_variant_id`),
  KEY `cpr_detail_unit_id_index` (`unit_id`),
  KEY `cpr_detail_warehouse_id_index` (`warehouse_id`),
  KEY `cpr_detail_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #############################################################################
-- FILE: production_stock_transfer.sql
-- #############################################################################

-- Production output melalui Stock Transfer + traceability Product Issue.
-- Alternatif: php artisan migrate

SET @c1 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_details'
    AND COLUMN_NAME = 'destination_warehouse_id'
);
SET @s1 := IF(@c1 = 0,
  'ALTER TABLE `production_details` ADD COLUMN `destination_warehouse_id` BIGINT UNSIGNED NULL AFTER `unit_id`, ADD INDEX `production_details_destination_wh_idx` (`destination_warehouse_id`)',
  'SELECT ''production_details.destination_warehouse_id sudah ada'' AS info'
);
PREPARE q1 FROM @s1; EXECUTE q1; DEALLOCATE PREPARE q1;

SET @c2 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transfers' AND COLUMN_NAME = 'source_type'
);
SET @s2 := IF(@c2 = 0,
  'ALTER TABLE `stock_transfers` ADD COLUMN `source_type` VARCHAR(30) NULL AFTER `accept_note`, ADD INDEX `stock_transfers_source_type_idx` (`source_type`)',
  'SELECT ''stock_transfers.source_type sudah ada'' AS info'
);
PREPARE q2 FROM @s2; EXECUTE q2; DEALLOCATE PREPARE q2;

SET @c3 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transfers' AND COLUMN_NAME = 'source_id'
);
SET @s3 := IF(@c3 = 0,
  'ALTER TABLE `stock_transfers` ADD COLUMN `source_id` BIGINT UNSIGNED NULL AFTER `source_type`, ADD INDEX `stock_transfers_source_id_idx` (`source_id`)',
  'SELECT ''stock_transfers.source_id sudah ada'' AS info'
);
PREPARE q3 FROM @s3; EXECUTE q3; DEALLOCATE PREPARE q3;

SET @c4 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transfers' AND COLUMN_NAME = 'disposition'
);
SET @s4 := IF(@c4 = 0,
  'ALTER TABLE `stock_transfers` ADD COLUMN `disposition` VARCHAR(30) NULL AFTER `source_id`',
  'SELECT ''stock_transfers.disposition sudah ada'' AS info'
);
PREPARE q4 FROM @s4; EXECUTE q4; DEALLOCATE PREPARE q4;

SET @c5 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_issues' AND COLUMN_NAME = 'source_type'
);
SET @s5 := IF(@c5 = 0,
  'ALTER TABLE `product_issues` ADD COLUMN `source_type` VARCHAR(30) NULL AFTER `pi_notes`, ADD INDEX `product_issues_source_type_idx` (`source_type`)',
  'SELECT ''product_issues.source_type sudah ada'' AS info'
);
PREPARE q5 FROM @s5; EXECUTE q5; DEALLOCATE PREPARE q5;

SET @c6 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_issues' AND COLUMN_NAME = 'source_id'
);
SET @s6 := IF(@c6 = 0,
  'ALTER TABLE `product_issues` ADD COLUMN `source_id` BIGINT UNSIGNED NULL AFTER `source_type`, ADD INDEX `product_issues_source_id_idx` (`source_id`)',
  'SELECT ''product_issues.source_id sudah ada'' AS info'
);
PREPARE q6 FROM @s6; EXECUTE q6; DEALLOCATE PREPARE q6;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_30_030000_add_production_transfer_traceability',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS migration_batches)
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_07_30_030000_add_production_transfer_traceability'
);


-- #############################################################################
-- FILE: fase2_recent_patches.sql
-- #############################################################################

-- =============================================================================
-- PEGASUS - Patch schema ~1 minggu terakhir (Fase 2)
-- Idempotent (cek kolom / tabel dulu). Jalankan SETELAH warehouse + stock transfer.
-- Alternatif: php artisan migrate (branch fase2/main)
-- =============================================================================

SET @db := DATABASE();

-- -----------------------------------------------------------------------------
-- 1) product_variants.qty_per_pallet (Produksi: 1 pallet = N satuan default)
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'qty_per_pallet') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants') = 1,
  'ALTER TABLE `product_variants` ADD COLUMN `qty_per_pallet` INT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`',
  'SELECT ''skip qty_per_pallet'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 2) sales_delivery_orders_details.unit_id
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_delivery_orders_details' AND COLUMN_NAME = 'unit_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_delivery_orders_details') = 1,
  'ALTER TABLE `sales_delivery_orders_details` ADD COLUMN `unit_id` INT UNSIGNED NULL DEFAULT NULL AFTER `sdod_qty`',
  'SELECT ''skip sales_delivery_orders_details.unit_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 3) log_stocks.log_saldo
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'log_stocks' AND COLUMN_NAME = 'log_saldo') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'log_stocks') = 1,
  'ALTER TABLE `log_stocks` ADD COLUMN `log_saldo` DECIMAL(18,4) NULL DEFAULT NULL AFTER `log_jumlah`',
  'SELECT ''skip log_saldo'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 4) customer_supply_returns.so_id → NULLABLE
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_supply_returns') = 1,
  'ALTER TABLE `customer_supply_returns` MODIFY COLUMN `so_id` INT NULL',
  'SELECT ''skip customer_supply_returns.so_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 5) stock_opnames.is_draft / stock_opname_bahans.is_draft
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames' AND COLUMN_NAME = 'is_draft') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames') = 1,
  'ALTER TABLE `stock_opnames` ADD COLUMN `is_draft` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`',
  'SELECT ''skip stock_opnames.is_draft'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans' AND COLUMN_NAME = 'is_draft') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans') = 1,
  'ALTER TABLE `stock_opname_bahans` ADD COLUMN `is_draft` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`',
  'SELECT ''skip stock_opname_bahans.is_draft'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 6) Catat di migrations (supaya artisan migrate tidak bentrok)
-- -----------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_31_010000_add_is_draft_to_stock_opnames_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_31_010000_add_is_draft_to_stock_opnames_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_31_020000_add_is_draft_to_stock_opname_bahans_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_31_020000_add_is_draft_to_stock_opname_bahans_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_03_010000_add_qty_per_pallet_to_product_variants_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_03_010000_add_qty_per_pallet_to_product_variants_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_03_020000_add_unit_id_to_sales_delivery_orders_details',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_03_020000_add_unit_id_to_sales_delivery_orders_details');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_03_150000_add_log_saldo_to_log_stocks_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_03_150000_add_log_saldo_to_log_stocks_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_03_160000_make_customer_supply_returns_so_id_nullable',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_03_160000_make_customer_supply_returns_so_id_nullable');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_03_161000_create_customer_product_returns_tables',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_returns')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_03_161000_create_customer_product_returns_tables');

SELECT 'fase2_recent_patches OK' AS result;

SET FOREIGN_KEY_CHECKS = 1;
SELECT 'fase2_schema_all OK' AS result;
