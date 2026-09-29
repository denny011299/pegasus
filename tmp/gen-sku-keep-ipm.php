<?php
/**
 * Generate SKU sync SQL — KEEP IPM product structure, attach PMO ref, drop PMO twins.
 * Source: u906028329_dev (4).sql
 */
$path = 'c:/Users/Ruben/Downloads/u906028329_dev (4).sql';
$outDir = dirname(__DIR__) . '/database/scripts/sync-sku-duplicates';
@mkdir($outDir, 0777, true);

function loadAllInserts(string $path, string $table): string
{
    $fh = fopen($path, 'r');
    $chunks = [];
    $cap = false;
    $buf = '';
    while (($line = fgets($fh)) !== false) {
        if (preg_match('/^INSERT INTO `'.preg_quote($table, '/').'`/', $line)) {
            $cap = true;
            $buf = '';
        }
        if ($cap) {
            $buf .= $line;
            if (str_ends_with(rtrim($line), ';')) {
                $chunks[] = $buf;
                $cap = false;
            }
        }
    }
    fclose($fh);
    if ($chunks === []) {
        return '';
    }
    // Merge: first keeps header, rest contribute value tuples
    $first = $chunks[0];
    if (! preg_match('/^(INSERT INTO `[^`]+` \([^)]+\) VALUES)\s*/s', $first, $hm)) {
        return $first;
    }
    $header = $hm[1];
    $allValues = [];
    foreach ($chunks as $chunk) {
        if (! preg_match('/VALUES\s*(.+);\s*$/s', $chunk, $m)) {
            continue;
        }
        $allValues[] = rtrim(trim($m[1]), ',');
    }

    return $header."\n".implode(",\n", $allValues).';';
}

function parseRows(string $ins): array
{
    if (! preg_match('/VALUES\s*(.+);\s*$/s', $ins, $m)) {
        return [];
    }
    $body = $m[1];
    $rows = [];
    $len = strlen($body);
    $i = 0;
    while ($i < $len) {
        while ($i < $len && ($body[$i] === ',' || ctype_space($body[$i]))) {
            $i++;
        }
        if ($i >= $len) {
            break;
        }
        if ($body[$i] !== '(') {
            $i++;
            continue;
        }
        $i++;
        $fields = [];
        $cur = '';
        $inStr = false;
        while ($i < $len) {
            $ch = $body[$i];
            if ($inStr) {
                if ($ch === '\\') {
                    $cur .= $ch.$body[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($ch === "'") {
                    if ($i + 1 < $len && $body[$i + 1] === "'") {
                        $cur .= "''";
                        $i += 2;
                        continue;
                    }
                    $inStr = false;
                    $cur .= $ch;
                    $i++;
                    continue;
                }
                $cur .= $ch;
                $i++;
                continue;
            }
            if ($ch === "'") {
                $inStr = true;
                $cur .= $ch;
                $i++;
                continue;
            }
            if ($ch === ',') {
                $fields[] = $cur;
                $cur = '';
                $i++;
                continue;
            }
            if ($ch === ')') {
                $fields[] = $cur;
                $i++;
                break;
            }
            $cur .= $ch;
            $i++;
        }
        $rows[] = $fields;
    }

    return $rows;
}

function unq(?string $v): ?string
{
    if ($v === null) {
        return null;
    }
    $v = trim($v);
    if (strcasecmp($v, 'NULL') === 0) {
        return null;
    }
    if (strlen($v) >= 2 && $v[0] === "'" && substr($v, -1) === "'") {
        return stripcslashes(str_replace("''", "'", substr($v, 1, -1)));
    }

    return $v;
}

function sqlStr(string $s): string
{
    return "'".str_replace(["\\", "'"], ["\\\\", "''"], $s)."'";
}

$prodIns = loadAllInserts($path, 'products');
$varIns = loadAllInserts($path, 'product_variants');
preg_match('/INSERT INTO `products` \(([^)]+)\) VALUES/s', $prodIns, $pc);
preg_match('/INSERT INTO `product_variants` \(([^)]+)\) VALUES/s', $varIns, $vc);
$pi = array_flip(array_map(fn ($x) => trim($x, " `\n\r\t"), explode(',', $pc[1])));
$vi = array_flip(array_map(fn ($x) => trim($x, " `\n\r\t"), explode(',', $vc[1])));

$products = [];
foreach (parseRows($prodIns) as $f) {
    $id = (int) $f[$pi['product_id']];
    $products[$id] = [
        'ref' => unq($f[$pi['ref_product_id']]),
        'name' => unq($f[$pi['product_name']]),
        'status' => (int) $f[$pi['status']],
    ];
}

$variants = [];
$bySku = [];
foreach (parseRows($varIns) as $f) {
    $id = (int) $f[$vi['product_variant_id']];
    $sku = unq($f[$vi['product_variant_sku']]) ?? '';
    $row = [
        'id' => $id,
        'product_id' => (int) $f[$vi['product_id']],
        'sku' => $sku,
        'name' => unq($f[$vi['product_variant_name']]),
        'status' => (int) $f[$vi['status']],
        'unit_id' => (int) $f[$vi['unit_id']],
    ];
    $variants[$id] = $row;
    if ($row['status'] !== 1) {
        continue;
    }
    $k = strtolower(trim($sku));
    if ($k === '') {
        continue;
    }
    $bySku[$k][] = $row;
}

$pairs = [];
$ambiguous = [];
foreach ($bySku as $skuKey => $list) {
    if (count($list) < 2) {
        continue;
    }
    $locals = [];
    $pmos = [];
    foreach ($list as $v) {
        $p = $products[$v['product_id']] ?? null;
        $hasRef = $p && $p['ref'] !== null && $p['ref'] !== '';
        $enriched = $v + ['product' => $p, 'ref' => $p['ref'] ?? null];
        if ($hasRef) {
            $pmos[] = $enriched;
        } else {
            $locals[] = $enriched;
        }
    }
    if (count($locals) === 1 && count($pmos) === 1) {
        $keep = $locals[0];
        $drop = $pmos[0];
        $pairs[] = [
            'sku_key' => $skuKey,
            'keep_vid' => $keep['id'],
            'keep_pid' => $keep['product_id'],
            'keep_sku' => $keep['sku'],
            'keep_pname' => $keep['product']['name'] ?? '',
            'keep_unit' => $keep['unit_id'],
            'drop_vid' => $drop['id'],
            'drop_pid' => $drop['product_id'],
            'drop_sku' => $drop['sku'],
            'drop_ref' => $drop['ref'],
            'drop_pname' => $drop['product']['name'] ?? '',
            'drop_unit' => $drop['unit_id'],
        ];
    } else {
        $ambiguous[] = [
            'sku' => $skuKey,
            'n_local' => count($locals),
            'n_pmo' => count($pmos),
            'ids' => array_map(fn ($v) => $v['id'].'@p'.$v['product_id'], $list),
        ];
    }
}

usort($pairs, fn ($a, $b) => $a['keep_vid'] <=> $b['keep_vid'] ?: $a['drop_vid'] <=> $b['drop_vid']);

// Ref plan: one ref per keep_pid (first pair wins)
$refPlan = [];
foreach ($pairs as $p) {
    $pid = $p['keep_pid'];
    if (! isset($refPlan[$pid])) {
        $refPlan[$pid] = [
            'ref' => $p['drop_ref'],
            'from_drop_pid' => $p['drop_pid'],
            'extras' => [],
        ];
    } elseif ((string) $refPlan[$pid]['ref'] !== (string) $p['drop_ref']) {
        $refPlan[$pid]['extras'][] = $p['drop_ref'].' via drop_p'.$p['drop_pid'].'/v'.$p['drop_vid'];
    }
}

// mapping.md
$md = "# Mapping SKU twin — keep IPM, drop PMO (DEV dump 4)\n\n";
$md .= "**Arah benar:** varian keep tetap di `product_id` IPM (1 produk banyak varian).\n";
$md .= "`ref_product_id` PMO dipasang ke produk IPM (1 ref / produk — UNIQUE).\n";
$md .= "Twin PMO dimatikan; SO/dokumen remap drop→keep.\n\n";
$md .= 'Pasangan clean: **'.count($pairs)."**\n\n";
$md .= "| # | SKU | keep_vid | keep_pid | IPM product | drop_vid | drop_pid | ref | canonical_sku |\n";
$md .= "|---|---|---:|---:|---|---:|---:|---|---|\n";
foreach ($pairs as $i => $p) {
    $md .= '| '.($i + 1)." | `{$p['sku_key']}` | {$p['keep_vid']} | {$p['keep_pid']} | {$p['keep_pname']} | {$p['drop_vid']} | {$p['drop_pid']} | {$p['drop_ref']} | {$p['drop_sku']} |\n";
}
file_put_contents("$outDir/mapping.md", $md);

$am = "# Ambiguous SKU twins (tidak di-merge otomatis)\n\n";
foreach ($ambiguous as $a) {
    $am .= "- `{$a['sku']}`: local={$a['n_local']} pmo={$a['n_pmo']} → ".implode(', ', $a['ids'])."\n";
}
file_put_contents("$outDir/ambiguous.md", $am);

$rp = "# Ref attach plan\n\n";
$rp .= "Kolom `ref_product_id` UNIQUE — 1 produk IPM hanya 1 ref.\n";
$rp .= "Produk multi-varian: ref pertama yang menang; ref PMO lain dibersihkan saat produk drop dimatikan.\n";
$rp .= "Sync lanjutan mengandalkan match SKU (bukan tiap ref PMO).\n\n";
foreach ($refPlan as $pid => $info) {
    $rp .= "- **product_id {$pid}** ← ref `{$info['ref']}` (dari PMO product {$info['from_drop_pid']})";
    if ($info['extras']) {
        $rp .= "\n  - unused: ".implode('; ', $info['extras']);
    }
    $rp .= "\n";
}
file_put_contents("$outDir/ref-attach-plan.md", $rp);

// unit compare
$uc = "# Unit keep vs drop (info)\n\n| SKU | keep_vid | keep_unit | drop_vid | drop_unit | same? |\n|---|---:|---:|---:|---:|---|\n";
foreach ($pairs as $p) {
    $same = $p['keep_unit'] === $p['drop_unit'] ? 'yes' : 'NO';
    $uc .= "| {$p['sku_key']} | {$p['keep_vid']} | {$p['keep_unit']} | {$p['drop_vid']} | {$p['drop_unit']} | {$same} |\n";
}
file_put_contents("$outDir/unit-compare-keep-vs-drop.md", $uc);

// Build SQL values: drop_vid, keep_vid, keep_pid, drop_pid, drop_ref, canonical_sku, note
$values = [];
foreach ($pairs as $p) {
    $note = "{$p['sku_key']} keep={$p['keep_vid']}@p{$p['keep_pid']} drop={$p['drop_vid']}@p{$p['drop_pid']}";
    $values[] = sprintf(
        "  (%d, %d, %d, %d, %s, %s, %s)",
        $p['drop_vid'],
        $p['keep_vid'],
        $p['keep_pid'],
        $p['drop_pid'],
        $p['drop_ref'] === null ? 'NULL' : sqlStr((string) $p['drop_ref']),
        sqlStr($p['drop_sku']),
        sqlStr($note)
    );
}

$refValues = [];
foreach ($refPlan as $pid => $info) {
    $refValues[] = sprintf(
        "  (%d, %d, %s)",
        $pid,
        $info['from_drop_pid'],
        $info['ref'] === null ? 'NULL' : sqlStr((string) $info['ref'])
    );
}

$sql = <<<'SQL'
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
VALUES
SQL;

$sql .= implode(",\n", $values).";\n\n";
$sql .= "SELECT * FROM tmp_variant_remap ORDER BY keep_variant_id, drop_variant_id;\n\n";

$sql .= <<<'SQL'
-- Produk IPM yang akan menerima 1 ref (UNIQUE)
DROP TEMPORARY TABLE IF EXISTS tmp_ref_attach;
CREATE TEMPORARY TABLE tmp_ref_attach (
  keep_product_id INT NOT NULL PRIMARY KEY,
  from_drop_product_id INT NOT NULL,
  ref_product_id VARCHAR(64) NOT NULL
) ENGINE=Memory;

INSERT INTO tmp_ref_attach (keep_product_id, from_drop_product_id, ref_product_id) VALUES
SQL;
$sql .= implode(",\n", $refValues).";\n\n";
$sql .= "SELECT * FROM tmp_ref_attach ORDER BY keep_product_id;\n\n";

$sql .= <<<'SQL'
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
SQL;

file_put_contents("$outDir/ALL_IN_ONE_phpmyadmin.sql", $sql);

$readme = <<<'MD'
# Sync SKU kembar — keep IPM + pasang ref PMO

**Arah benar (bukan pindah ke produk PMO):**

1. Varian lokal (tanpa ref) = **KEEP** — tetap di produk IPM (1 produk banyak varian)
2. Varian sync PMO (produk ber-ref) = **DROP** — dimatikan
3. `ref_product_id` dari produk PMO dipasang ke produk IPM (1 ref / produk)
4. SO / pengiriman / stok / BOM remap drop → keep

## Urutan di DEV

1. Restore dump **DEV(4)** (kalau staging sudah kena merge SKU arah lama)
2. `migrate-units-ipm-to-pmo/ALL_IN_ONE_phpmyadmin.sql` → cek → `COMMIT;`
3. File ini → Go (auto-COMMIT di akhir)
4. Cek sample AIR AKI: varian 14 di `product_id=3`, ada ref, 603 status=0

## Mapping

Lihat `mapping.md`, `ref-attach-plan.md`, `ambiguous.md`.
MD;
file_put_contents("$outDir/README.md", $readme);

echo 'pairs='.count($pairs).' ambiguous='.count($ambiguous).' ref_targets='.count($refPlan)."\n";
echo "Wrote $outDir\n";
