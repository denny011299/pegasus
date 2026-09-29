-- =============================================================================
-- 00_dry_run.sql — ACC PO warehouse misplacement (Eceran → Gudang Besar)
-- Dump baseline: u906028329_pegasus (3).sql
-- Main WH=1, Eceran WH=2
-- EXCLUDE supplies 248,249 (SAM OIL GEAR 90 / 140 20x1L)
-- =============================================================================

SET @main_wh := (
  SELECT w.id
  FROM warehouses w
  JOIN warehouse_types wt ON wt.id = w.warehouse_type_id
  WHERE w.status = 1 AND wt.is_main_warehouse = 1
  ORDER BY w.id
  LIMIT 1
);
SET @eceran_wh := 2;
SET @has_po_wh := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'purchase_orders'
    AND COLUMN_NAME = 'warehouse_id'
);

SELECT @main_wh AS main_warehouse_id, @eceran_wh AS eceran_warehouse_id, @has_po_wh AS po_has_warehouse_id_col;

-- Warehouses
SELECT id, warehouse_name, warehouse_type_id, status FROM warehouses ORDER BY id;

-- PO backfill scope
SELECT
  COUNT(*) AS po_total,
  SUM(CASE WHEN @has_po_wh = 0 THEN 1
           WHEN warehouse_id IS NULL OR warehouse_id = 0 THEN 1
           ELSE 0 END) AS po_need_backfill_to_main
FROM purchase_orders;

-- Pembelian logs on Eceran (all, then excl SAM)
SELECT
  COUNT(*) AS pembelian_logs_eceran,
  COUNT(DISTINCT log_kode) AS distinct_po,
  SUM(log_jumlah) AS qty_sum
FROM log_stocks
WHERE log_category = 1
  AND warehouse_id = @eceran_wh
  AND (log_notes LIKE 'Pembelian%' OR log_kode REGEXP '^PO[0-9]+');

SELECT
  COUNT(*) AS pembelian_logs_eceran_excl_sam,
  COUNT(DISTINCT log_kode) AS distinct_po_excl_sam,
  SUM(log_jumlah) AS qty_sum_excl_sam
FROM log_stocks
WHERE log_category = 1
  AND warehouse_id = @eceran_wh
  AND log_type = 2
  AND log_item_id NOT IN (248, 249)
  AND (log_notes LIKE 'Pembelian%' OR log_kode REGEXP '^PO[0-9]+');

-- Per PO (wrong WH)
SELECT log_kode AS po_number, COUNT(*) AS line_cnt, SUM(log_jumlah) AS qty
FROM log_stocks
WHERE log_category = 1
  AND warehouse_id = @eceran_wh
  AND (log_notes LIKE 'Pembelian%' OR log_kode REGEXP '^PO[0-9]+')
GROUP BY log_kode
ORDER BY log_kode;

-- Aggregate credits excl SAM vs current WH2 stock (expect EXACT)
SELECT
  x.supplies_id,
  s.supplies_name,
  x.unit_id,
  x.credit_qty,
  COALESCE(ss2.ss_stock, 0) AS wh2_stock,
  COALESCE(ss1.ss_stock, 0) AS wh1_stock,
  CASE
    WHEN ss2.ss_id IS NULL THEN 'MISSING_WH2'
    WHEN ss2.ss_stock = x.credit_qty THEN 'EXACT'
    WHEN ss2.ss_stock < x.credit_qty THEN 'SHORT'
    ELSE 'EXTRA'
  END AS match_flag
FROM (
  SELECT log_item_id AS supplies_id, unit_id, SUM(log_jumlah) AS credit_qty
  FROM log_stocks
  WHERE log_category = 1
    AND warehouse_id = @eceran_wh
    AND log_type = 2
    AND log_item_id NOT IN (248, 249)
    AND (log_notes LIKE 'Pembelian%' OR log_kode REGEXP '^PO[0-9]+')
  GROUP BY log_item_id, unit_id
) x
LEFT JOIN supplies s ON s.supplies_id = x.supplies_id
LEFT JOIN supplies_stocks ss2
  ON ss2.supplies_id = x.supplies_id AND ss2.unit_id = x.unit_id
 AND ss2.warehouse_id = @eceran_wh AND ss2.status = 1
LEFT JOIN supplies_stocks ss1
  ON ss1.supplies_id = x.supplies_id AND ss1.unit_id = x.unit_id
 AND ss1.warehouse_id = @main_wh AND ss1.status = 1
ORDER BY x.credit_qty DESC;

-- SAM OIL — DO NOT MOVE (client already credited WH1)
SELECT
  s.supplies_id,
  s.supplies_name,
  ss.ss_id,
  ss.unit_id,
  ss.warehouse_id,
  ss.ss_stock,
  ss.updated_at
FROM supplies s
JOIN supplies_stocks ss ON ss.supplies_id = s.supplies_id AND ss.status = 1
WHERE s.supplies_id IN (248, 249)
ORDER BY s.supplies_id, ss.warehouse_id, ss.unit_id;

SELECT log_id, log_date, log_kode, log_item_id, log_jumlah, unit_id, warehouse_id, log_notes
FROM log_stocks
WHERE log_type = 2
  AND log_item_id IN (248, 249)
  AND (log_notes LIKE 'Pembelian%' OR log_kode REGEXP '^PO[0-9]+')
ORDER BY log_date, log_id;
