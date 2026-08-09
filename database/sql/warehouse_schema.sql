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
