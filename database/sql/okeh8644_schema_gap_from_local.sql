-- Generated from local_pegasus.sql vs live DB okeh8644_pegasus
-- 1 missing table(s), 8 missing column(s)
-- Dump lokal TIDAK berisi: external_applications, external_api_keys, external_api_request_logs
-- Jalankan juga: database/sql/create_external_applications_table.sql

-- =============================================================================
-- GAP SCHEMA: okeh8644_pegasus <- database/local_pegasus.sql
-- Schema only, tanpa data. Idempotent. Jalankan di DB okeh8644_pegasus.
-- =============================================================================

SET NAMES utf8mb4;
SET @db := DATABASE();

-- TABLE external_api_endpoints
CREATE TABLE IF NOT EXISTS `external_api_endpoints` (
  `external_api_endpoint_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `endpoint_key` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ApiEndpointDoc::key() — identitas unik endpoint lintas semua versi API',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Endpoint bisa dipanggil — dicek di AuthenticateExternalApi sebelum autentikasi API Key',
  `is_public_docs_show` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Endpoint muncul di halaman dokumentasi publik /api-docs',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`external_api_endpoint_id`),
  UNIQUE KEY `external_api_endpoints_key_unique` (`endpoint_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- COLUMNS customer_product_return_details
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_return_details' AND COLUMN_NAME = 'destination_warehouse_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_return_details') = 1,
  'ALTER TABLE `customer_product_return_details` ADD COLUMN `destination_warehouse_id` bigint unsigned DEFAULT NULL AFTER `warehouse_id`',
  'SELECT ''skip customer_product_return_details.destination_warehouse_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_return_details' AND INDEX_NAME = 'cpr_detail_item_dest_unique') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_return_details') = 1,
  'ALTER TABLE `customer_product_return_details` ADD UNIQUE KEY `cpr_detail_item_dest_unique` (`return_id`,`product_variant_id`,`unit_id`,`warehouse_id`,`destination_warehouse_id`)',
  'SELECT ''skip idx customer_product_return_details.cpr_detail_item_dest_unique'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_return_details' AND INDEX_NAME = 'cpr_detail_dest_wh_idx') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_return_details') = 1,
  'ALTER TABLE `customer_product_return_details` ADD KEY `cpr_detail_dest_wh_idx` (`destination_warehouse_id`)',
  'SELECT ''skip idx customer_product_return_details.cpr_detail_dest_wh_idx'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- COLUMNS customer_product_returns
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_returns' AND COLUMN_NAME = 'qc_staff_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_returns') = 1,
  'ALTER TABLE `customer_product_returns` ADD COLUMN `qc_staff_id` int DEFAULT NULL AFTER `created_by`',
  'SELECT ''skip customer_product_returns.qc_staff_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_returns' AND INDEX_NAME = 'cpr_qc_staff_id_index') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_product_returns') = 1,
  'ALTER TABLE `customer_product_returns` ADD KEY `cpr_qc_staff_id_index` (`qc_staff_id`)',
  'SELECT ''skip idx customer_product_returns.cpr_qc_staff_id_index'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- COLUMNS customer_supply_returns
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_supply_returns' AND COLUMN_NAME = 'qc_staff_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_supply_returns') = 1,
  'ALTER TABLE `customer_supply_returns` ADD COLUMN `qc_staff_id` int DEFAULT NULL AFTER `created_by`',
  'SELECT ''skip customer_supply_returns.qc_staff_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_supply_returns' AND INDEX_NAME = 'csr_qc_staff_id_index') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customer_supply_returns') = 1,
  'ALTER TABLE `customer_supply_returns` ADD KEY `csr_qc_staff_id_index` (`qc_staff_id`)',
  'SELECT ''skip idx customer_supply_returns.csr_qc_staff_id_index'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- COLUMNS dashboard_change_logs
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'dashboard_change_logs' AND COLUMN_NAME = 'activity_type') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'dashboard_change_logs') = 1,
  'ALTER TABLE `dashboard_change_logs` ADD COLUMN `activity_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''change'' AFTER `module_key`',
  'SELECT ''skip dashboard_change_logs.activity_type'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'dashboard_change_logs' AND COLUMN_NAME = 'duration_seconds') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'dashboard_change_logs') = 1,
  'ALTER TABLE `dashboard_change_logs` ADD COLUMN `duration_seconds` int unsigned DEFAULT NULL AFTER `meta`',
  'SELECT ''skip dashboard_change_logs.duration_seconds'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- COLUMNS staff_warehouses
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'staff_warehouses' AND COLUMN_NAME = 'is_kepala_cabang') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'staff_warehouses') = 1,
  'ALTER TABLE `staff_warehouses` ADD COLUMN `is_kepala_cabang` tinyint(1) NOT NULL DEFAULT ''0'' AFTER `warehouse_id`',
  'SELECT ''skip staff_warehouses.is_kepala_cabang'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'staff_warehouses' AND INDEX_NAME = 'staff_warehouses_warehouse_kepala_index') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'staff_warehouses') = 1,
  'ALTER TABLE `staff_warehouses` ADD KEY `staff_warehouses_warehouse_kepala_index` (`warehouse_id`,`is_kepala_cabang`)',
  'SELECT ''skip idx staff_warehouses.staff_warehouses_warehouse_kepala_index'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- COLUMNS stock_opname_bahans
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans' AND COLUMN_NAME = 'is_draft') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans') = 1,
  'ALTER TABLE `stock_opname_bahans` ADD COLUMN `is_draft` tinyint(1) NOT NULL DEFAULT ''0'' AFTER `status`',
  'SELECT ''skip stock_opname_bahans.is_draft'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- COLUMNS stock_opnames
SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames' AND COLUMN_NAME = 'is_draft') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames') = 1,
  'ALTER TABLE `stock_opnames` ADD COLUMN `is_draft` tinyint(1) NOT NULL DEFAULT ''0'' AFTER `status`',
  'SELECT ''skip stock_opnames.is_draft'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

