-- =============================================================================
-- 01_dry_run.sql
-- Buat peta remap + lihat dampak. AMAN: tidak ubah data bisnis.
-- Mapping: 7 DOS → 126 Dus | 9 Piece → 127 Pcs
-- Tabel diubah: — (hanya TEMP tmp_unit_remap)
-- Tabel dibaca: units, product_stocks, supplies_stocks, log_stocks,
--   sales_order_details, stock_transfer_details, product_variants, product_relations
-- =============================================================================

DROP TEMPORARY TABLE IF EXISTS tmp_unit_remap;
CREATE TEMPORARY TABLE tmp_unit_remap (
  from_unit_id INT NOT NULL PRIMARY KEY,
  to_unit_id   INT NOT NULL,
  note         VARCHAR(255) NULL
) ENGINE = Memory;

INSERT INTO tmp_unit_remap (from_unit_id, to_unit_id, note) VALUES
  (7, 126, 'DOS → Dus (PMO)'),
  (9, 127, 'Piece → Pcs (PMO)');

-- Pastikan target PMO unit ada & aktif (harus: 126 Dus=1, 127 Pcs=1)
SELECT u.unit_id, u.ref_unit_id, u.unit_name, u.unit_short_name, u.status
FROM units u
WHERE u.unit_id IN (SELECT to_unit_id FROM tmp_unit_remap)
   OR u.unit_id IN (SELECT from_unit_id FROM tmp_unit_remap)
ORDER BY u.unit_id;

-- Impact counts
SELECT m.from_unit_id, m.to_unit_id, m.note,
  (SELECT COUNT(*) FROM product_stocks ps WHERE ps.unit_id = m.from_unit_id) AS rows_product_stocks,
  (SELECT IFNULL(SUM(ps.ps_stock),0) FROM product_stocks ps WHERE ps.unit_id = m.from_unit_id) AS qty_product_stocks,
  (SELECT COUNT(*) FROM supplies_stocks ss WHERE ss.unit_id = m.from_unit_id) AS rows_supplies_stocks,
  (SELECT IFNULL(SUM(ss.ss_stock),0) FROM supplies_stocks ss WHERE ss.unit_id = m.from_unit_id) AS qty_supplies_stocks,
  (SELECT COUNT(*) FROM log_stocks ls WHERE ls.unit_id = m.from_unit_id) AS rows_log_stocks,
  (SELECT COUNT(*) FROM sales_order_details sod WHERE sod.unit_id = m.from_unit_id) AS rows_so_details,
  (SELECT COUNT(*) FROM stock_transfer_details std
     WHERE std.unit_id = m.from_unit_id OR IFNULL(std.received_unit_id,0) = m.from_unit_id) AS rows_st_details,
  (SELECT COUNT(*) FROM product_variants pv
     WHERE pv.unit_id = m.from_unit_id
        OR IFNULL(pv.retail_unit,0) = m.from_unit_id
        OR IFNULL(pv.safety_unit_id,0) = m.from_unit_id) AS rows_variants,
  (SELECT COUNT(*) FROM product_relations pr
     WHERE pr.pr_unit_id_1 = m.from_unit_id OR pr.pr_unit_id_2 = m.from_unit_id) AS rows_relations
FROM tmp_unit_remap m
ORDER BY m.from_unit_id;

-- Konflik product_stocks (akan di-SUM di step 03)
SELECT m.from_unit_id, m.to_unit_id,
       ps_from.warehouse_id, ps_from.product_variant_id,
       ps_from.ps_stock AS stock_from,
       ps_to.ps_stock   AS stock_to,
       (IFNULL(ps_from.ps_stock,0) + IFNULL(ps_to.ps_stock,0)) AS stock_after_merge
FROM tmp_unit_remap m
JOIN product_stocks ps_from ON ps_from.unit_id = m.from_unit_id
JOIN product_stocks ps_to
  ON ps_to.unit_id = m.to_unit_id
 AND ps_to.warehouse_id <=> ps_from.warehouse_id
 AND ps_to.product_variant_id = ps_from.product_variant_id;

-- Konflik supplies_stocks (akan di-SUM di step 04)
SELECT m.from_unit_id, m.to_unit_id,
       ss_from.warehouse_id, ss_from.supplies_id,
       ss_from.ss_stock AS stock_from,
       ss_to.ss_stock   AS stock_to,
       (IFNULL(ss_from.ss_stock,0) + IFNULL(ss_to.ss_stock,0)) AS stock_after_merge
FROM tmp_unit_remap m
JOIN supplies_stocks ss_from ON ss_from.unit_id = m.from_unit_id
JOIN supplies_stocks ss_to
  ON ss_to.unit_id = m.to_unit_id
 AND ss_to.warehouse_id <=> ss_from.warehouse_id
 AND ss_to.supplies_id = ss_from.supplies_id;

-- >>> REVIEW hasil di atas. Backup DB. Lanjut 02_begin_checksum.sql <<<
