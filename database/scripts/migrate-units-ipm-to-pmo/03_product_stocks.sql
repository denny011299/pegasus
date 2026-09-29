-- =============================================================================
-- 03_product_stocks.sql
-- Alihkan stok produk ke satuan PMO (merge bentrok + remap).
-- Tabel diubah: product_stocks
--   kolom: unit_id, ps_stock, ps_safety_stock, ps_alert_stock, status, updated_at
-- =============================================================================

-- 1a. Merge qty ke baris PMO (to) kalau gudang+varian sama
UPDATE product_stocks ps_to
JOIN tmp_unit_remap m ON m.to_unit_id = ps_to.unit_id
JOIN product_stocks ps_from
  ON ps_from.unit_id = m.from_unit_id
 AND ps_from.warehouse_id <=> ps_to.warehouse_id
 AND ps_from.product_variant_id = ps_to.product_variant_id
SET
  ps_to.ps_stock        = IFNULL(ps_to.ps_stock, 0) + IFNULL(ps_from.ps_stock, 0),
  ps_to.ps_safety_stock = GREATEST(IFNULL(ps_to.ps_safety_stock, 0), IFNULL(ps_from.ps_safety_stock, 0)),
  ps_to.ps_alert_stock  = GREATEST(IFNULL(ps_to.ps_alert_stock, 0), IFNULL(ps_from.ps_alert_stock, 0)),
  ps_to.status          = IF(ps_to.status = 1 OR ps_from.status = 1, 1, ps_to.status),
  ps_to.updated_at      = NOW();

-- 1b. Hapus baris from yang sudah ter-merge
DELETE ps_from FROM product_stocks ps_from
JOIN tmp_unit_remap m ON m.from_unit_id = ps_from.unit_id
JOIN product_stocks ps_to
  ON ps_to.unit_id = m.to_unit_id
 AND ps_to.warehouse_id <=> ps_from.warehouse_id
 AND ps_to.product_variant_id = ps_from.product_variant_id;

-- 1c. Remap sisa from → to (belum ada baris to)
UPDATE product_stocks ps
JOIN tmp_unit_remap m ON m.from_unit_id = ps.unit_id
SET ps.unit_id = m.to_unit_id,
    ps.updated_at = NOW();

-- Lanjut 04_supplies_stocks.sql
