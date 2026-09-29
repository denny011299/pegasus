-- =============================================================================
-- ALL_IN_ONE_phpmyadmin.sql
-- Migrasi satuan IPM → PMO (DEV staging): 7 DOS→126 Dus | 9 Piece→127 Pcs
--
-- Pakai file ini di phpMyAdmin (satu kali Go) supaya TEMP table & transaksi
-- tidak hilang antar-step. File 00–09 tetap ada untuk debug per-step.
--
-- WAJIB sebelum run:
--   1. Backup DB DEV
--   2. Pastikan database aktif = u906028329_dev (bukan production)
--
-- Setelah Go: cek hasil SELECT di bawah.
--   - leftover = 0, unit 7/9 status=0, 126/127 status=1, delta checksum = 0
--   → jalankan:  COMMIT;
--   - ada yang salah / checksum GAGAL (script berhenti di tengah)
--   → jalankan:  ROLLBACK;
--
-- COMMIT TIDAK ikut di file ini (sengaja).
-- =============================================================================

-- Bersihkan transaksi sisa (aman)
ROLLBACK;

-- -----------------------------------------------------------------------------
-- 00 setup session
-- -----------------------------------------------------------------------------
SET NAMES utf8mb4;
SET @OLD_UNIQUE_CHECKS := @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS := @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 01 remap map (+ preview ringkas)
-- -----------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_unit_remap;
CREATE TEMPORARY TABLE tmp_unit_remap (
  from_unit_id INT NOT NULL PRIMARY KEY,
  to_unit_id   INT NOT NULL,
  note         VARCHAR(255) NULL
) ENGINE = Memory;

INSERT INTO tmp_unit_remap (from_unit_id, to_unit_id, note) VALUES
  (7, 126, 'DOS → Dus (PMO)'),
  (9, 127, 'Piece → Pcs (PMO)');

SELECT u.unit_id, u.ref_unit_id, u.unit_name, u.unit_short_name, u.status
FROM units u
WHERE u.unit_id IN (SELECT to_unit_id FROM tmp_unit_remap)
   OR u.unit_id IN (SELECT from_unit_id FROM tmp_unit_remap)
ORDER BY u.unit_id;

-- -----------------------------------------------------------------------------
-- 02 begin transaction + checksum before
-- -----------------------------------------------------------------------------
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

-- -----------------------------------------------------------------------------
-- 03 product_stocks merge + remap
-- -----------------------------------------------------------------------------
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

DELETE ps_from FROM product_stocks ps_from
JOIN tmp_unit_remap m ON m.from_unit_id = ps_from.unit_id
JOIN product_stocks ps_to
  ON ps_to.unit_id = m.to_unit_id
 AND ps_to.warehouse_id <=> ps_from.warehouse_id
 AND ps_to.product_variant_id = ps_from.product_variant_id;

UPDATE product_stocks ps
JOIN tmp_unit_remap m ON m.from_unit_id = ps.unit_id
SET ps.unit_id = m.to_unit_id,
    ps.updated_at = NOW();

-- -----------------------------------------------------------------------------
-- 04 supplies_stocks merge + remap
-- -----------------------------------------------------------------------------
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

-- -----------------------------------------------------------------------------
-- 05 checksum verify — HARD STOP kalau gagal (phpMyAdmin berhenti, jangan lanjut)
-- -----------------------------------------------------------------------------
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

SELECT scope, qty_before, qty_after, (qty_after - qty_before) AS delta
FROM tmp_stock_checksum;

SET @bad := (
  SELECT COUNT(*) FROM tmp_stock_checksum
  WHERE qty_after IS NULL OR qty_after <> qty_before
);

SELECT IF(@bad = 0,
  'STOCK CHECKSUM OK — lanjut remap FK',
  'STOCK CHECKSUM GAGAL — script STOP, jalankan ROLLBACK'
) AS stock_guard;

-- Kalau @bad > 0 → query ke tabel fiktif → error → sisa script tidak jalan
SET @guard_sql := IF(
  @bad = 0,
  'SELECT ''guard_pass'' AS guard',
  'SELECT * FROM `__STOP_STOCK_CHECKSUM_GAGAL__`'
);
PREPARE guard_stmt FROM @guard_sql;
EXECUTE guard_stmt;
DEALLOCATE PREPARE guard_stmt;

-- -----------------------------------------------------------------------------
-- 06 product_relations
-- -----------------------------------------------------------------------------
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

-- -----------------------------------------------------------------------------
-- 07 remap operational FKs
-- -----------------------------------------------------------------------------
UPDATE log_stocks t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE sales_order_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE sales_delivery_orders_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE stock_transfer_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE stock_transfer_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.received_unit_id
SET t.received_unit_id = m.to_unit_id;

UPDATE purchase_orders_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE production_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE product_issues_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE customer_product_return_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE customer_supply_return_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE return_supplies_detail t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE stock_opname_lines t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE stock_opname_bahan_lines t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE product_variants t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE product_variants t
JOIN tmp_unit_remap m ON m.from_unit_id = t.retail_unit
SET t.retail_unit = m.to_unit_id;

UPDATE product_variants t
JOIN tmp_unit_remap m ON m.from_unit_id = t.safety_unit_id
SET t.safety_unit_id = m.to_unit_id;

UPDATE bom_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

-- -----------------------------------------------------------------------------
-- 08 deactivate legacy units
-- -----------------------------------------------------------------------------
UPDATE units u
JOIN tmp_unit_remap m ON m.from_unit_id = u.unit_id
SET u.status = 0, u.updated_at = NOW();

UPDATE units SET unit_name = 'Dus', unit_short_name = 'Dus', status = 1, updated_at = NOW()
WHERE unit_id = 126;

UPDATE units SET unit_name = 'Pcs', unit_short_name = 'Pcs', status = 1, updated_at = NOW()
WHERE unit_id = 127;

UPDATE units SET status = 0, updated_at = NOW()
WHERE status = 1
  AND unit_id IN (96, 98, 100, 102, 110);

-- -----------------------------------------------------------------------------
-- 09 verify (lalu kamu COMMIT / ROLLBACK manual)
-- -----------------------------------------------------------------------------
SELECT 'product_stocks leftover' AS cek, COUNT(*) AS n
FROM product_stocks ps
JOIN tmp_unit_remap m ON m.from_unit_id = ps.unit_id
UNION ALL
SELECT 'supplies_stocks leftover', COUNT(*)
FROM supplies_stocks ss
JOIN tmp_unit_remap m ON m.from_unit_id = ss.unit_id;

SELECT unit_id, ref_unit_id, unit_name, unit_short_name, status
FROM units
WHERE unit_id IN (7, 9, 126, 127)
ORDER BY unit_id;

SELECT scope, qty_before, qty_after, (qty_after - qty_before) AS delta
FROM tmp_stock_checksum;

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;

SELECT 'SELESAI — cek hasil di atas. Kalau OK ketik: COMMIT;  Kalau ragu: ROLLBACK;' AS next_step;

-- COMMIT;
-- ROLLBACK;
