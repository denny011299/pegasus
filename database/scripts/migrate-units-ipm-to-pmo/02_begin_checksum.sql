-- =============================================================================
-- 02_begin_checksum.sql
-- Mulai transaksi + snapshot total stok SEBELUM mutate.
-- Syarat: sudah jalankan 00 + 01 di session yang sama.
-- Tabel diubah: — (TEMP tmp_stock_checksum)
-- Tabel dibaca: product_stocks, supplies_stocks
-- =============================================================================

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_stock_checksum;
CREATE TEMPORARY TABLE tmp_stock_checksum (
  scope VARCHAR(32) NOT NULL PRIMARY KEY,
  qty_before BIGINT NOT NULL,
  qty_after  BIGINT NULL
) ENGINE = Memory;

INSERT INTO tmp_stock_checksum (scope, qty_before)
SELECT 'product_stocks', IFNULL(SUM(ps.ps_stock), 0)
FROM product_stocks ps
WHERE ps.unit_id IN (
  SELECT from_unit_id FROM tmp_unit_remap
  UNION
  SELECT to_unit_id FROM tmp_unit_remap
);

INSERT INTO tmp_stock_checksum (scope, qty_before)
SELECT 'supplies_stocks', IFNULL(SUM(ss.ss_stock), 0)
FROM supplies_stocks ss
WHERE ss.unit_id IN (
  SELECT from_unit_id FROM tmp_unit_remap
  UNION
  SELECT to_unit_id FROM tmp_unit_remap
);

SELECT * FROM tmp_stock_checksum;
-- Catat angka qty_before. Lanjut 03_product_stocks.sql
