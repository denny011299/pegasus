-- =============================================================================
-- PEGASUS — Schema gap: DB production (pegasuso) → fase2/main
-- Jalankan SETELAH import dump / multi-warehouse upgrade.
-- Idempotent (ADD COLUMN IF NOT EXISTS pattern). Tanpa DELETE data.
--
-- Kurang vs fase2 (selain multi-gudang):
--   product_stocks: ps_safety_stock, ps_alert_stock, ps_min_order
--   product_variants: retail_unit, lead_time_days, safety_stock, safety_unit_id, qty_per_pallet
--   supplies: lead_time_days, safety_stock
--   log_stocks: log_saldo
--   sales_orders: retail_warehouse_id, ref_shipment_id, notes, cancel_reason, ...
--   sales_order_details: warehouse_id
--   Tabel baru: customer_product_returns, customer_supply_returns, external_*, dll.
-- =============================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();

-- -----------------------------------------------------------------------------
-- 1) Safety stock produk (variant + stok per gudang)
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'safety_stock') = 0,
  'ALTER TABLE `product_variants` ADD COLUMN `safety_stock` INT NOT NULL DEFAULT 0 AFTER `product_variant_alert`',
  'SELECT ''skip product_variants.safety_stock'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'safety_unit_id') = 0,
  'ALTER TABLE `product_variants` ADD COLUMN `safety_unit_id` INT NULL DEFAULT NULL AFTER `safety_stock`',
  'SELECT ''skip product_variants.safety_unit_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_stocks' AND COLUMN_NAME = 'ps_safety_stock') = 0,
  'ALTER TABLE `product_stocks` ADD COLUMN `ps_safety_stock` INT NOT NULL DEFAULT 0 AFTER `ps_stock`',
  'SELECT ''skip product_stocks.ps_safety_stock'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 2) retail_unit + lead_time (produk)
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'retail_unit') = 0,
  'ALTER TABLE `product_variants` ADD COLUMN `retail_unit` INT NULL AFTER `product_variant_stock`',
  'SELECT ''skip product_variants.retail_unit'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'lead_time_days') = 0,
  'ALTER TABLE `product_variants` ADD COLUMN `lead_time_days` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `product_variant_alert`',
  'SELECT ''skip product_variants.lead_time_days'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'qty_per_pallet') = 0,
  'ALTER TABLE `product_variants` ADD COLUMN `qty_per_pallet` INT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`',
  'SELECT ''skip product_variants.qty_per_pallet'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 3) ps_alert_stock + backfill dari product_variant_alert
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_stocks' AND COLUMN_NAME = 'ps_alert_stock') = 0,
  'ALTER TABLE `product_stocks` ADD COLUMN `ps_alert_stock` INT NOT NULL DEFAULT 0 AFTER `ps_safety_stock`',
  'SELECT ''skip product_stocks.ps_alert_stock'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `product_stocks` ps
INNER JOIN `product_variants` pv
  ON pv.`product_variant_id` = ps.`product_variant_id`
 AND pv.`unit_id` = ps.`unit_id`
 AND pv.`status` = 1
SET ps.`ps_alert_stock` = COALESCE(pv.`product_variant_alert`, 0)
WHERE COALESCE(ps.`ps_alert_stock`, 0) = 0
  AND COALESCE(pv.`product_variant_alert`, 0) > 0
  AND ps.`status` = 1
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_stocks' AND COLUMN_NAME = 'ps_alert_stock'
  );

-- -----------------------------------------------------------------------------
-- 4) ps_min_order
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_stocks' AND COLUMN_NAME = 'ps_min_order') = 0,
  'ALTER TABLE `product_stocks` ADD COLUMN `ps_min_order` INT NULL DEFAULT NULL AFTER `ps_alert_stock`',
  'SELECT ''skip product_stocks.ps_min_order'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 5) Supplies lead_time + safety_stock (header bahan)
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'lead_time_days') = 0,
  'ALTER TABLE `supplies` ADD COLUMN `lead_time_days` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `supplies_alert`',
  'SELECT ''skip supplies.lead_time_days'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'safety_stock') = 0,
  'ALTER TABLE `supplies` ADD COLUMN `safety_stock` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `lead_time_days`',
  'SELECT ''skip supplies.safety_stock'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 6) log_stocks.log_saldo
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'log_stocks' AND COLUMN_NAME = 'log_saldo') = 0,
  'ALTER TABLE `log_stocks` ADD COLUMN `log_saldo` DECIMAL(18,4) NULL DEFAULT NULL AFTER `log_jumlah`',
  'SELECT ''skip log_stocks.log_saldo'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 7) Sales order multi-gudang eceran
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'retail_warehouse_id') = 0,
  'ALTER TABLE `sales_orders` ADD COLUMN `retail_warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `so_customer`, ADD INDEX `sales_orders_retail_warehouse_id_index` (`retail_warehouse_id`)',
  'SELECT ''skip sales_orders.retail_warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_order_details' AND COLUMN_NAME = 'warehouse_id') = 0,
  'ALTER TABLE `sales_order_details` ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`, ADD INDEX `sales_order_details_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''skip sales_order_details.warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 8) Sales orders — shipment / notes (External API)
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'ref_shipment_id') = 0,
  'ALTER TABLE `sales_orders` ADD COLUMN `ref_shipment_id` VARCHAR(100) NULL AFTER `so_ref_number`, ADD UNIQUE INDEX `sales_orders_ref_shipment_id_unique` (`ref_shipment_id`)',
  'SELECT ''skip sales_orders.ref_shipment_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'notes') = 0,
  'ALTER TABLE `sales_orders` ADD COLUMN `notes` TEXT NULL AFTER `ref_shipment_id`',
  'SELECT ''skip sales_orders.notes'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'cancel_reason') = 0,
  'ALTER TABLE `sales_orders` ADD COLUMN `cancel_reason` TEXT NULL AFTER `notes`',
  'SELECT ''skip sales_orders.cancel_reason'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 9) sales_delivery_orders_details.unit_id
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_delivery_orders_details' AND COLUMN_NAME = 'unit_id') = 0,
  'ALTER TABLE `sales_delivery_orders_details` ADD COLUMN `unit_id` INT UNSIGNED NULL DEFAULT NULL AFTER `sdod_qty`',
  'SELECT ''skip sales_delivery_orders_details.unit_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 10) Retur produk / bahan (tabel baru — kosong OK)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `customer_product_returns` (
  `return_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_number` VARCHAR(40) NOT NULL,
  `customer_id` INT NOT NULL,
  `return_date` DATE NOT NULL,
  `ref_number` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `proof_path` VARCHAR(255) NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `acc_by` INT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`return_id`),
  UNIQUE KEY `cpr_return_number_unique` (`return_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_product_return_details` (
  `return_detail_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_id` INT NOT NULL,
  `product_variant_id` INT NOT NULL,
  `unit_id` INT NOT NULL,
  `warehouse_id` INT NULL,
  `destination_warehouse_id` INT NULL,
  `qty` BIGINT UNSIGNED NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`return_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_supply_returns` (
  `csr_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `csr_number` VARCHAR(40) NOT NULL,
  `customer_id` INT NOT NULL,
  `so_id` INT NULL,
  `return_date` DATE NOT NULL,
  `notes` TEXT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_by` INT NULL,
  `acc_by` INT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`csr_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_supply_return_details` (
  `csrd_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `csr_id` INT NOT NULL,
  `supplies_id` INT NOT NULL,
  `unit_id` INT NOT NULL,
  `qty` BIGINT UNSIGNED NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`csrd_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 12) Sales orders — status 4–7 (scheduled, belum/sudah terkirim, dibatalkan)
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders` MODIFY `status` TINYINT NOT NULL DEFAULT 1 COMMENT ''1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong), 5 = Belum Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status), 6 = Sudah Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status)''',
  'SELECT ''skip sales_orders.status 4-6'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'cancel_reason') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders` ADD COLUMN `cancel_reason` TEXT NULL COMMENT ''Alasan pembatalan (PUT /shipments/{ref}/cancel, body.reason)'' AFTER `notes`',
  'SELECT ''skip sales_orders.cancel_reason v2'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders` MODIFY `status` TINYINT NOT NULL DEFAULT 1 COMMENT ''1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong), 5 = Belum Terkirim (API, belum ada endpoint yang menghasilkan ini), 6 = Sudah Terkirim (API, lewat PATCH /shipments/{ref}/change-status dari status 4 — MEMOTONG STOK sungguhan), 7 = Dibatalkan (API, lewat PUT /shipments/{ref}/cancel, stok dikembalikan kalau sebelumnya Confirmed atau Sudah Terkirim)''',
  'SELECT ''skip sales_orders.status 7 final'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 13) Stock Transfer — approval QC/Ops + received_unit_id
-- -----------------------------------------------------------------------------
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_transfers' AND COLUMN_NAME = 'qc_approved_by') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_transfers') = 1,
  'ALTER TABLE `stock_transfers`
     ADD COLUMN `qc_approved_by` INT UNSIGNED NULL AFTER `acc_by`,
     ADD COLUMN `qc_approved_at` TIMESTAMP NULL AFTER `qc_approved_by`,
     ADD COLUMN `ops_approved_by` INT UNSIGNED NULL AFTER `qc_approved_at`,
     ADD COLUMN `ops_approved_at` TIMESTAMP NULL AFTER `ops_approved_by`',
  'SELECT ''skip stock_transfers approval columns'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_transfer_details' AND COLUMN_NAME = 'received_unit_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_transfer_details') = 1,
  'ALTER TABLE `stock_transfer_details` ADD COLUMN `received_unit_id` INT UNSIGNED NULL AFTER `unit_id`',
  'SELECT ''skip stock_transfer_details.received_unit_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_transfers') = 1,
  'ALTER TABLE `stock_transfers` MODIFY `status` TINYINT NOT NULL DEFAULT 1 COMMENT ''0=deleted,1=pending,2=kirim,3=ditolak,4=diterima''',
  'SELECT ''skip stock_transfers.status comment'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------------------------
-- 11) Catat migrations penting
-- -----------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_24_140000_add_safety_stock_columns', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_24_140000_add_safety_stock_columns');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_27_002744_add_retail_unit_to_product_variants_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_27_002744_add_retail_unit_to_product_variants_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_27_221000_add_ps_alert_stock_to_product_stocks_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_27_221000_add_ps_alert_stock_to_product_stocks_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_09_130000_add_ps_min_order_to_product_stocks_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_09_130000_add_ps_min_order_to_product_stocks_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_29_150000_add_lead_time_minimum_order_columns', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_29_150000_add_lead_time_minimum_order_columns');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_03_150000_add_log_saldo_to_log_stocks_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_03_150000_add_log_saldo_to_log_stocks_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_03_010000_add_qty_per_pallet_to_product_variants_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_03_010000_add_qty_per_pallet_to_product_variants_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_11_130000_add_ref_shipment_id_and_scheduled_status_to_sales_orders', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_11_130000_add_ref_shipment_id_and_scheduled_status_to_sales_orders');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_12_100000_add_notes_to_sales_orders_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_12_100000_add_notes_to_sales_orders_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_12_110000_add_not_delivered_and_delivered_statuses_to_sales_orders', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_12_110000_add_not_delivered_and_delivered_statuses_to_sales_orders');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_12_120000_add_cancel_reason_and_cancelled_status_to_sales_orders', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_12_120000_add_cancel_reason_and_cancelled_status_to_sales_orders');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_13_090000_update_sales_orders_status_comment_for_delivered_deduction', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_13_090000_update_sales_orders_status_comment_for_delivered_deduction');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_29_160000_add_received_unit_id_to_stock_transfer_details_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_29_160000_add_received_unit_id_to_stock_transfer_details_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_24_003700_add_approval_columns_to_stock_transfers_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_24_003700_add_approval_columns_to_stock_transfers_table');

SELECT 'pegasuso_production_fase2_schema_gap OK' AS result;
