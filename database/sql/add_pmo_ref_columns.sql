-- =============================================================================
-- PMO / External API — kolom rujukan (ref_*) yang TIDAK ada di fase2_schema_all.sql
-- Jalankan sekali di phpMyAdmin (dev/live) jika insert kas/API/sync PMO gagal
-- karena "Unknown column 'ref_payment_id'" atau kolom ref_* serupa.
-- Idempotent — aman diulang.
--
-- Sumber DDL: pegasus_live_fase2_export_5 + migration SQL terpisah di folder ini.
-- =============================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();

-- -----------------------------------------------------------------------------
-- 1) cash_sales.ref_payment_id  (BLOCKER: Kas Sales + External API payments)
-- Migration: 2026_07_29_030000_add_ref_payment_id_to_cash_payment_tables
-- -----------------------------------------------------------------------------
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

-- -----------------------------------------------------------------------------
-- 2) cash_armadas.ref_payment_id  (BLOCKER: Kas Armada + External API payments)
-- -----------------------------------------------------------------------------
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

-- -----------------------------------------------------------------------------
-- 3) staffs.external_ref_id  (External API master/sales — staff_id path param)
-- Migration: 2026_08_08_120000_add_external_ref_id_to_staffs_table
-- -----------------------------------------------------------------------------
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

-- -----------------------------------------------------------------------------
-- 4) products.ref_product_id  (Sync PMO produk + External API /produk)
-- Awalnya di create_products migration; DB lama sering belum punya kolom ini.
-- Langsung BIGINT UNSIGNED (id PMO 16 digit).
-- -----------------------------------------------------------------------------
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

-- -----------------------------------------------------------------------------
-- 5) units.ref_unit_id  (Sync PMO satuan + External API /master/units)
-- -----------------------------------------------------------------------------
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

-- -----------------------------------------------------------------------------
-- 6) Perlebar ref_product_id / ref_unit_id ke BIGINT jika masih INT (DB lama)
-- Migration: 2026_08_20_090000_widen_pmo_reference_id_columns
-- -----------------------------------------------------------------------------
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

-- -----------------------------------------------------------------------------
-- 7) supplies.ref_supplies_id  (External API /bahan)
-- Migration: 2026_08_17_090000_add_ref_supplies_id_to_supplies_table
-- -----------------------------------------------------------------------------
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

-- -----------------------------------------------------------------------------
-- 8) customers.ref_armada_id  (Sync PMO armada)
-- Migration: 2026_08_23_090000_add_ref_armada_id_to_customers_table
-- -----------------------------------------------------------------------------
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

-- -----------------------------------------------------------------------------
-- 9) sales_orders.ref_shipment_id  (External API /shipments/*)
-- Migration: 2026_08_11_130000_add_ref_shipment_id_and_scheduled_status_to_sales_orders
-- (Sudah ada di sebagian server; tetap idempotent.)
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'ref_shipment_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders`
     ADD COLUMN `ref_shipment_id` VARCHAR(100) NULL COMMENT ''Referensi shipment dari sistem eksternal (POST /shipments/scheduled)'' AFTER `so_ref_number`,
     ADD UNIQUE INDEX `sales_orders_ref_shipment_id_unique` (`ref_shipment_id`)',
  'SELECT ''skip sales_orders.ref_shipment_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- Catat migration records (supaya artisan migrate tidak bentrok)
-- -----------------------------------------------------------------------------
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

SELECT 'add_pmo_ref_columns OK' AS result;
