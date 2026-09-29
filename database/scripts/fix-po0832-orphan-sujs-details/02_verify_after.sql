-- =============================================================================
-- 02_verify_after.sql — setelah COMMIT 01, pastikan PO0832 aman
-- Hanya CEK. Tidak mengubah data.
-- =============================================================================

-- A) Header tetap ada, status menunggu, total Mulia
SELECT po_id, po_number, po_supplier, po_total, status, created_at, created_by
FROM purchase_orders
WHERE po_id = 832 AND po_number = 'PO0832';
-- Expect: po_supplier=41 (Mulia), po_total=12915000, status=1

-- B) SUJS harus status=0 (soft-deleted)
SELECT pod_id, pod_sku, status, updated_at
FROM purchase_orders_details
WHERE po_id = 832
  AND pod_id IN (1713, 1714, 1715, 1716)
ORDER BY pod_id;
-- Expect: 4 baris, semua status=0

-- C) Aktif di PO0832 hanya Mulia
SELECT pod_id, pod_sku, pod_nama, pod_subtotal, status, created_at
FROM purchase_orders_details
WHERE po_id = 832 AND status = 1
ORDER BY pod_id;
-- Expect: 1 baris — DOSHKAA400MLMGM / pod_id 1717

-- D) Ringkas OK/FAIL
SELECT
  CASE WHEN EXISTS (
    SELECT 1 FROM purchase_orders
    WHERE po_id = 832 AND po_number = 'PO0832' AND po_total = 12915000 AND status = 1
  ) THEN 'OK' ELSE 'FAIL' END AS header_ok,
  CASE WHEN (
    SELECT COUNT(*) FROM purchase_orders_details
    WHERE po_id = 832 AND pod_id IN (1713,1714,1715,1716) AND status = 0
  ) = 4 THEN 'OK' ELSE 'FAIL' END AS sujs_soft_deleted_ok,
  CASE WHEN (
    SELECT COUNT(*) FROM purchase_orders_details
    WHERE po_id = 832 AND status = 1
  ) = 1
  AND EXISTS (
    SELECT 1 FROM purchase_orders_details
    WHERE po_id = 832 AND status = 1 AND pod_id = 1717
  ) THEN 'OK' ELSE 'FAIL' END AS active_only_mulia_ok;
