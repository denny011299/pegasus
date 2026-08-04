-- =============================================================================
-- GAP SCHEMA: okeh8644_pegasus ← pegasus (hanya yang kurang)
-- Idempotent. Jalankan di DB okeh8644_pegasus.
-- =============================================================================

SET NAMES utf8mb4;

-- 1) product_variants.qty_per_pallet
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'qty_per_pallet') = 0,
  'ALTER TABLE `product_variants` ADD COLUMN `qty_per_pallet` INT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`',
  'SELECT ''skip qty_per_pallet'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) log_stocks.log_saldo
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'log_stocks' AND COLUMN_NAME = 'log_saldo') = 0,
  'ALTER TABLE `log_stocks` ADD COLUMN `log_saldo` DECIMAL(18,4) NULL DEFAULT NULL AFTER `log_jumlah`',
  'SELECT ''skip log_saldo'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) sales_delivery_orders_details.unit_id
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sales_delivery_orders_details' AND COLUMN_NAME = 'unit_id') = 0,
  'ALTER TABLE `sales_delivery_orders_details` ADD COLUMN `unit_id` INT UNSIGNED NULL DEFAULT NULL AFTER `sdod_qty`',
  'SELECT ''skip unit_id delivery'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) customer_supply_returns.so_id nullable
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_supply_returns') = 1,
  'ALTER TABLE `customer_supply_returns` MODIFY COLUMN `so_id` INT NULL',
  'SELECT ''skip so_id nullable'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5) customer_product_returns (+ details)
CREATE TABLE IF NOT EXISTS `customer_product_returns` (
  `return_id` int unsigned NOT NULL AUTO_INCREMENT,
  `return_number` varchar(40) NOT NULL,
  `customer_id` int NOT NULL,
  `return_date` date NOT NULL,
  `ref_number` varchar(100) DEFAULT NULL,
  `notes` text,
  `proof_path` varchar(255) NOT NULL,
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '0=deleted, 1=pending, 2=accepted, 3=declined',
  `created_by` int DEFAULT NULL,
  `acc_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`return_id`),
  UNIQUE KEY `customer_product_returns_return_number_unique` (`return_number`),
  KEY `customer_product_returns_customer_id_index` (`customer_id`),
  KEY `customer_product_returns_return_date_index` (`return_date`),
  KEY `customer_product_returns_status_index` (`status`),
  KEY `customer_product_returns_created_by_index` (`created_by`),
  KEY `customer_product_returns_acc_by_index` (`acc_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_product_return_details` (
  `return_detail_id` int unsigned NOT NULL AUTO_INCREMENT,
  `return_id` int NOT NULL,
  `product_variant_id` int NOT NULL,
  `unit_id` int NOT NULL,
  `warehouse_id` int NOT NULL,
  `qty` bigint unsigned NOT NULL,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`return_detail_id`),
  UNIQUE KEY `cpr_detail_item_unique` (`return_id`,`product_variant_id`,`unit_id`,`warehouse_id`),
  KEY `customer_product_return_details_return_id_index` (`return_id`),
  KEY `customer_product_return_details_product_variant_id_index` (`product_variant_id`),
  KEY `customer_product_return_details_unit_id_index` (`unit_id`),
  KEY `customer_product_return_details_warehouse_id_index` (`warehouse_id`),
  KEY `customer_product_return_details_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'okeh8644_schema_gap OK' AS result;
