-- =============================================================================
-- 01_soft_delete_sujs_details.sql
-- Soft-delete HANYA 4 orphan detail SUJS di PO0832 (pod_id 1713–1716).
-- Tidak sentuh: header PO, detail Mulia (1717), PDO, INV, stok, log_stocks.
-- Cara: jalankan 00_dry_run dulu → pastikan tepat 4 baris → script ini → REVIEW → COMMIT.
-- =============================================================================

START TRANSACTION;

-- Guard: wajib exact 4 baris. Kalau bukan 4 → STOP (ROLLBACK), jangan lanjut.
SELECT COUNT(*) AS target_rows_must_be_4
FROM purchase_orders_details
WHERE po_id = 832
  AND status = 1
  AND pod_id IN (1713, 1714, 1715, 1716)
  AND pod_sku IN ('BTLMREM1LTRSUJS', 'SUMPEL50ML', 'TTP50MLBR', 'TTPBTL50MLMRH');

UPDATE purchase_orders_details
SET status = 0,
    updated_at = NOW()
WHERE po_id = 832
  AND status = 1
  AND pod_id IN (1713, 1714, 1715, 1716)
  AND pod_sku IN ('BTLMREM1LTRSUJS', 'SUMPEL50ML', 'TTP50MLBR', 'TTPBTL50MLMRH');

-- Harus: affected = 4. Kalau 0 / >4 → ROLLBACK.
SELECT ROW_COUNT() AS rows_updated;

-- Verifikasi: SUJS hilang dari aktif; Mulia tetap
SELECT pod_id, pod_sku, status, pod_subtotal, created_at
FROM purchase_orders_details
WHERE po_id = 832
  AND pod_id IN (1713, 1714, 1715, 1716, 1717)
ORDER BY pod_id;

SELECT po_id, po_number, po_total, status
FROM purchase_orders
WHERE po_id = 832 AND po_number = 'PO0832';
-- po_total harus tetap 12915000 (sudah = Mulia saja)

-- REVIEW lalu:
-- COMMIT;
-- atau ROLLBACK;
