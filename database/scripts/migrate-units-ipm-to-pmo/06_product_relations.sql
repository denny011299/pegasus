-- =============================================================================
-- 06_product_relations.sql
-- Relasi konversi satuan ikut unit PMO.
-- Tabel diubah: product_relations (pr_unit_id_1, pr_unit_id_2) + hapus duplikat
-- =============================================================================

UPDATE product_relations pr
JOIN tmp_unit_remap m ON m.from_unit_id = pr.pr_unit_id_1
SET pr.pr_unit_id_1 = m.to_unit_id;

UPDATE product_relations pr
JOIN tmp_unit_remap m ON m.from_unit_id = pr.pr_unit_id_2
SET pr.pr_unit_id_2 = m.to_unit_id;

DELETE pr1 FROM product_relations pr1
INNER JOIN product_relations pr2
  ON pr1.product_variant_id = pr2.product_variant_id
 AND pr1.pr_unit_id_1 = pr2.pr_unit_id_1
 AND pr1.pr_unit_id_2 = pr2.pr_unit_id_2
 AND pr1.pr_id > pr2.pr_id;

-- Lanjut 07_remap_operational_fks.sql
