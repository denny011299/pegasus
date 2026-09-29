-- =============================================================================
-- 03_zero_sam_eceran_residual.sql (opsional)
-- SAM OIL GEAR 90/140 (248/249): client sudah isi Gudang Besar.
-- Nolkan residual Eceran saja — JANGAN tambah qty ke WH1.
-- =============================================================================

START TRANSACTION;

UPDATE supplies_stocks
SET ss_stock = 0,
    updated_at = NOW()
WHERE supplies_id IN (248, 249)
  AND warehouse_id = 2
  AND status = 1
  AND ss_stock <> 0;

-- Expect: WH2 = 0; WH1 tetap 10 (90) / 15 (140) pada unit DOS (unit_id=7)
SELECT supplies_id, unit_id, warehouse_id, ss_stock
FROM supplies_stocks
WHERE supplies_id IN (248, 249)
  AND status = 1
ORDER BY supplies_id, warehouse_id, unit_id;

-- COMMIT;
-- atau ROLLBACK;
