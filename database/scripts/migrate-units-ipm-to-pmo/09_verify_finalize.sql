-- =============================================================================
-- 09_verify_finalize.sql
-- Verifikasi akhir + kembalikan setting session.
-- TIDAK COMMIT otomatis — kamu yang putuskan.
-- Tabel diubah: —
-- =============================================================================

-- Leftover stok di unit from (harus 0)
SELECT 'product_stocks leftover' AS cek, COUNT(*) AS n
FROM product_stocks ps
JOIN tmp_unit_remap m ON m.from_unit_id = ps.unit_id
UNION ALL
SELECT 'supplies_stocks leftover', COUNT(*)
FROM supplies_stocks ss
JOIN tmp_unit_remap m ON m.from_unit_id = ss.unit_id;

-- Status unit
SELECT unit_id, ref_unit_id, unit_name, unit_short_name, status
FROM units
WHERE unit_id IN (7, 9, 126, 127)
ORDER BY unit_id;
-- Harus: 7 & 9 status=0; 126 Dus & 127 Pcs status=1

SELECT scope, qty_before, qty_after, (qty_after - qty_before) AS delta
FROM tmp_stock_checksum;
-- delta harus 0

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;

-- Hanya kalau semua OK:
--   COMMIT;
-- Kalau ragu / checksum gagal:
--   ROLLBACK;
