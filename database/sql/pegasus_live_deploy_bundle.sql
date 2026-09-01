-- =============================================================================
-- PEGASUS LIVE DEPLOY BUNDLE — patch schema pasca fase2 (idempotent)
-- Branch: fase-2 | Update: 2026-09-01
--
-- JALANKAN SETELAH salah satu:
--   A) Import dump: database/sql/pegasus_live_fase2_export_6.sql
--   B) database/sql/fase2_schema_all.sql (server lama belum full fase2)
--
-- Urutan praktis ke live:
--   1. Backup DB production
--   2. Import export_6 ATAU fase2_schema_all.sql
--   3. File INI (pegasus_live_deploy_bundle.sql)
--   4. (Opsional) seed_external_api_from_snapshot.sql
--   5. Deploy kode PHP (lihat database/sql/_RUN_ORDER.md)
--
-- Aman diulang. Tidak TRUNCATE data bisnis (kecuali bagian CSR — lihat catatan).
-- =============================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();


-- #############################################################################
-- BLOK 1 — Stock Opname per gudang (warehouse_id header)
-- Sumber: 2026_08_20_180000_add_warehouse_id_to_stock_opnames_tables.sql
-- #############################################################################

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames' AND COLUMN_NAME = 'warehouse_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames') = 1,
  'ALTER TABLE `stock_opnames`
     ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `category_id`,
     ADD INDEX `stock_opnames_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''skip stock_opnames.warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans' AND COLUMN_NAME = 'warehouse_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans') = 1,
  'ALTER TABLE `stock_opname_bahans`
     ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `staff_id`,
     ADD INDEX `stock_opname_bahans_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''skip stock_opname_bahans.warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @main_wh := (
  SELECT w.id
  FROM warehouses w
  INNER JOIN warehouse_types wt ON wt.id = w.warehouse_type_id
  WHERE w.status = 1 AND wt.status = 1 AND wt.is_main_warehouse = 1
  ORDER BY w.id
  LIMIT 1
);

UPDATE stock_opnames
SET warehouse_id = @main_wh
WHERE warehouse_id IS NULL AND @main_wh IS NOT NULL;

UPDATE stock_opname_bahans
SET warehouse_id = @main_wh
WHERE warehouse_id IS NULL AND @main_wh IS NOT NULL;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_20_180000_add_warehouse_id_to_stock_opnames_tables',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_20_180000_add_warehouse_id_to_stock_opnames_tables');

SELECT 'BLOK 1 stock_opname warehouse_id OK' AS result;


-- #############################################################################
-- BLOK 2 — Stock Opname detail: kolom touched
-- Sumber: 2026_08_14_010000_add_touched_to_stock_opname_details_tables.sql
-- #############################################################################

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_details' AND COLUMN_NAME = 'stod_touched') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_details') = 1,
  'ALTER TABLE `stock_opname_details` ADD COLUMN `stod_touched` TINYINT(1) NOT NULL DEFAULT 0 AFTER `stod_notes`',
  'SELECT ''skip stock_opname_details.stod_touched'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_detail_bahans' AND COLUMN_NAME = 'stobd_touched') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_detail_bahans') = 1,
  'ALTER TABLE `stock_opname_detail_bahans` ADD COLUMN `stobd_touched` TINYINT(1) NOT NULL DEFAULT 0 AFTER `stobd_notes`',
  'SELECT ''skip stock_opname_detail_bahans.stobd_touched'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_14_010000_add_touched_to_stock_opname_details_tables',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_14_010000_add_touched_to_stock_opname_details_tables');

SELECT 'BLOK 2 stock_opname touched OK' AS result;


-- #############################################################################
-- BLOK 3 — Stock Transfer: approval QC + Ops (utama → eceran)
-- Sumber: 2026_08_24_003700_add_approval_columns_to_stock_transfers_table.sql
-- #############################################################################

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_transfers' AND COLUMN_NAME = 'qc_approved_by') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_transfers') = 1,
  'ALTER TABLE `stock_transfers`
     ADD COLUMN `qc_approved_by` INT UNSIGNED NULL AFTER `acc_by`,
     ADD COLUMN `qc_approved_at` TIMESTAMP NULL AFTER `qc_approved_by`,
     ADD COLUMN `ops_approved_by` INT UNSIGNED NULL AFTER `qc_approved_at`,
     ADD COLUMN `ops_approved_at` TIMESTAMP NULL AFTER `ops_approved_by`',
  'SELECT ''skip stock_transfers approval columns'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_24_003700_add_approval_columns_to_stock_transfers_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_24_003700_add_approval_columns_to_stock_transfers_table');

SELECT 'BLOK 3 stock_transfer approval OK' AS result;


-- #############################################################################
-- BLOK 4 — productions.warehouse_id (header gudang asal produksi)
-- Sumber: add_productions_warehouse_id.sql
-- #############################################################################

SET @col_prod_wh_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = @db
    AND TABLE_NAME = 'productions'
    AND COLUMN_NAME = 'warehouse_id'
);

SET @sql_prod_wh := IF(
  @col_prod_wh_exists = 0,
  'ALTER TABLE `productions`
     ADD COLUMN `warehouse_id` BIGINT UNSIGNED NOT NULL DEFAULT 1 AFTER `production_created_by`,
     ADD INDEX `productions_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''skip productions.warehouse_id'' AS info'
);

PREPARE stmt_prod_wh FROM @sql_prod_wh;
EXECUTE stmt_prod_wh;
DEALLOCATE PREPARE stmt_prod_wh;

SET @main_wh_id := IFNULL(@main_wh, (
  SELECT w.`id`
  FROM `warehouses` w
  INNER JOIN `warehouse_types` wt ON wt.`id` = w.`warehouse_type_id`
  WHERE w.`status` = 1 AND wt.`is_main_warehouse` = 1
  ORDER BY w.`id`
  LIMIT 1
));
SET @main_wh_id := IFNULL(@main_wh_id, 1);

UPDATE `productions`
SET `warehouse_id` = @main_wh_id, `updated_at` = NOW()
WHERE `warehouse_id` IS NULL OR `warehouse_id` = 0;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_01_000000_add_warehouse_id_to_productions_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_01_000000_add_warehouse_id_to_productions_table'
  );

SELECT 'BLOK 4 productions.warehouse_id OK' AS result;


-- #############################################################################
-- BLOK 5 — PMO / External API ref_* columns (BLOCKER Kas Sales, sync PMO)
-- Sumber: add_pmo_ref_columns.sql
-- #############################################################################

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cash_sales' AND COLUMN_NAME = 'ref_payment_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cash_sales') = 1,
  'ALTER TABLE `cash_sales`
     ADD COLUMN `ref_payment_id` VARCHAR(100) NULL COMMENT ''Referensi pembayaran dari sistem eksternal'' AFTER `cs_id`,
     ADD UNIQUE INDEX `cash_sales_ref_payment_id_unique` (`ref_payment_id`)',
  'SELECT ''skip cash_sales.ref_payment_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cash_armadas' AND COLUMN_NAME = 'ref_payment_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cash_armadas') = 1,
  'ALTER TABLE `cash_armadas`
     ADD COLUMN `ref_payment_id` VARCHAR(100) NULL COMMENT ''Referensi pembayaran dari sistem eksternal'' AFTER `cr_id`,
     ADD UNIQUE INDEX `cash_armadas_ref_payment_id_unique` (`ref_payment_id`)',
  'SELECT ''skip cash_armadas.ref_payment_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'staffs' AND COLUMN_NAME = 'external_ref_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'staffs') = 1,
  'ALTER TABLE `staffs`
     ADD COLUMN `external_ref_id` VARCHAR(191) NULL AFTER `staff_code`,
     ADD UNIQUE INDEX `staffs_external_ref_id_unique` (`external_ref_id`)',
  'SELECT ''skip staffs.external_ref_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'ref_product_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products') = 1,
  'ALTER TABLE `products`
     ADD COLUMN `ref_product_id` BIGINT UNSIGNED NULL COMMENT ''ref_product_id pada sistem PMO'' AFTER `product_id`,
     ADD UNIQUE INDEX `products_ref_product_id_unique` (`ref_product_id`)',
  'SELECT ''skip products.ref_product_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'units' AND COLUMN_NAME = 'ref_unit_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'units') = 1,
  'ALTER TABLE `units`
     ADD COLUMN `ref_unit_id` BIGINT UNSIGNED NULL COMMENT ''unit_id pada sistem PMO'' AFTER `unit_id`,
     ADD UNIQUE INDEX `units_ref_unit_id_unique` (`ref_unit_id`)',
  'SELECT ''skip units.ref_unit_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'units') = 1,
  "ALTER TABLE `units` MODIFY `ref_unit_id` BIGINT UNSIGNED NULL COMMENT 'unit_id pada sistem PMO'",
  'SELECT ''skip units.ref_unit_id widen'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products') = 1,
  "ALTER TABLE `products` MODIFY `ref_product_id` BIGINT UNSIGNED NULL COMMENT 'ref_product_id pada sistem PMO'",
  'SELECT ''skip products.ref_product_id widen'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'ref_supplies_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies') = 1,
  'ALTER TABLE `supplies`
     ADD COLUMN `ref_supplies_id` INT NULL COMMENT ''supplies_id pada sistem PMO'' AFTER `supplies_id`,
     ADD UNIQUE INDEX `supplies_ref_supplies_id_unique` (`ref_supplies_id`)',
  'SELECT ''skip supplies.ref_supplies_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'ref_armada_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customers') = 1,
  "ALTER TABLE `customers`
     ADD COLUMN `ref_armada_id` BIGINT UNSIGNED NULL COMMENT 'armada_id pada sistem PMO' AFTER `customer_code`,
     ADD UNIQUE INDEX `customers_ref_armada_id_unique` (`ref_armada_id`)",
  'SELECT ''skip customers.ref_armada_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'ref_shipment_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders`
     ADD COLUMN `ref_shipment_id` VARCHAR(100) NULL COMMENT ''Referensi shipment dari sistem eksternal'' AFTER `so_ref_number`,
     ADD UNIQUE INDEX `sales_orders_ref_shipment_id_unique` (`ref_shipment_id`)',
  'SELECT ''skip sales_orders.ref_shipment_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_29_030000_add_ref_payment_id_to_cash_payment_tables',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_29_030000_add_ref_payment_id_to_cash_payment_tables');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_08_120000_add_external_ref_id_to_staffs_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_08_120000_add_external_ref_id_to_staffs_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_17_090000_add_ref_supplies_id_to_supplies_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_17_090000_add_ref_supplies_id_to_supplies_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_20_090000_widen_pmo_reference_id_columns',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_20_090000_widen_pmo_reference_id_columns');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_23_090000_add_ref_armada_id_to_customers_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_23_090000_add_ref_armada_id_to_customers_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_11_130000_add_ref_shipment_id_and_scheduled_status_to_sales_orders',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_11_130000_add_ref_shipment_id_and_scheduled_status_to_sales_orders');

SELECT 'BLOK 5 add_pmo_ref_columns OK' AS result;


-- #############################################################################
-- BLOK 6 — External API: tabel platform (DDL saja, tanpa seed)
-- Sumber: 2026_07_28_220000_create_external_applications_table.sql
--         2026_08_17_090000_create_external_api_endpoints_table.sql
-- Seed opsional: seed_external_api_from_snapshot.sql (file terpisah)
-- #############################################################################

CREATE TABLE IF NOT EXISTS `external_applications` (
  `external_application_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_code` VARCHAR(100) NOT NULL,
  `application_name` VARCHAR(150) NOT NULL,
  `company` VARCHAR(150) NULL DEFAULT NULL,
  `contact_name` VARCHAR(150) NULL DEFAULT NULL,
  `contact_email` VARCHAR(150) NULL DEFAULT NULL,
  `description` TEXT NULL,
  `application_status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_by` INT NULL DEFAULT NULL,
  `updated_by` INT NULL DEFAULT NULL,
  `status` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`external_application_id`),
  UNIQUE KEY `external_applications_code_unique` (`application_code`),
  KEY `external_applications_status_idx` (`status`, `application_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `external_api_keys` (
  `external_api_key_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `external_application_id` BIGINT UNSIGNED NOT NULL,
  `key_name` VARCHAR(150) NOT NULL,
  `environment` VARCHAR(20) NOT NULL DEFAULT 'production',
  `key_prefix` VARCHAR(64) NOT NULL,
  `key_hash` VARCHAR(64) NOT NULL,
  `key_last_four` VARCHAR(8) NOT NULL,
  `key_status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `revoked_at` TIMESTAMP NULL DEFAULT NULL,
  `revoked_by` INT NULL DEFAULT NULL,
  `created_by` INT NULL DEFAULT NULL,
  `status` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`external_api_key_id`),
  UNIQUE KEY `external_api_keys_prefix_unique` (`key_prefix`),
  KEY `external_api_keys_application_idx` (`external_application_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `external_api_request_logs` (
  `external_api_request_log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `external_application_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `external_api_key_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `application_name` VARCHAR(150) NULL DEFAULT NULL,
  `key_name` VARCHAR(150) NULL DEFAULT NULL,
  `method` VARCHAR(10) NOT NULL,
  `endpoint` VARCHAR(255) NOT NULL,
  `route_name` VARCHAR(150) NULL DEFAULT NULL,
  `api_version` VARCHAR(20) NULL DEFAULT NULL,
  `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `duration_ms` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  `requested_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`external_api_request_log_id`),
  KEY `external_api_request_logs_requested_at_idx` (`requested_at`),
  KEY `external_api_request_logs_application_idx` (`external_application_id`, `requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `external_api_endpoints` (
  `external_api_endpoint_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `endpoint_key` VARCHAR(150) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_public_docs_show` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`external_api_endpoint_id`),
  UNIQUE KEY `external_api_endpoints_key_unique` (`endpoint_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_28_220000_create_external_applications_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_28_220000_create_external_applications_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_17_090000_create_external_api_endpoints_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_17_090000_create_external_api_endpoints_table');

SELECT 'BLOK 6 external_api DDL OK' AS result;


-- #############################################################################
-- BLOK 7 — Default retail_unit = Piece (multi-gudang eceran)
-- Sumber: set_default_retail_unit_piece.sql
-- #############################################################################

SET @piece_unit_id := (
  SELECT `unit_id`
  FROM `units`
  WHERE `status` = 1
    AND (
      LOWER(TRIM(`unit_name`)) = 'piece'
      OR LOWER(TRIM(`unit_short_name`)) IN ('pcs', 'piece')
    )
  ORDER BY `unit_id`
  LIMIT 1
);

UPDATE `product_variants`
SET `retail_unit` = @piece_unit_id, `updated_at` = NOW()
WHERE `status` = 1
  AND (`retail_unit` IS NULL OR `retail_unit` = 0)
  AND @piece_unit_id IS NOT NULL;

SELECT 'BLOK 7 set_default_retail_unit_piece OK' AS result,
       @piece_unit_id AS piece_unit_id;


-- #############################################################################
-- BLOK 8 — Perbaiki stok gudang eceran (hanya satuan retail_unit)
-- Sumber: fix_retail_warehouse_stock_units.sql
-- #############################################################################

SET @retail_wh_id := (
  SELECT w.`id`
  FROM `warehouses` w
  INNER JOIN `warehouse_types` wt ON wt.`id` = w.`warehouse_type_id`
  WHERE w.`status` = 1 AND wt.`status` = 1 AND wt.`is_main_warehouse` = 0
  ORDER BY w.`id`
  LIMIT 1
);

UPDATE `product_stocks` ps
INNER JOIN `product_variants` pv ON pv.`product_variant_id` = ps.`product_variant_id`
SET ps.`status` = 0, ps.`updated_at` = NOW()
WHERE ps.`warehouse_id` = @retail_wh_id
  AND ps.`status` = 1
  AND @retail_wh_id IS NOT NULL
  AND (
    pv.`retail_unit` IS NULL
    OR pv.`retail_unit` = 0
    OR ps.`unit_id` <> pv.`retail_unit`
  );

INSERT INTO `product_stocks` (
  `product_variant_id`, `product_id`, `unit_id`, `warehouse_id`,
  `ps_stock`, `status`, `created_at`, `updated_at`, `created_by`
)
SELECT
  pv.`product_variant_id`, pv.`product_id`, pv.`retail_unit`, @retail_wh_id,
  0, 1, NOW(), NOW(), NULL
FROM `product_variants` pv
WHERE pv.`status` = 1
  AND pv.`retail_unit` IS NOT NULL AND pv.`retail_unit` > 0
  AND @retail_wh_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM `product_stocks` x
    WHERE x.`warehouse_id` = @retail_wh_id
      AND x.`product_variant_id` = pv.`product_variant_id`
      AND x.`unit_id` = pv.`retail_unit`
      AND x.`status` = 1
  );

SELECT 'BLOK 8 fix_retail_warehouse_stock_units OK' AS result,
       @retail_wh_id AS retail_warehouse_id;


-- #############################################################################
-- BLOK 9 — Dedup product_stocks + unique index
-- Sumber: fix_product_stocks_duplicates.sql
-- #############################################################################

UPDATE product_stocks ps
INNER JOIN (
    SELECT
        warehouse_id,
        product_variant_id,
        unit_id,
        SUBSTRING_INDEX(
            GROUP_CONCAT(ps_id ORDER BY status DESC, ps_stock DESC, ps_id ASC),
            ',', 1
        ) AS keep_id,
        SUM(ps_stock) AS total_stock,
        MAX(ps_safety_stock) AS max_safety,
        MAX(ps_alert_stock) AS max_alert
    FROM product_stocks
    WHERE warehouse_id IS NOT NULL
    GROUP BY warehouse_id, product_variant_id, unit_id
    HAVING COUNT(*) > 1
) d ON d.warehouse_id = ps.warehouse_id
   AND d.product_variant_id = ps.product_variant_id
   AND d.unit_id = ps.unit_id
   AND ps.ps_id = d.keep_id
SET ps.ps_stock = d.total_stock,
    ps.ps_safety_stock = d.max_safety,
    ps.ps_alert_stock = d.max_alert,
    ps.status = 1,
    ps.updated_at = NOW();

UPDATE product_stocks ps
INNER JOIN (
    SELECT
        warehouse_id,
        product_variant_id,
        unit_id,
        SUBSTRING_INDEX(
            GROUP_CONCAT(ps_id ORDER BY status DESC, ps_stock DESC, ps_id ASC),
            ',', 1
        ) AS keep_id
    FROM product_stocks
    WHERE warehouse_id IS NOT NULL
    GROUP BY warehouse_id, product_variant_id, unit_id
    HAVING COUNT(*) > 1
) d ON d.warehouse_id = ps.warehouse_id
   AND d.product_variant_id = ps.product_variant_id
   AND d.unit_id = ps.unit_id
   AND ps.ps_id <> d.keep_id
SET ps.status = 0,
    ps.warehouse_id = NULL,
    ps.ps_stock = 0,
    ps.updated_at = NOW();

SET @ps_dup := (
    SELECT COUNT(*) FROM (
        SELECT warehouse_id, product_variant_id, unit_id, COUNT(*) AS c
        FROM product_stocks
        WHERE warehouse_id IS NOT NULL
        GROUP BY warehouse_id, product_variant_id, unit_id
        HAVING c > 1
    ) x
);

SET @idx_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'product_stocks'
      AND INDEX_NAME = 'product_stocks_warehouse_variant_unit_unique'
);

SET @sql_uq := IF(
    @ps_dup = 0 AND @idx_exists = 0,
    'ALTER TABLE `product_stocks`
       ADD UNIQUE INDEX `product_stocks_warehouse_variant_unit_unique`
         (`warehouse_id`, `product_variant_id`, `unit_id`)',
    'SELECT ''skip product_stocks unique index (duplikat atau sudah ada)'' AS info'
);
PREPARE stmt_uq FROM @sql_uq; EXECUTE stmt_uq; DEALLOCATE PREPARE stmt_uq;

SELECT 'BLOK 9 fix_product_stocks_duplicates OK' AS result,
       @ps_dup AS remaining_duplicate_groups;


-- #############################################################################
-- VERIFIKASI AKHIR
-- #############################################################################

SELECT
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'productions' AND COLUMN_NAME = 'warehouse_id') AS prod_wh_col,
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cash_sales' AND COLUMN_NAME = 'ref_payment_id') AS ref_payment_col,
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_transfers' AND COLUMN_NAME = 'source_type') AS st_source_col,
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames' AND COLUMN_NAME = 'warehouse_id') AS so_wh_col,
  'pegasus_live_deploy_bundle OK' AS result;
