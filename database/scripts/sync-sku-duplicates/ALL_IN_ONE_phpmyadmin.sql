-- =============================================================================
-- ALL_IN_ONE_phpmyadmin.sql
-- Sync SKU twin (casefold): KEEP di produk IPM + pasang ref PMO, matikan twin PMO
-- Sumber mapping: dump u906028329_dev (4).sql
--
-- PRA-SYARAT:
--   1. DB = restore dari DEV(4) ATAU setara (belum kena merge SKU arah lama)
--   2. Migrasi UNIT (DOS→Dus, Piece→Pcs) sudah dijalankan & COMMIT
--   3. Backup DB DEV
--
-- Akhir file: COMMIT otomatis (phpMyAdmin sering putus session).
-- =============================================================================

ROLLBACK;
SET NAMES utf8mb4;
SET @OLD_UNIQUE_CHECKS := @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS := @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

DROP TEMPORARY TABLE IF EXISTS tmp_variant_remap;
CREATE TEMPORARY TABLE tmp_variant_remap (
  drop_variant_id INT NOT NULL PRIMARY KEY,
  keep_variant_id INT NOT NULL,
  keep_product_id INT NOT NULL,
  drop_product_id INT NOT NULL,
  drop_ref VARCHAR(64) NULL,
  canonical_sku VARCHAR(100) NOT NULL,
  note VARCHAR(255) NULL
) ENGINE=Memory;

INSERT INTO tmp_variant_remap
  (drop_variant_id, keep_variant_id, keep_product_id, drop_product_id, drop_ref, canonical_sku, note)
VALUES  (610, 1, 1, 322, '3108062024014246', 'Aap1000ml', 'aap1000ml keep=1@p1 drop=610@p322'),
  (614, 2, 1, 327, '4908062024014525', 'Aap600ml', 'aap600ml keep=2@p1 drop=614@p327'),
  (609, 3, 1, 323, '5006062024124628', 'Aap1500ml', 'aap1500ml keep=3@p1 drop=609@p323'),
  (605, 13, 3, 219, '9109062024052817', 'Aahk600ml', 'aahk600ml keep=13@p3 drop=605@p219'),
  (603, 14, 3, 217, '8109062024052502', 'Aahk1500ml', 'aahk1500ml keep=14@p3 drop=603@p217'),
  (604, 15, 3, 218, '9508062024015348', 'Aahk400ml', 'aahk400ml keep=15@p3 drop=604@p218'),
  (622, 17, 4, 226, '9309062024053056', 'Azhk600ml', 'azhk600ml keep=17@p4 drop=622@p226'),
  (617, 18, 4, 221, '2109062024052926', 'Azhk1500ml', 'azhk1500ml keep=18@p4 drop=617@p221'),
  (619, 19, 4, 223, '8708062024021501', 'Azhk400ml', 'azhk400ml keep=19@p4 drop=619@p223'),
  (683, 24, 8, 348, '2408062024022459', 'RCP1LM', 'rcp1lm keep=24@p8 drop=683@p348'),
  (673, 27, 9, 261, '2508062024022648', 'RCHK5LM', 'rchk5lm keep=27@p9 drop=673@p261'),
  (670, 30, 10, 258, '7008062024023010', 'RCHK30LM', 'rchk30lm keep=30@p10 drop=670@p258'),
  (452, 52, 14, 283, '4412022026073556', 'HKTP100ML', 'hktp100ml keep=52@p14 drop=452@p283'),
  (454, 68, 22, 285, '7627022025032121', 'HKWF100ML', 'hkwf100ml keep=68@p22 drop=454@p285'),
  (448, 72, 25, 279, '1308062024025546', 'hktp30ltr', 'hktp30ltr keep=72@p25 drop=448@p279'),
  (397, 75, 28, 229, '7612022026072159', 'HKCC460ML', 'hkcc460ml keep=75@p28 drop=397@p229'),
  (419, 76, 29, 251, '7704072026141010', 'HK4D220ML', 'hk4d220ml keep=76@p29 drop=419@p251'),
  (650, 80, 32, 245, '5808062024144708', 'MRHK300MLM', 'mrhk300mlm keep=80@p32 drop=650@p245'),
  (651, 85, 34, 246, '6206072024055312', 'MRHK300P', 'mrhk300p keep=85@p34 drop=651@p246'),
  (443, 98, 39, 274, '8508102024085357', 'HSG100GR', 'hsg100gr keep=98@p39 drop=443@p274'),
  (441, 100, 39, 272, '608102024095555', 'HSG450GR', 'hsg450gr keep=100@p39 drop=441@p272'),
  (404, 106, 40, 236, '8812022025013125', 'HGT450GR', 'hgt450gr keep=106@p40 drop=404@p236'),
  (408, 107, 40, 240, '8306092025064101', 'HGT4x4KG', 'hgt4x4kg keep=107@p40 drop=408@p240'),
  (398, 148, 50, 230, '10020062026114929', 'HKCH220ML', 'hkch220ml keep=148@p50 drop=398@p230'),
  (406, 164, 57, 238, '2109012025024759', 'HGT160kg', 'hgt160kg keep=164@p57 drop=406@p238'),
  (680, 167, 60, 316, '9625022025073114', 'RCO5LM', 'rco5lm keep=167@p60 drop=680@p316'),
  (676, 170, 61, 312, '1429072024080920', 'RCO1LH', 'rco1lh keep=170@p61 drop=676@p312'),
  (606, 176, 65, 294, '9507072024115944', 'AAKI1L', 'aaki1l keep=176@p65 drop=606@p294'),
  (623, 177, 65, 295, '5107072024120040', 'AZKI1L', 'azki1l keep=177@p65 drop=623@p295');

SELECT * FROM tmp_variant_remap ORDER BY keep_variant_id, drop_variant_id;

-- Produk IPM yang akan menerima 1 ref (UNIQUE)
DROP TEMPORARY TABLE IF EXISTS tmp_ref_attach;
CREATE TEMPORARY TABLE tmp_ref_attach (
  keep_product_id INT NOT NULL PRIMARY KEY,
  from_drop_product_id INT NOT NULL,
  ref_product_id VARCHAR(64) NOT NULL
) ENGINE=Memory;

INSERT INTO tmp_ref_attach (keep_product_id, from_drop_product_id, ref_product_id) VALUES  (1, 322, '3108062024014246'),
  (3, 219, '9109062024052817'),
  (4, 226, '9309062024053056'),
  (8, 348, '2408062024022459'),
  (9, 261, '2508062024022648'),
  (10, 258, '7008062024023010'),
  (14, 283, '4412022026073556'),
  (22, 285, '7627022025032121'),
  (25, 279, '1308062024025546'),
  (28, 229, '7612022026072159'),
  (29, 251, '7704072026141010'),
  (32, 245, '5808062024144708'),
  (34, 246, '6206072024055312'),
  (39, 274, '8508102024085357'),
  (40, 236, '8812022025013125'),
  (50, 230, '10020062026114929'),
  (57, 238, '2109012025024759'),
  (60, 316, '9625022025073114'),
  (61, 312, '1429072024080920'),
  (65, 294, '9507072024115944');

SELECT * FROM tmp_ref_attach ORDER BY keep_product_id;

DROP TEMPORARY TABLE IF EXISTS tmp_sku_stock_checksum;
CREATE TEMPORARY TABLE tmp_sku_stock_checksum (
  scope VARCHAR(32) PRIMARY KEY,
  qty_before BIGINT NOT NULL,
  qty_after BIGINT NULL
) ENGINE=Memory;

INSERT INTO tmp_sku_stock_checksum (scope, qty_before)
SELECT 'product_stocks', IFNULL(SUM(ps.ps_stock),0)
FROM product_stocks ps
WHERE ps.product_variant_id IN (
  SELECT keep_variant_id FROM tmp_variant_remap
  UNION
  SELECT drop_variant_id FROM tmp_variant_remap
);

-- Merge stok drop → keep bila bentrok gudang+unit
UPDATE product_stocks ps_keep
JOIN tmp_variant_remap m ON m.keep_variant_id = ps_keep.product_variant_id
JOIN product_stocks ps_drop
  ON ps_drop.product_variant_id = m.drop_variant_id
 AND ps_drop.warehouse_id <=> ps_keep.warehouse_id
 AND ps_drop.unit_id = ps_keep.unit_id
SET ps_keep.ps_stock = IFNULL(ps_keep.ps_stock,0) + IFNULL(ps_drop.ps_stock,0),
    ps_keep.status = IF(ps_keep.status=1 OR ps_drop.status=1, 1, ps_keep.status),
    ps_keep.updated_at = NOW();

DELETE ps_drop FROM product_stocks ps_drop
JOIN tmp_variant_remap m ON m.drop_variant_id = ps_drop.product_variant_id
JOIN product_stocks ps_keep
  ON ps_keep.product_variant_id = m.keep_variant_id
 AND ps_keep.warehouse_id <=> ps_drop.warehouse_id
 AND ps_keep.unit_id = ps_drop.unit_id;

-- Sisa stok drop → keep variant (product_id = IPM keep)
UPDATE product_stocks ps
JOIN tmp_variant_remap m ON m.drop_variant_id = ps.product_variant_id
SET ps.product_variant_id = m.keep_variant_id,
    ps.product_id = m.keep_product_id,
    ps.updated_at = NOW();

-- Remap dokumen drop → keep
UPDATE `sales_order_details` t
JOIN tmp_variant_remap m ON m.drop_variant_id = t.product_variant_id
SET t.product_variant_id = m.keep_variant_id;

UPDATE `sales_delivery_orders_details` t
JOIN tmp_variant_remap m ON m.drop_variant_id = t.product_variant_id
SET t.product_variant_id = m.keep_variant_id;

UPDATE `stock_transfer_details` t
JOIN tmp_variant_remap m ON m.drop_variant_id = t.product_variant_id
SET t.product_variant_id = m.keep_variant_id;

UPDATE `production_details` t
JOIN tmp_variant_remap m ON m.drop_variant_id = t.product_variant_id
SET t.product_variant_id = m.keep_variant_id;

UPDATE `product_issues_details` t
JOIN tmp_variant_remap m ON m.drop_variant_id = t.item_id
SET t.item_id = m.keep_variant_id;

UPDATE `customer_product_return_details` t
JOIN tmp_variant_remap m ON m.drop_variant_id = t.product_variant_id
SET t.product_variant_id = m.keep_variant_id;

-- stock_opname_lines: unique (sto_id, product_variant_id, unit_id)
DELETE t_drop FROM stock_opname_lines t_drop
JOIN tmp_variant_remap m ON m.drop_variant_id = t_drop.product_variant_id
JOIN stock_opname_lines t_keep
  ON t_keep.sto_id = t_drop.sto_id
 AND t_keep.product_variant_id = m.keep_variant_id
 AND t_keep.unit_id <=> t_drop.unit_id;

UPDATE `stock_opname_lines` t
JOIN tmp_variant_remap m ON m.drop_variant_id = t.product_variant_id
SET t.product_variant_id = m.keep_variant_id;

-- boms.product_id di Pegasus = product_variant_id
DELETE b_drop FROM boms b_drop
JOIN tmp_variant_remap m ON m.drop_variant_id = b_drop.product_id
JOIN boms b_keep ON b_keep.product_id = m.keep_variant_id
 AND b_keep.status = 1
WHERE b_drop.status = 1;

UPDATE `boms` t
JOIN tmp_variant_remap m ON m.drop_variant_id = t.product_id
SET t.product_id = m.keep_variant_id;

UPDATE `product_relations` t
JOIN tmp_variant_remap m ON m.drop_variant_id = t.product_variant_id
SET t.product_variant_id = m.keep_variant_id;

DELETE pr1 FROM product_relations pr1
INNER JOIN product_relations pr2
  ON pr1.product_variant_id = pr2.product_variant_id
 AND pr1.pr_unit_id_1 = pr2.pr_unit_id_1
 AND pr1.pr_unit_id_2 = pr2.pr_unit_id_2
 AND pr1.pr_id > pr2.pr_id;

-- Keep: TETAP di produk IPM + SKU kanonik PMO + aktif
UPDATE product_variants pv
JOIN (
  SELECT keep_variant_id, MIN(keep_product_id) AS keep_product_id, MIN(canonical_sku) AS canonical_sku
  FROM tmp_variant_remap
  GROUP BY keep_variant_id
) m ON m.keep_variant_id = pv.product_variant_id
SET pv.product_id = m.keep_product_id,
    pv.product_variant_sku = m.canonical_sku,
    pv.status = 1,
    pv.updated_at = NOW();

-- Rapikan product_id di stok keep (jaga-jaga)
UPDATE product_stocks ps
JOIN tmp_variant_remap m ON m.keep_variant_id = ps.product_variant_id
SET ps.product_id = m.keep_product_id, ps.updated_at = NOW();

-- Drop twin: matikan + SKU -DUP (hindari unique/casefold bentrok)
UPDATE product_variants pv
JOIN tmp_variant_remap m ON m.drop_variant_id = pv.product_variant_id
SET pv.status = 0,
    pv.product_variant_sku = CONCAT(pv.product_variant_sku, '-DUP', pv.product_variant_id),
    pv.updated_at = NOW();

-- Lepas ref dari produk PMO dulu (UNIQUE), lalu pasang ke produk IPM
UPDATE products p
JOIN (SELECT DISTINCT drop_product_id FROM tmp_variant_remap) m ON m.drop_product_id = p.product_id
SET p.ref_product_id = NULL, p.updated_at = NOW();

UPDATE products p
JOIN tmp_ref_attach r ON r.keep_product_id = p.product_id
SET p.ref_product_id = r.ref_product_id,
    p.status = 1,
    p.updated_at = NOW()
WHERE p.ref_product_id IS NULL;

-- Matikan produk PMO twin (shell 1-SKU)
UPDATE products p
JOIN (SELECT DISTINCT drop_product_id FROM tmp_variant_remap) m ON m.drop_product_id = p.product_id
SET p.status = 0, p.updated_at = NOW();

UPDATE tmp_sku_stock_checksum c
SET c.qty_after = (
  SELECT IFNULL(SUM(ps.ps_stock),0) FROM product_stocks ps
  WHERE ps.product_variant_id IN (
    SELECT keep_variant_id FROM tmp_variant_remap
    UNION SELECT drop_variant_id FROM tmp_variant_remap
  )
)
WHERE c.scope = 'product_stocks';

SELECT * FROM tmp_sku_stock_checksum;
SELECT IF(qty_after = qty_before, 'STOCK CHECKSUM OK', 'STOCK CHECKSUM GAGAL') AS stock_guard
FROM tmp_sku_stock_checksum WHERE scope = 'product_stocks';

SET @bad := (SELECT COUNT(*) FROM tmp_sku_stock_checksum WHERE qty_after IS NULL OR qty_after <> qty_before);
SET @guard_sql := IF(@bad = 0, 'SELECT \'guard_pass\' AS guard', 'SELECT * FROM `__STOP_SKU_STOCK_CHECKSUM_GAGAL__`');
PREPARE guard_stmt FROM @guard_sql;
EXECUTE guard_stmt;
DEALLOCATE PREPARE guard_stmt;

SELECT 'SO leftover on drop' AS cek, COUNT(*) AS n
FROM sales_order_details sod
JOIN tmp_variant_remap m ON m.drop_variant_id = sod.product_variant_id
UNION ALL
SELECT 'stock leftover on drop', COUNT(*)
FROM product_stocks ps
JOIN tmp_variant_remap m ON m.drop_variant_id = ps.product_variant_id;

-- Sample: AIR AKI 1500 harus tetap di product_id IPM (3), punya ref, twin 603 mati
SELECT pv.product_variant_id, pv.product_id, pv.product_variant_sku, pv.product_variant_name, pv.status,
       p.ref_product_id, p.product_name, p.status AS product_status
FROM product_variants pv
JOIN products p ON p.product_id = pv.product_id
WHERE pv.product_variant_id IN (14, 603)
   OR LOWER(pv.product_variant_sku) LIKE 'aahk1500ml%'
ORDER BY pv.product_variant_id;

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;

COMMIT;

SELECT 'DONE — SKU merge keep-IPM sudah di-COMMIT' AS next_step;