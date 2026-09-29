-- =============================================================================
-- 08_deactivate_legacy_units.sql
-- Nonaktifkan satuan IPM legacy; pastikan Dus/Pcs PMO aktif & nama benar.
-- Tabel diubah: units
-- =============================================================================

UPDATE units u
JOIN tmp_unit_remap m ON m.from_unit_id = u.unit_id
SET u.status = 0, u.updated_at = NOW();

UPDATE units SET unit_name = 'Dus', unit_short_name = 'Dus', status = 1, updated_at = NOW()
WHERE unit_id = 126;

UPDATE units SET unit_name = 'Pcs', unit_short_name = 'Pcs', status = 1, updated_at = NOW()
WHERE unit_id = 127;

-- Junk IPMTEST aktif (tanpa remap stok)
UPDATE units SET status = 0, updated_at = NOW()
WHERE status = 1
  AND unit_id IN (96, 98, 100, 102, 110);

-- Lanjut 09_verify_finalize.sql
