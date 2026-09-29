-- =============================================================================
-- 05_checksum_verify.sql
-- Pastikan total stok tidak hilang (qty_after == qty_before).
-- Kalau GAGAL: jalankan ROLLBACK; — JANGAN lanjut step 06–08.
-- Tabel diubah: TEMP tmp_stock_checksum (qty_after)
-- =============================================================================

UPDATE tmp_stock_checksum c
SET c.qty_after = (
  SELECT IFNULL(SUM(ps.ps_stock), 0)
  FROM product_stocks ps
  WHERE ps.unit_id IN (SELECT to_unit_id FROM tmp_unit_remap)
)
WHERE c.scope = 'product_stocks';

UPDATE tmp_stock_checksum c
SET c.qty_after = (
  SELECT IFNULL(SUM(ss.ss_stock), 0)
  FROM supplies_stocks ss
  WHERE ss.unit_id IN (SELECT to_unit_id FROM tmp_unit_remap)
)
WHERE c.scope = 'supplies_stocks';

SELECT scope, qty_before, qty_after,
       (qty_after - qty_before) AS delta
FROM tmp_stock_checksum;
-- delta HARUS 0

SET @bad := (
  SELECT COUNT(*) FROM tmp_stock_checksum
  WHERE qty_after IS NULL OR qty_after <> qty_before
);

SELECT IF(@bad = 0,
  'STOCK CHECKSUM OK — aman lanjut ke 06',
  'STOCK CHECKSUM GAGAL — STOP, jalankan ROLLBACK'
) AS stock_guard;

-- Kalau gagal: ROLLBACK;
-- Kalau OK: lanjut 06_product_relations.sql
