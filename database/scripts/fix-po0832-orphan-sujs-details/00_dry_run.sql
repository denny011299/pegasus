-- =============================================================================
-- 00_dry_run.sql — PO0832 orphan detail SUJS (staging nembak live ~09:17)
-- Hanya CEK. Tidak mengubah data.
-- Target: 4 baris purchase_orders_details (pod_id 1713–1716), bukan header/Mulia.
-- =============================================================================

SELECT po_id, po_number, po_supplier, po_total, status, created_at, updated_at, created_by
FROM purchase_orders
WHERE po_id = 832 AND po_number = 'PO0832';

-- Harus tepat 4 baris SUJS aktif; Mulia (1717) jangan ikut
SELECT pod_id, po_id, supplies_variant_id, pod_sku, pod_nama, pod_qty, pod_harga, pod_subtotal, status, created_at
FROM purchase_orders_details
WHERE po_id = 832
  AND status = 1
  AND pod_id IN (1713, 1714, 1715, 1716)
  AND pod_sku IN ('BTLMREM1LTRSUJS', 'SUMPEL50ML', 'TTP50MLBR', 'TTPBTL50MLMRH')
ORDER BY pod_id;

-- Yang HARUS tetap (Mulia)
SELECT pod_id, po_id, pod_sku, pod_nama, pod_subtotal, status, created_at
FROM purchase_orders_details
WHERE po_id = 832 AND status = 1 AND pod_id = 1717;

-- Jejak ACC setengah (TIDAK dihapus script 01 — info saja)
SELECT pdo_id, po_id, pdo_number, status, created_at
FROM purchase_delivery_orders
WHERE po_id = 832 AND pdo_id = 763;

SELECT poi_id, po_id, poi_code, poi_total, status, created_at
FROM purchase_order_detail_invoices
WHERE po_id = 832 AND poi_id = 763;
