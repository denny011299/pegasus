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