-- =============================================================================
-- 02_move_pembelian_eceran_to_main.sql
-- Pindahkan stok credits ACC PO yang salah ke Gudang Eceran (2) → Gudang Besar (1)
-- + rewrite log_stocks.warehouse_id.
-- EXCLUDE supplies_id 248, 249 (SAM OIL GEAR 90 / 140) — client sudah fix.
-- Prasyarat: 00_dry_run match_flag semua EXACT (kecuali SAM).
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
SET @eceran_wh := 2;

-- Temp aggregate credit qty (excl SAM)
DROP TEMPORARY TABLE IF EXISTS tmp_po_eceran_credits;
CREATE TEMPORARY TABLE tmp_po_eceran_credits (
  supplies_id INT NOT NULL,
  unit_id INT NOT NULL,
  credit_qty INT NOT NULL,
  PRIMARY KEY (supplies_id, unit_id)
);

INSERT INTO tmp_po_eceran_credits (supplies_id, unit_id, credit_qty)
SELECT log_item_id, unit_id, SUM(log_jumlah)
FROM log_stocks
WHERE log_category = 1
  AND warehouse_id = @eceran_wh
  AND log_type = 2
  AND log_item_id NOT IN (248, 249)
  AND (log_notes LIKE 'Pembelian%' OR log_kode REGEXP '^PO[0-9]+')
GROUP BY log_item_id, unit_id;

-- Guard: SHORT/MISSING harus 0 baris. Jika ada → ROLLBACK; jangan lanjut.
SELECT *
FROM (
  SELECT
    c.supplies_id,
    c.unit_id,
    c.credit_qty,
    ss2.ss_stock AS wh2_stock,
    CASE
      WHEN ss2.ss_id IS NULL THEN 'MISSING_WH2'
      WHEN ss2.ss_stock < c.credit_qty THEN 'SHORT'
      WHEN ss2.ss_stock = c.credit_qty THEN 'EXACT'
      ELSE 'EXTRA'
    END AS match_flag
  FROM tmp_po_eceran_credits c
  LEFT JOIN supplies_stocks ss2
    ON ss2.supplies_id = c.supplies_id
   AND ss2.unit_id = c.unit_id
   AND ss2.warehouse_id = @eceran_wh
   AND ss2.status = 1
) g
WHERE g.match_flag IN ('MISSING_WH2', 'SHORT');

-- Pastikan baris WH1 ada (create 0 bila belum)
INSERT INTO supplies_stocks (supplies_id, unit_id, warehouse_id, ss_stock, status, created_at, updated_at)
SELECT c.supplies_id, c.unit_id, @main_wh, 0, 1, NOW(), NOW()
FROM tmp_po_eceran_credits c
LEFT JOIN supplies_stocks ss1
  ON ss1.supplies_id = c.supplies_id
 AND ss1.unit_id = c.unit_id
 AND ss1.warehouse_id = @main_wh
 AND ss1.status = 1
WHERE ss1.ss_id IS NULL;

-- Debit Eceran
UPDATE supplies_stocks ss2
JOIN tmp_po_eceran_credits c
  ON c.supplies_id = ss2.supplies_id
 AND c.unit_id = ss2.unit_id
SET ss2.ss_stock = ss2.ss_stock - c.credit_qty,
    ss2.updated_at = NOW()
WHERE ss2.warehouse_id = @eceran_wh
  AND ss2.status = 1;

-- Credit Gudang Besar
UPDATE supplies_stocks ss1
JOIN tmp_po_eceran_credits c
  ON c.supplies_id = ss1.supplies_id
 AND c.unit_id = ss1.unit_id
SET ss1.ss_stock = ss1.ss_stock + c.credit_qty,
    ss1.updated_at = NOW()
WHERE ss1.warehouse_id = @main_wh
  AND ss1.status = 1;

-- Rewrite pembelian logs Eceran → main (excl SAM 248/249)
UPDATE log_stocks
SET warehouse_id = @main_wh,
    updated_at = NOW()
WHERE log_category = 1
  AND warehouse_id = @eceran_wh
  AND log_type = 2
  AND log_item_id NOT IN (248, 249)
  AND (log_notes LIKE 'Pembelian%' OR log_kode REGEXP '^PO[0-9]+');

-- Verify: no non-SAM pembelian logs left on eceran
SELECT COUNT(*) AS remaining_non_sam_pembelian_on_eceran
FROM log_stocks
WHERE log_category = 1
  AND warehouse_id = @eceran_wh
  AND log_type = 2
  AND log_item_id NOT IN (248, 249)
  AND (log_notes LIKE 'Pembelian%' OR log_kode REGEXP '^PO[0-9]+');
-- expect 0

-- SAM residual on eceran (LEFT INTENTIONALLY)
SELECT ss.supplies_id, s.supplies_name, ss.unit_id, ss.warehouse_id, ss.ss_stock
FROM supplies_stocks ss
JOIN supplies s ON s.supplies_id = ss.supplies_id
WHERE ss.supplies_id IN (248, 249)
  AND ss.status = 1
ORDER BY ss.supplies_id, ss.warehouse_id;

-- Spot-check moved rows (WH2 should be 0 for credit keys)
SELECT
  c.supplies_id,
  c.unit_id,
  c.credit_qty,
  ss2.ss_stock AS wh2_after,
  ss1.ss_stock AS wh1_after
FROM tmp_po_eceran_credits c
LEFT JOIN supplies_stocks ss2
  ON ss2.supplies_id = c.supplies_id AND ss2.unit_id = c.unit_id
 AND ss2.warehouse_id = @eceran_wh AND ss2.status = 1
LEFT JOIN supplies_stocks ss1
  ON ss1.supplies_id = c.supplies_id AND ss1.unit_id = c.unit_id
 AND ss1.warehouse_id = @main_wh AND ss1.status = 1
ORDER BY c.credit_qty DESC;

DROP TEMPORARY TABLE IF EXISTS tmp_po_eceran_credits;

-- REVIEW lalu:
-- COMMIT;
-- atau ROLLBACK;
