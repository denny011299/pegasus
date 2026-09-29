-- =============================================================================
-- 01_alter_backfill_po_warehouse.sql
-- Setara migrate 2026_09_29_100000_add_warehouse_id_to_purchase_orders_table
-- Dump: kolom BELUM ada → ALTER + backfill semua PO ke gudang utama.
-- =============================================================================

START TRANSACTION;

SET @main_wh := (
  SELECT w.id
  FROM warehouses w
  JOIN warehouse_types wt ON wt.id = w.warehouse_type_id
  WHERE w.status = 1 AND wt.is_main_warehouse = 1
  ORDER BY w.id
  LIMIT 1
);

SELECT @main_wh AS main_warehouse_id;

SET @has_po_wh := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'purchase_orders'
    AND COLUMN_NAME = 'warehouse_id'
);

-- ADD COLUMN jika belum ada
SET @sql := IF(
  @has_po_wh = 0,
  'ALTER TABLE `purchase_orders` ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `po_supplier`, ADD INDEX `purchase_orders_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''purchase_orders.warehouse_id already exists'' AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Backfill null/0 → main
UPDATE purchase_orders
SET warehouse_id = @main_wh
WHERE warehouse_id IS NULL OR warehouse_id = 0;

SELECT
  COUNT(*) AS po_total,
  SUM(CASE WHEN warehouse_id = @main_wh THEN 1 ELSE 0 END) AS po_on_main,
  SUM(CASE WHEN warehouse_id IS NULL OR warehouse_id = 0 THEN 1 ELSE 0 END) AS po_still_null
FROM purchase_orders;

-- Catat migrate Laravel jika tabel migrations dipakai di staging
INSERT INTO migrations (`migration`, `batch`)
SELECT '2026_09_29_100000_add_warehouse_id_to_purchase_orders_table',
       COALESCE((SELECT MAX(batch) FROM migrations m), 0) + 1
WHERE NOT EXISTS (
  SELECT 1 FROM migrations
  WHERE migration = '2026_09_29_100000_add_warehouse_id_to_purchase_orders_table'
);

-- REVIEW lalu:
-- COMMIT;
-- atau ROLLBACK;
