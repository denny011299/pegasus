-- Migration: 2026_08_20_180000_add_warehouse_id_to_stock_opnames_tables
-- Idempotent. Stock Opname Produk + Bahan per gudang.
-- Alternatif: php artisan migrate --path=database/migrations/2026_08_20_180000_add_warehouse_id_to_stock_opnames_tables.php --force

SET @db := DATABASE();

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

-- Backfill dokumen lama ke gudang utama (is_main_warehouse=1)
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
WHERE warehouse_id IS NULL
  AND @main_wh IS NOT NULL
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opnames' AND COLUMN_NAME = 'warehouse_id'
  );

UPDATE stock_opname_bahans
SET warehouse_id = @main_wh
WHERE warehouse_id IS NULL
  AND @main_wh IS NOT NULL
  AND EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_bahans' AND COLUMN_NAME = 'warehouse_id'
  );

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_20_180000_add_warehouse_id_to_stock_opnames_tables',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_20_180000_add_warehouse_id_to_stock_opnames_tables');

SELECT '2026_08_20_180000_add_warehouse_id_to_stock_opnames_tables OK' AS result,
       @main_wh AS backfilled_to_warehouse_id;
