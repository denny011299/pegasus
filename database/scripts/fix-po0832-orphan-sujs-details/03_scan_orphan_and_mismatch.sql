-- =============================================================================
-- 03_scan_orphan_and_mismatch.sql
-- Audit pembelian mirip kasus PO0832 (staging nembak live / orphan / campur supplier).
-- Hanya CEK. Tidak mengubah data.
-- =============================================================================

-- ---------------------------------------------------------------------------
-- 1) Detail AKTIF tanpa header PO (orphan murni — pola dump2 sebelum header lahir)
-- Expect: 0 baris. Kalau ada → bahaya, po_id "nyasar".
-- ---------------------------------------------------------------------------
SELECT d.pod_id, d.po_id, d.pod_sku, d.pod_nama, d.pod_subtotal, d.status, d.created_at
FROM purchase_orders_details d
LEFT JOIN purchase_orders p ON p.po_id = d.po_id
WHERE d.status = 1
  AND p.po_id IS NULL
ORDER BY d.po_id, d.pod_id;

-- ---------------------------------------------------------------------------
-- 2) Delivery / invoice menunjuk po_id yang header-nya tidak ada
-- Expect: 0 (kecuali jejak lama yang sudah diketahui, mis. sebelum soft-fix).
-- ---------------------------------------------------------------------------
SELECT 'delivery' AS kind, pdo.pdo_id AS id, pdo.po_id, pdo.pdo_number AS code, pdo.status, pdo.created_at
FROM purchase_delivery_orders pdo
LEFT JOIN purchase_orders p ON p.po_id = pdo.po_id
WHERE p.po_id IS NULL AND pdo.status >= 0

UNION ALL

SELECT 'invoice' AS kind, poi.poi_id AS id, poi.po_id, poi.poi_code AS code, poi.status, poi.created_at
FROM purchase_order_detail_invoices poi
LEFT JOIN purchase_orders p ON p.po_id = poi.po_id
WHERE p.po_id IS NULL AND poi.status >= 0

ORDER BY kind, id;

-- ---------------------------------------------------------------------------
-- 3) Detail aktif yang supplier VARIANT ≠ supplier HEADER PO
--    (= barang dari supplier lain nempel di PO — pola SUJS di PO Mulia)
-- Expect setelah fix PO0832: 0, atau hanya kasus bisnis yang sengaja (jarang).
-- ---------------------------------------------------------------------------
SELECT
  p.po_id,
  p.po_number,
  p.po_supplier AS header_supplier_id,
  sh.supplier_name AS header_supplier,
  d.pod_id,
  d.pod_sku,
  d.pod_nama,
  d.pod_subtotal,
  sv.supplier_id AS variant_supplier_id,
  sv_sup.supplier_name AS variant_supplier,
  d.created_at AS detail_created_at,
  p.created_at AS header_created_at,
  CASE
    WHEN d.created_at < p.created_at THEN 'DETAIL_OLDER_THAN_HEADER'
    ELSE 'DETAIL_SAME_OR_NEWER'
  END AS time_flag
FROM purchase_orders_details d
JOIN purchase_orders p ON p.po_id = d.po_id
JOIN supplies_variants sv ON sv.supplies_variant_id = d.supplies_variant_id
LEFT JOIN suppliers sh ON sh.supplier_id = p.po_supplier
LEFT JOIN suppliers sv_sup ON sv_sup.supplier_id = sv.supplier_id
WHERE d.status = 1
  AND p.status != 0
  AND sv.supplier_id IS NOT NULL
  AND p.po_supplier IS NOT NULL
  AND sv.supplier_id <> p.po_supplier
ORDER BY p.po_id, d.pod_id;

-- ---------------------------------------------------------------------------
-- 4) Ringkas: detail aktif lebih tua dari header (curiga orphan nempel id sama)
-- ---------------------------------------------------------------------------
SELECT
  p.po_id,
  p.po_number,
  COUNT(*) AS older_detail_count,
  MIN(d.created_at) AS oldest_detail,
  p.created_at AS header_created_at
FROM purchase_orders_details d
JOIN purchase_orders p ON p.po_id = d.po_id
WHERE d.status = 1
  AND p.status != 0
  AND d.created_at < p.created_at
GROUP BY p.po_id, p.po_number, p.created_at
ORDER BY older_detail_count DESC, p.po_id;

-- ---------------------------------------------------------------------------
-- 5) PO0832 sisa jejak ACC setengah (info — bukan orphan detail lagi)
-- ---------------------------------------------------------------------------
SELECT 'pdo' AS kind, pdo_id AS id, po_id, pdo_number AS code, status, created_at
FROM purchase_delivery_orders
WHERE po_id = 832 AND pdo_id = 763
UNION ALL
SELECT 'poi', poi_id, po_id, poi_code, status, created_at
FROM purchase_order_detail_invoices
WHERE po_id = 832 AND poi_id = 763;
