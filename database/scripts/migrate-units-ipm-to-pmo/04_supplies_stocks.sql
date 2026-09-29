-- =============================================================================
-- 04_supplies_stocks.sql
-- Alihkan stok bahan mentah ke satuan PMO (merge + remap).
-- Tabel diubah: supplies_stocks
--   kolom: unit_id, ss_stock, status, updated_at
-- =============================================================================

UPDATE supplies_stocks ss_to
JOIN tmp_unit_remap m ON m.to_unit_id = ss_to.unit_id
JOIN supplies_stocks ss_from
  ON ss_from.unit_id = m.from_unit_id
 AND ss_from.warehouse_id <=> ss_to.warehouse_id
 AND ss_from.supplies_id = ss_to.supplies_id
SET
  ss_to.ss_stock   = IFNULL(ss_to.ss_stock, 0) + IFNULL(ss_from.ss_stock, 0),
  ss_to.status     = IF(ss_to.status = 1 OR ss_from.status = 1, 1, ss_to.status),
  ss_to.updated_at = NOW();

DELETE ss_from FROM supplies_stocks ss_from
JOIN tmp_unit_remap m ON m.from_unit_id = ss_from.unit_id
JOIN supplies_stocks ss_to
  ON ss_to.unit_id = m.to_unit_id
 AND ss_to.warehouse_id <=> ss_from.warehouse_id
 AND ss_to.supplies_id = ss_from.supplies_id;

UPDATE supplies_stocks ss
JOIN tmp_unit_remap m ON m.from_unit_id = ss.unit_id
SET ss.unit_id = m.to_unit_id,
    ss.updated_at = NOW();

-- Lanjut 05_checksum_verify.sql
