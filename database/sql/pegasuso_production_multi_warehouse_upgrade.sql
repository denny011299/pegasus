-- =============================================================================
-- PEGASUS — Upgrade DB production (pegasuso) ke multi-gudang Fase 2
-- Sumber perbandingan: pegasuso_pegasus_production 31-08-26.sql
--
-- YANG KURANG di dump production (vs fase2/main):
--   - Tabel: warehouse_types, warehouses, staff_warehouses, stock_transfers, stock_transfer_details
--   - Kolom: staffs.last_active_warehouse_id
--   - Kolom: product_stocks.warehouse_id, supplies_stocks.warehouse_id
--   - Kolom: stock_opnames.warehouse_id, stock_opname_bahans.warehouse_id
--   - Kolom: log_stocks.warehouse_id
--   - Kolom: sales_orders.retail_warehouse_id, sales_order_details.warehouse_id
--   - Kolom: staff_warehouses.is_kepala_cabang, warehouse_types.is_main_warehouse
--
-- ATURAN KEAMANAN:
--   - TIDAK ADA DELETE / TRUNCATE
--   - Hanya ADD kolom/tabel + UPDATE backfill + INSERT gudang default (jika belum ada)
--   - Tidak mengubah ps_stock / ss_stock / qty transaksi lain
--   - Unique index hanya ditambah jika tidak ada duplikat (tanpa soft-delete baris)
--
-- Default gudang:
--   - Gudang Hikari Pegasus Sidoarjo (utama, backfill data lama)
--   - Gudang Eceran Sidoarjo (eceran client, stok awal 0)
-- Alternatif artisan: php artisan db:seed --class=ProductionMultiWarehouseSeeder
--
-- UNTUK IMPORT 1 FILE SQL (data real + warehouse_id sudah di INSERT):
--   1) php docs/scripts/build_production_warehouse_sql.php
--   2) Import: database/sql/pegasuso_production_import_with_warehouses.sql
--      (postfix otomatis jalankan pegasuso_production_fase2_schema_gap.sql)
--   ATAU setelah import dump biasa:
--      database/sql/pegasuso_production_multi_warehouse_upgrade.sql
--      database/sql/pegasuso_production_fase2_schema_gap.sql
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1) Schema gudang (warehouse_schema.sql ringkas)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `warehouse_types` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_type_name` VARCHAR(250) NOT NULL,
  `is_main_warehouse` TINYINT NOT NULL DEFAULT 0 COMMENT '1 = tipe gudang utama (hanya 1)',
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1=active, 0=deleted',
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouse_types_status_index` (`status`),
  KEY `warehouse_types_is_main_warehouse_index` (`is_main_warehouse`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col_wt_main := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouse_types' AND COLUMN_NAME = 'is_main_warehouse'
);
SET @sql_wt_main := IF(
  @col_wt_main = 0,
  'ALTER TABLE `warehouse_types` ADD COLUMN `is_main_warehouse` TINYINT NOT NULL DEFAULT 0 COMMENT ''1 = tipe gudang utama'' AFTER `warehouse_type_name`, ADD INDEX `warehouse_types_is_main_warehouse_index` (`is_main_warehouse`)',
  'SELECT ''skip warehouse_types.is_main_warehouse'' AS info'
);
PREPARE stmt_wt_main FROM @sql_wt_main; EXECUTE stmt_wt_main; DEALLOCATE PREPARE stmt_wt_main;

CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_name` VARCHAR(250) NOT NULL,
  `warehouse_type_id` BIGINT UNSIGNED NOT NULL,
  `warehouse_address` TEXT NULL DEFAULT NULL,
  `sidebar_menus` JSON NULL DEFAULT NULL,
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '1=aktif, 2=non-aktif, 0=deleted',
  `created_by` INT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `warehouses_status_index` (`status`),
  KEY `warehouses_warehouse_type_id_index` (`warehouse_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col_wh_sidebar := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'warehouses' AND COLUMN_NAME = 'sidebar_menus'
);
SET @sql_wh_sidebar := IF(
  @col_wh_sidebar = 0,
  'ALTER TABLE `warehouses` ADD COLUMN `sidebar_menus` JSON NULL DEFAULT NULL AFTER `warehouse_address`',
  'SELECT ''skip warehouses.sidebar_menus'' AS info'
);
PREPARE stmt_wh_sidebar FROM @sql_wh_sidebar; EXECUTE stmt_wh_sidebar; DEALLOCATE PREPARE stmt_wh_sidebar;

CREATE TABLE IF NOT EXISTS `staff_warehouses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `warehouse_id` BIGINT UNSIGNED NOT NULL,
  `is_kepala_cabang` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_warehouses_staff_id_warehouse_id_unique` (`staff_id`, `warehouse_id`),
  KEY `staff_warehouses_staff_id_index` (`staff_id`),
  KEY `staff_warehouses_warehouse_id_index` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @col_sw_kc := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_warehouses' AND COLUMN_NAME = 'is_kepala_cabang'
);
SET @sql_sw_kc := IF(
  @col_sw_kc = 0,
  'ALTER TABLE `staff_warehouses` ADD COLUMN `is_kepala_cabang` TINYINT(1) NOT NULL DEFAULT 0 AFTER `warehouse_id`',
  'SELECT ''skip staff_warehouses.is_kepala_cabang'' AS info'
);
PREPARE stmt_sw_kc FROM @sql_sw_kc; EXECUTE stmt_sw_kc; DEALLOCATE PREPARE stmt_sw_kc;

SET @col_staff_law := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staffs' AND COLUMN_NAME = 'last_active_warehouse_id'
);
SET @sql_staff_law := IF(
  @col_staff_law = 0,
  'ALTER TABLE `staffs` ADD COLUMN `last_active_warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `role_id`, ADD INDEX `staffs_last_active_warehouse_id_index` (`last_active_warehouse_id`)',
  'SELECT ''skip staffs.last_active_warehouse_id'' AS info'
);
PREPARE stmt_staff_law FROM @sql_staff_law; EXECUTE stmt_staff_law; DEALLOCATE PREPARE stmt_staff_law;

-- Stock Transfer (kosong di production — hanya buat tabel agar fitur fase2 tidak error)
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
  `status` tinyint NOT NULL DEFAULT 1,
  `created_by` int unsigned DEFAULT NULL,
  `acc_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`st_id`),
  UNIQUE KEY `stock_transfers_transfer_code_unique` (`transfer_code`),
  KEY `stock_transfers_from_wh_idx` (`from_warehouse_id`),
  KEY `stock_transfers_to_wh_idx` (`to_warehouse_id`),
  KEY `stock_transfers_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_transfer_details` (
  `std_id` int unsigned NOT NULL AUTO_INCREMENT,
  `st_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `product_variant_id` int unsigned NOT NULL,
  `unit_id` int unsigned NOT NULL,
  `qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `qty_received` decimal(18,4) DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`std_id`),
  KEY `stock_transfer_details_st_id_idx` (`st_id`),
  KEY `stock_transfer_details_pv_idx` (`product_variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2) Seed tipe + gudang default (INSERT saja jika belum ada)
-- -----------------------------------------------------------------------------
INSERT INTO `warehouse_types` (`warehouse_type_name`, `is_main_warehouse`, `status`, `created_at`, `updated_at`)
SELECT 'Gudang Besar', 1, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `warehouse_types`
  WHERE `warehouse_type_name` = 'Gudang Besar' AND `status` = 1
);

UPDATE `warehouse_types`
SET `is_main_warehouse` = 1, `updated_at` = NOW()
WHERE `warehouse_type_name` = 'Gudang Besar' AND `status` = 1;

SET @default_wt_id := (
  SELECT `id` FROM `warehouse_types`
  WHERE `warehouse_type_name` = 'Gudang Besar' AND `status` = 1
  ORDER BY `id` LIMIT 1
);

INSERT INTO `warehouses` (`warehouse_name`, `warehouse_type_id`, `warehouse_address`, `sidebar_menus`, `status`, `created_at`, `updated_at`)
SELECT
  'Gudang Hikari Pegasus Sidoarjo',
  @default_wt_id,
  'Pergudangan Meiko Abadi 2, Blok A8 - 9, Jalan Industri, Buduran, Sidoarjo',
  NULL,
  1,
  NOW(),
  NOW()
FROM DUAL
WHERE @default_wt_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `warehouses`
    WHERE `warehouse_name` = 'Gudang Hikari Pegasus Sidoarjo' AND `status` IN (1, 2)
  );

SET @default_wh_id := (
  SELECT `id` FROM `warehouses`
  WHERE `warehouse_name` = 'Gudang Hikari Pegasus Sidoarjo' AND `status` IN (1, 2)
  ORDER BY `id` LIMIT 1
);

SET @default_wh_id := IFNULL(
  @default_wh_id,
  (
    SELECT w.`id`
    FROM `warehouses` w
    INNER JOIN `warehouse_types` wt ON wt.`id` = w.`warehouse_type_id`
    WHERE w.`status` = 1 AND wt.`status` = 1 AND wt.`is_main_warehouse` = 1
    ORDER BY w.`id` LIMIT 1
  )
);

SET @default_wh_id := IFNULL(
  @default_wh_id,
  (SELECT `id` FROM `warehouses` WHERE `status` = 1 ORDER BY `id` LIMIT 1)
);

-- Tipe + gudang eceran (client)
INSERT INTO `warehouse_types` (`warehouse_type_name`, `is_main_warehouse`, `status`, `created_at`, `updated_at`)
SELECT 'Gudang Eceran', 0, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `warehouse_types`
  WHERE `warehouse_type_name` = 'Gudang Eceran' AND `status` = 1
);

SET @retail_wt_id := (
  SELECT `id` FROM `warehouse_types`
  WHERE `warehouse_type_name` = 'Gudang Eceran' AND `status` = 1
  ORDER BY `id` LIMIT 1
);

INSERT INTO `warehouses` (`warehouse_name`, `warehouse_type_id`, `warehouse_address`, `sidebar_menus`, `status`, `created_at`, `updated_at`)
SELECT
  'Gudang Eceran Sidoarjo',
  @retail_wt_id,
  'Pergudangan Meiko Abadi 2, Blok A9, Jalan Industri, Buduran, Sidoarjo',
  NULL,
  1,
  NOW(),
  NOW()
FROM DUAL
WHERE @retail_wt_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `warehouses`
    WHERE `warehouse_name` = 'Gudang Eceran Sidoarjo' AND `status` IN (1, 2)
  );

SET @retail_wh_id := (
  SELECT `id` FROM `warehouses`
  WHERE `warehouse_name` = 'Gudang Eceran Sidoarjo' AND `status` IN (1, 2)
  ORDER BY `id` LIMIT 1
);

-- -----------------------------------------------------------------------------
-- 3) Kolom warehouse_id di tabel stok & transaksi
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_stocks' AND COLUMN_NAME = 'warehouse_id') = 0,
  'ALTER TABLE `product_stocks` ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`, ADD INDEX `product_stocks_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''skip product_stocks.warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'supplies_stocks' AND COLUMN_NAME = 'warehouse_id') = 0,
  'ALTER TABLE `supplies_stocks` ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`, ADD INDEX `supplies_stocks_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''skip supplies_stocks.warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_opnames' AND COLUMN_NAME = 'warehouse_id') = 0,
  'ALTER TABLE `stock_opnames` ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `category_id`, ADD INDEX `stock_opnames_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''skip stock_opnames.warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_opname_bahans' AND COLUMN_NAME = 'warehouse_id') = 0,
  'ALTER TABLE `stock_opname_bahans` ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `staff_id`, ADD INDEX `stock_opname_bahans_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''skip stock_opname_bahans.warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'log_stocks' AND COLUMN_NAME = 'warehouse_id') = 0,
  'ALTER TABLE `log_stocks` ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `staff_id`, ADD INDEX `log_stocks_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''skip log_stocks.warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'retail_warehouse_id') = 0,
  'ALTER TABLE `sales_orders` ADD COLUMN `retail_warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `so_customer`, ADD INDEX `sales_orders_retail_warehouse_id_index` (`retail_warehouse_id`)',
  'SELECT ''skip sales_orders.retail_warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_order_details' AND COLUMN_NAME = 'warehouse_id') = 0,
  'ALTER TABLE `sales_order_details` ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`, ADD INDEX `sales_order_details_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''skip sales_order_details.warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 4) Backfill — semua data lama → gudang default (UPDATE saja, tanpa hapus baris)
-- -----------------------------------------------------------------------------
UPDATE `product_stocks` SET `warehouse_id` = @default_wh_id, `updated_at` = NOW()
WHERE `warehouse_id` IS NULL AND @default_wh_id IS NOT NULL;

UPDATE `supplies_stocks` SET `warehouse_id` = @default_wh_id, `updated_at` = NOW()
WHERE `warehouse_id` IS NULL AND @default_wh_id IS NOT NULL;

UPDATE `stock_opnames` SET `warehouse_id` = @default_wh_id, `updated_at` = NOW()
WHERE `warehouse_id` IS NULL AND @default_wh_id IS NOT NULL;

UPDATE `stock_opname_bahans` SET `warehouse_id` = @default_wh_id, `updated_at` = NOW()
WHERE `warehouse_id` IS NULL AND @default_wh_id IS NOT NULL;

UPDATE `log_stocks` SET `warehouse_id` = @default_wh_id, `updated_at` = NOW()
WHERE `warehouse_id` IS NULL AND @default_wh_id IS NOT NULL;

-- -----------------------------------------------------------------------------
-- 5) Assign semua staff aktif ke gudang default + set last_active_warehouse_id
-- -----------------------------------------------------------------------------
INSERT INTO `staff_warehouses` (`staff_id`, `warehouse_id`, `is_kepala_cabang`, `created_at`, `updated_at`)
SELECT s.`staff_id`, @default_wh_id, 0, NOW(), NOW()
FROM `staffs` s
WHERE s.`status` = 1
  AND @default_wh_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `staff_warehouses` sw
    WHERE sw.`staff_id` = s.`staff_id` AND sw.`warehouse_id` = @default_wh_id
  );

UPDATE `staffs` SET `last_active_warehouse_id` = @default_wh_id, `updated_at` = NOW()
WHERE @default_wh_id IS NOT NULL
  AND (`last_active_warehouse_id` IS NULL OR `last_active_warehouse_id` = 0)
  AND `status` = 1;

-- Staff aktif juga di-assign ke gudang eceran
INSERT INTO `staff_warehouses` (`staff_id`, `warehouse_id`, `is_kepala_cabang`, `created_at`, `updated_at`)
SELECT s.`staff_id`, @retail_wh_id, 0, NOW(), NOW()
FROM `staffs` s
WHERE s.`status` = 1
  AND @retail_wh_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `staff_warehouses` sw
    WHERE sw.`staff_id` = s.`staff_id` AND sw.`warehouse_id` = @retail_wh_id
  );

-- Stok 0 di gudang eceran (INSERT baru — data lama di gudang utama tidak diubah)
INSERT INTO `product_stocks` (
  `product_variant_id`, `product_id`, `unit_id`, `warehouse_id`,
  `ps_stock`, `status`, `created_at`, `updated_at`, `created_by`
)
SELECT
  ps.`product_variant_id`,
  ps.`product_id`,
  ps.`unit_id`,
  @retail_wh_id,
  0,
  1,
  NOW(),
  NOW(),
  NULL
FROM `product_stocks` ps
WHERE ps.`status` = 1
  AND ps.`warehouse_id` = @default_wh_id
  AND @retail_wh_id IS NOT NULL
  AND @default_wh_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `product_stocks` x
    WHERE x.`warehouse_id` = @retail_wh_id
      AND x.`product_variant_id` = ps.`product_variant_id`
      AND x.`unit_id` = ps.`unit_id`
  );

INSERT INTO `supplies_stocks` (
  `supplies_id`, `unit_id`, `warehouse_id`,
  `ss_stock`, `status`, `created_at`, `updated_at`, `created_by`
)
SELECT
  ss.`supplies_id`,
  ss.`unit_id`,
  @retail_wh_id,
  0,
  1,
  NOW(),
  NOW(),
  NULL
FROM `supplies_stocks` ss
WHERE ss.`status` = 1
  AND ss.`warehouse_id` = @default_wh_id
  AND @retail_wh_id IS NOT NULL
  AND @default_wh_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `supplies_stocks` x
    WHERE x.`warehouse_id` = @retail_wh_id
      AND x.`supplies_id` = ss.`supplies_id`
      AND x.`unit_id` = ss.`unit_id`
  );

-- -----------------------------------------------------------------------------
-- 6) Unique index — hanya jika tidak ada duplikat (tanpa ubah status baris)
-- -----------------------------------------------------------------------------
SET @ps_dup := (
  SELECT COUNT(*) FROM (
    SELECT `warehouse_id`, `product_variant_id`, `unit_id`, COUNT(*) AS c
    FROM `product_stocks`
    WHERE `warehouse_id` IS NOT NULL
    GROUP BY `warehouse_id`, `product_variant_id`, `unit_id`
    HAVING c > 1
  ) d
);

SET @sql_ps_uq := IF(
  @ps_dup = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_stocks' AND INDEX_NAME = 'product_stocks_warehouse_variant_unit_unique') = 0,
  'ALTER TABLE `product_stocks` ADD UNIQUE INDEX `product_stocks_warehouse_variant_unit_unique` (`warehouse_id`, `product_variant_id`, `unit_id`)',
  'SELECT ''skip product_stocks unique (duplikat atau sudah ada)'' AS info'
);
PREPARE stmt_ps_uq FROM @sql_ps_uq; EXECUTE stmt_ps_uq; DEALLOCATE PREPARE stmt_ps_uq;

SET @ss_dup := (
  SELECT COUNT(*) FROM (
    SELECT `warehouse_id`, `supplies_id`, `unit_id`, COUNT(*) AS c
    FROM `supplies_stocks`
    WHERE `warehouse_id` IS NOT NULL
    GROUP BY `warehouse_id`, `supplies_id`, `unit_id`
    HAVING c > 1
  ) d
);

SET @sql_ss_uq := IF(
  @ss_dup = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'supplies_stocks' AND INDEX_NAME = 'supplies_stocks_warehouse_supplies_unit_unique') = 0,
  'ALTER TABLE `supplies_stocks` ADD UNIQUE INDEX `supplies_stocks_warehouse_supplies_unit_unique` (`warehouse_id`, `supplies_id`, `unit_id`)',
  'SELECT ''skip supplies_stocks unique (duplikat atau sudah ada)'' AS info'
);
PREPARE stmt_ss_uq FROM @sql_ss_uq; EXECUTE stmt_ss_uq; DEALLOCATE PREPARE stmt_ss_uq;

SET FOREIGN_KEY_CHECKS = 1;

SELECT
  @default_wh_id AS default_warehouse_id,
  (SELECT `warehouse_name` FROM `warehouses` WHERE `id` = @default_wh_id) AS default_warehouse_name,
  @retail_wh_id AS retail_warehouse_id,
  (SELECT `warehouse_name` FROM `warehouses` WHERE `id` = @retail_wh_id) AS retail_warehouse_name,
  (SELECT COUNT(*) FROM `product_stocks` WHERE `warehouse_id` = @default_wh_id) AS product_stocks_main,
  (SELECT COUNT(*) FROM `product_stocks` WHERE `warehouse_id` = @retail_wh_id) AS product_stocks_retail,
  (SELECT COUNT(*) FROM `supplies_stocks` WHERE `warehouse_id` = @default_wh_id) AS supplies_stocks_main,
  (SELECT COUNT(*) FROM `supplies_stocks` WHERE `warehouse_id` = @retail_wh_id) AS supplies_stocks_retail,
  (SELECT COUNT(*) FROM `stock_opnames` WHERE `warehouse_id` = @default_wh_id) AS stock_opnames_backfilled,
  (SELECT COUNT(*) FROM `staff_warehouses` WHERE `warehouse_id` = @default_wh_id) AS staff_assigned_main,
  (SELECT COUNT(*) FROM `staff_warehouses` WHERE `warehouse_id` = @retail_wh_id) AS staff_assigned_retail,
  @ps_dup AS product_stock_duplicate_groups,
  @ss_dup AS supplies_stock_duplicate_groups,
  'pegasuso_production_multi_warehouse_upgrade OK' AS result;
