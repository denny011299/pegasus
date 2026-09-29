<?php

/**
 * Generate SQL: merge casefold SKU duplicates (LOKAL keep → attach to PMO product).
 * Keep local variant_id (stock/history). Move under PMO product (has ref_product_id).
 * Deactivate PMO empty twin. Remap SO/docs from drop → keep.
 */

$path = 'c:/Users/Ruben/Downloads/u906028329_dev (5).sql';
$outDir = dirname(__DIR__).'/database/scripts/sync-sku-duplicates';
@mkdir($outDir, 0777, true);

function loadInsert(string $path, string $table): string
{
    $fh = fopen($path, 'r');
    $out = '';
    $cap = false;
    while (($line = fgets($fh)) !== false) {
        if (preg_match('/^INSERT INTO `'.preg_quote($table, '/').'`/', $line)) {
            $cap = true;
        }
        if ($cap) {
            $out .= $line;
            if (str_ends_with(rtrim($line), ';')) {
                $cap = false;
            }
        }
    }
    fclose($fh);

    return $out;
}

$productsSql = loadInsert($path, 'products');
$variantsSql = loadInsert($path, 'product_variants');
$stocksSql = loadInsert($path, 'product_stocks');
$sodSql = loadInsert($path, 'sales_order_details');

$products = [];
preg_match_all(
    "/\\((\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*(\\d+),\\s*(\\d+)/",
    $productsSql,
    $prows,
    PREG_SET_ORDER
);
foreach ($prows as $r) {
    $products[(int) $r[1]] = [
        'ref' => $r[2] === 'NULL' ? null : (int) $r[2],
        'name' => stripcslashes($r[3]),
        'status' => (int) $r[9],
    ];
}

$variants = [];
preg_match_all(
    "/\\((\\d+),\\s*(\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*(NULL|'((?:\\\\'|[^'])*)'),\\s*(NULL|\\d+),\\s*(NULL|\\d+),\\s*(NULL|\\d+),\\s*(\\d+),\\s*(\\d+),\\s*(NULL|\\d+),\\s*(NULL|\\d+),\\s*(NULL|\\d+),\\s*(\\d+)/",
    $variantsSql,
    $vrows,
    PREG_SET_ORDER
);
foreach ($vrows as $r) {
    $vid = (int) $r[1];
    $pid = (int) $r[2];
    $p = $products[$pid] ?? null;
    $variants[$vid] = [
        'id' => $vid,
        'product_id' => $pid,
        'name' => stripcslashes($r[3]),
        'sku' => stripcslashes($r[4]),
        'unit_id' => $r[14] === 'NULL' ? null : (int) $r[14],
        'status' => (int) $r[16],
        'ref' => $p['ref'] ?? null,
        'is_pmo' => ($p['ref'] ?? null) !== null,
        'product_name' => $p['name'] ?? '?',
    ];
}

$stockByVariant = [];
preg_match_all('/\((\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*([0-9.]+)/', $stocksSql, $srows, PREG_SET_ORDER);
foreach ($srows as $r) {
    $stockByVariant[(int) $r[2]] = ($stockByVariant[(int) $r[2]] ?? 0) + (float) $r[6];
}

$soCountByVariant = [];
preg_match_all('/\((\d+),\s*(\d+),\s*(\d+),/', $sodSql, $sodRows, PREG_SET_ORDER);
// too loose - sales_order_details: (sod_id, so_id, product_variant_id, unit_id, ...
preg_match_all(
    "/\\((\\d+),\\s*(\\d+),\\s*(\\d+),\\s*(\\d+),\\s*(NULL|\\d+)/",
    $sodSql,
    $sodRows,
    PREG_SET_ORDER
);
foreach ($sodRows as $r) {
    $vid = (int) $r[3];
    $soCountByVariant[$vid] = ($soCountByVariant[$vid] ?? 0) + 1;
}

// Group casefold SKU
$bySku = [];
foreach ($variants as $v) {
    $key = mb_strtolower(trim($v['sku']));
    if ($key === '' || $key === '—' || $key === '-') {
        continue;
    }
    $bySku[$key][] = $v;
}

$pairs = []; // keep_id, drop_id, pmo_product_id, canonical_sku, note
$skipped = [];

foreach ($bySku as $key => $arr) {
    if (count($arr) < 2) {
        continue;
    }

    $locals = array_values(array_filter($arr, fn ($v) => ! $v['is_pmo']));
    $pmos = array_values(array_filter($arr, fn ($v) => $v['is_pmo']));

    if ($locals === [] || $pmos === []) {
        $skipped[] = ['sku' => $key, 'reason' => 'bukan pasangan LOKAL+PMO', 'n' => count($arr)];

        continue;
    }

    // Prefer local with stock, then active, then lowest id
    usort($locals, function ($a, $b) use ($stockByVariant) {
        $sa = $stockByVariant[$a['id']] ?? 0;
        $sb = $stockByVariant[$b['id']] ?? 0;
        if ($sa != $sb) {
            return $sb <=> $sa;
        }
        if ($a['status'] != $b['status']) {
            return $b['status'] <=> $a['status'];
        }

        return $a['id'] <=> $b['id'];
    });
    $keep = $locals[0];

    // Prefer PMO active with SO refs, then any PMO — drop all PMO twins into keep
    usort($pmos, function ($a, $b) use ($soCountByVariant, $stockByVariant) {
        $soa = $soCountByVariant[$a['id']] ?? 0;
        $sob = $soCountByVariant[$b['id']] ?? 0;
        if ($soa != $sob) {
            return $sob <=> $soa;
        }
        $sa = $stockByVariant[$a['id']] ?? 0;
        $sb = $stockByVariant[$b['id']] ?? 0;
        if ($sa != $sb) {
            return $sb <=> $sa;
        }

        return $a['id'] <=> $b['id'];
    });

    // If multiple local twins, extra locals beyond keep also need handling — deactivate later as orphans
    foreach (array_slice($locals, 1) as $extraLocal) {
        $skipped[] = [
            'sku' => $key,
            'reason' => 'ada >1 lokal; keep='.$keep['id'].', ekstra lokal='.$extraLocal['id'].' (review manual)',
            'n' => 1,
        ];
    }

    // Primary PMO product to attach keep under = first pmo's product
    $primaryPmo = $pmos[0];
    foreach ($pmos as $drop) {
        // Skip if same product already somehow
        if ($drop['id'] === $keep['id']) {
            continue;
        }
        $pairs[] = [
            'sku_key' => $key,
            'keep_id' => $keep['id'],
            'keep_sku' => $keep['sku'],
            'keep_product' => $keep['product_id'],
            'keep_stock' => $stockByVariant[$keep['id']] ?? 0,
            'drop_id' => $drop['id'],
            'drop_sku' => $drop['sku'],
            'drop_product' => $drop['product_id'],
            'drop_stock' => $stockByVariant[$drop['id']] ?? 0,
            'drop_so' => $soCountByVariant[$drop['id']] ?? 0,
            'pmo_product_id' => $primaryPmo['product_id'],
            'canonical_sku' => $primaryPmo['sku'], // spelling from primary PMO
            'ref' => $primaryPmo['ref'],
        ];
    }
}

echo 'pairs: '.count($pairs)."\n";
echo 'skipped: '.count($skipped)."\n";

// --- Write README ---
$readme = <<<MD
# Sync SKU kembar (case-insensitive) — keep lokal + sambung PMO

**Strategi:** jangan remap stok massal. Keep `product_variant_id` lokal (punya stok/history),
pindahkan ke **produk PMO** yang sudah punya `ref_product_id`, nonaktifkan varian PMO kosong,
remap dokumen (SO dll) dari varian drop → keep.

## Cara jalan (phpMyAdmin — satu Go)

1. Backup DB DEV
2. Paste `ALL_IN_ONE_phpmyadmin.sql` → Go
3. Cek hasil SELECT verifikasi
4. `COMMIT;` atau `ROLLBACK;`

## Mapping

Lihat `mapping.md` (keep/drop per SKU).
MD;
file_put_contents("$outDir/README.md", $readme);

// mapping md
$mapLines = ["# Mapping keep ← drop", "", "| sku | keep | keep_stock | drop | drop_stock | drop_SO | pmo_product | ref | canonical_sku |", "|-----|------|------------|------|------------|---------|-------------|-----|---------------|"];
foreach ($pairs as $p) {
    $mapLines[] = sprintf(
        '| `%s` | **%d** (`%s`) | %s | %d (`%s`) | %s | %d | %d | %s | `%s` |',
        $p['sku_key'],
        $p['keep_id'],
        $p['keep_sku'],
        $p['keep_stock'],
        $p['drop_id'],
        $p['drop_sku'],
        $p['drop_stock'],
        $p['drop_so'],
        $p['pmo_product_id'],
        $p['ref'] ?? 'NULL',
        $p['canonical_sku']
    );
}
$mapLines[] = '';
$mapLines[] = '## Skipped / review manual';
$mapLines[] = '';
foreach ($skipped as $s) {
    $mapLines[] = '- `'.$s['sku'].'`: '.$s['reason'];
}
file_put_contents("$outDir/mapping.md", implode("\n", $mapLines));

// Build unique keep→pmo_product + sku updates (one row per keep)
$keepMeta = [];
foreach ($pairs as $p) {
    $kid = $p['keep_id'];
    if (! isset($keepMeta[$kid])) {
        $keepMeta[$kid] = $p;
    }
}

$sql = [];
$sql[] = '-- =============================================================================';
$sql[] = '-- ALL_IN_ONE_phpmyadmin.sql';
$sql[] = '-- Sync SKU casefold duplicates: keep lokal → produk PMO, matikan twin, remap SO';
$sql[] = '-- Sumber mapping: dump u906028329_dev (5).sql';
$sql[] = '-- COMMIT tidak otomatis — cek verifikasi dulu.';
$sql[] = '-- =============================================================================';
$sql[] = '';
$sql[] = 'ROLLBACK;';
$sql[] = 'SET NAMES utf8mb4;';
$sql[] = 'SET @OLD_UNIQUE_CHECKS := @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;';
$sql[] = 'SET @OLD_FOREIGN_KEY_CHECKS := @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;';
$sql[] = '';
$sql[] = 'START TRANSACTION;';
$sql[] = '';
$sql[] = 'DROP TEMPORARY TABLE IF EXISTS tmp_variant_remap;';
$sql[] = 'CREATE TEMPORARY TABLE tmp_variant_remap (';
$sql[] = '  drop_variant_id INT NOT NULL PRIMARY KEY,';
$sql[] = '  keep_variant_id INT NOT NULL,';
$sql[] = '  pmo_product_id INT NOT NULL,';
$sql[] = '  canonical_sku VARCHAR(100) NOT NULL,';
$sql[] = '  note VARCHAR(255) NULL';
$sql[] = ') ENGINE=Memory;';
$sql[] = '';
$sql[] = 'INSERT INTO tmp_variant_remap (drop_variant_id, keep_variant_id, pmo_product_id, canonical_sku, note) VALUES';

$values = [];
foreach ($pairs as $p) {
    $note = addslashes($p['sku_key'].' keep='.$p['keep_id'].' drop='.$p['drop_id']);
    $sku = addslashes($p['canonical_sku']);
    $values[] = sprintf(
        '  (%d, %d, %d, \'%s\', \'%s\')',
        $p['drop_id'],
        $p['keep_id'],
        $p['pmo_product_id'],
        $sku,
        $note
    );
}
$sql[] = implode(",\n", $values).';';
$sql[] = '';
$sql[] = 'SELECT * FROM tmp_variant_remap ORDER BY keep_variant_id, drop_variant_id;';
$sql[] = '';

// Snapshot stock checksum for affected variants
$sql[] = 'DROP TEMPORARY TABLE IF EXISTS tmp_sku_stock_checksum;';
$sql[] = 'CREATE TEMPORARY TABLE tmp_sku_stock_checksum (';
$sql[] = '  scope VARCHAR(32) PRIMARY KEY,';
$sql[] = '  qty_before BIGINT NOT NULL,';
$sql[] = '  qty_after BIGINT NULL';
$sql[] = ') ENGINE=Memory;';
$sql[] = '';
$sql[] = 'INSERT INTO tmp_sku_stock_checksum (scope, qty_before)';
$sql[] = 'SELECT \'product_stocks\', IFNULL(SUM(ps.ps_stock),0)';
$sql[] = 'FROM product_stocks ps';
$sql[] = 'WHERE ps.product_variant_id IN (';
$sql[] = '  SELECT keep_variant_id FROM tmp_variant_remap';
$sql[] = '  UNION';
$sql[] = '  SELECT drop_variant_id FROM tmp_variant_remap';
$sql[] = ');';
$sql[] = '';

// Merge product_stocks drop → keep (same warehouse+unit)
$sql[] = '-- Merge stok drop → keep bila bentrok gudang+unit';
$sql[] = 'UPDATE product_stocks ps_keep';
$sql[] = 'JOIN tmp_variant_remap m ON m.keep_variant_id = ps_keep.product_variant_id';
$sql[] = 'JOIN product_stocks ps_drop';
$sql[] = '  ON ps_drop.product_variant_id = m.drop_variant_id';
$sql[] = ' AND ps_drop.warehouse_id <=> ps_keep.warehouse_id';
$sql[] = ' AND ps_drop.unit_id = ps_keep.unit_id';
$sql[] = 'SET ps_keep.ps_stock = IFNULL(ps_keep.ps_stock,0) + IFNULL(ps_drop.ps_stock,0),';
$sql[] = '    ps_keep.status = IF(ps_keep.status=1 OR ps_drop.status=1, 1, ps_keep.status),';
$sql[] = '    ps_keep.updated_at = NOW();';
$sql[] = '';
$sql[] = 'DELETE ps_drop FROM product_stocks ps_drop';
$sql[] = 'JOIN tmp_variant_remap m ON m.drop_variant_id = ps_drop.product_variant_id';
$sql[] = 'JOIN product_stocks ps_keep';
$sql[] = '  ON ps_keep.product_variant_id = m.keep_variant_id';
$sql[] = ' AND ps_keep.warehouse_id <=> ps_drop.warehouse_id';
$sql[] = ' AND ps_keep.unit_id = ps_drop.unit_id;';
$sql[] = '';
$sql[] = '-- Sisa stok drop → pindah ke keep variant (product_id dirapikan setelah keep pindah produk)';
$sql[] = 'UPDATE product_stocks ps';
$sql[] = 'JOIN tmp_variant_remap m ON m.drop_variant_id = ps.product_variant_id';
$sql[] = 'SET ps.product_variant_id = m.keep_variant_id,';
$sql[] = '    ps.updated_at = NOW();';
$sql[] = '';

// Remap operational FKs (kolom sesuai schema DEV)
// skip: purchase_orders_details (supplies), bom_details (supplies), log_stocks (log_item_id)
$fkTables = [
    ['sales_order_details', 'product_variant_id'],
    ['sales_delivery_orders_details', 'product_variant_id'],
    ['stock_transfer_details', 'product_variant_id'],
    ['production_details', 'product_variant_id'],
    ['product_issues_details', 'item_id'], // item_id = product_variant_id
    ['customer_product_return_details', 'product_variant_id'],
    ['stock_opname_lines', 'product_variant_id'],
    ['boms', 'product_id'], // boms.product_id = product_variant_id
    ['product_relations', 'product_variant_id'],
];

$sql[] = '-- Remap dokumen drop → keep';
foreach ($fkTables as [$table, $col]) {
    $sql[] = "UPDATE `$table` t";
    $sql[] = 'JOIN tmp_variant_remap m ON m.drop_variant_id = t.'.$col;
    $sql[] = 'SET t.'.$col.' = m.keep_variant_id;';
    $sql[] = '';
}

// Attach keep to PMO product + canonical SKU
$sql[] = '-- Keep: pindah ke produk PMO + SKU kanonik + aktif';
$sql[] = 'UPDATE product_variants pv';
$sql[] = 'JOIN (';
$sql[] = '  SELECT keep_variant_id, MIN(pmo_product_id) AS pmo_product_id, MIN(canonical_sku) AS canonical_sku';
$sql[] = '  FROM tmp_variant_remap';
$sql[] = '  GROUP BY keep_variant_id';
$sql[] = ') m ON m.keep_variant_id = pv.product_variant_id';
$sql[] = 'SET pv.product_id = m.pmo_product_id,';
$sql[] = '    pv.product_variant_sku = m.canonical_sku,';
$sql[] = '    pv.status = 1,';
$sql[] = '    pv.updated_at = NOW();';
$sql[] = '';

// Fix product_id on stocks for keep after move
$sql[] = 'UPDATE product_stocks ps';
$sql[] = 'JOIN product_variants pv ON pv.product_variant_id = ps.product_variant_id';
$sql[] = 'JOIN (SELECT DISTINCT keep_variant_id FROM tmp_variant_remap) m ON m.keep_variant_id = ps.product_variant_id';
$sql[] = 'SET ps.product_id = pv.product_id, ps.updated_at = NOW();';
$sql[] = '';

// Deactivate drop variants
$sql[] = 'UPDATE product_variants pv';
$sql[] = 'JOIN tmp_variant_remap m ON m.drop_variant_id = pv.product_variant_id';
$sql[] = 'SET pv.status = 0,';
$sql[] = '    pv.product_variant_sku = CONCAT(pv.product_variant_sku, \'-DUP\', pv.product_variant_id),';
$sql[] = '    pv.updated_at = NOW();';
$sql[] = '';

// Ensure PMO products stay active
$sql[] = 'UPDATE products p';
$sql[] = 'JOIN (SELECT DISTINCT pmo_product_id FROM tmp_variant_remap) m ON m.pmo_product_id = p.product_id';
$sql[] = 'SET p.status = 1, p.updated_at = NOW();';
$sql[] = '';

// Checksum
$sql[] = 'UPDATE tmp_sku_stock_checksum c';
$sql[] = 'SET c.qty_after = (';
$sql[] = '  SELECT IFNULL(SUM(ps.ps_stock),0) FROM product_stocks ps';
$sql[] = '  WHERE ps.product_variant_id IN (';
$sql[] = '    SELECT keep_variant_id FROM tmp_variant_remap';
$sql[] = '    UNION SELECT drop_variant_id FROM tmp_variant_remap';
$sql[] = '  )';
$sql[] = ')';
$sql[] = 'WHERE c.scope = \'product_stocks\';';
$sql[] = '';
$sql[] = 'SELECT * FROM tmp_sku_stock_checksum;';
$sql[] = 'SELECT IF(qty_after = qty_before, \'STOCK CHECKSUM OK\', \'STOCK CHECKSUM GAGAL\') AS stock_guard';
$sql[] = 'FROM tmp_sku_stock_checksum WHERE scope = \'product_stocks\';';
$sql[] = '';
$sql[] = 'SET @bad := (SELECT COUNT(*) FROM tmp_sku_stock_checksum WHERE qty_after IS NULL OR qty_after <> qty_before);';
$sql[] = 'SET @guard_sql := IF(@bad = 0, \'SELECT \\\'guard_pass\\\' AS guard\', \'SELECT * FROM `__STOP_SKU_STOCK_CHECKSUM_GAGAL__`\');';
$sql[] = 'PREPARE guard_stmt FROM @guard_sql;';
$sql[] = 'EXECUTE guard_stmt;';
$sql[] = 'DEALLOCATE PREPARE guard_stmt;';
$sql[] = '';

// Verify AIR AKI sample + leftovers
$sql[] = 'SELECT \'SO leftover on drop\' AS cek, COUNT(*) AS n';
$sql[] = 'FROM sales_order_details sod';
$sql[] = 'JOIN tmp_variant_remap m ON m.drop_variant_id = sod.product_variant_id';
$sql[] = 'UNION ALL';
$sql[] = 'SELECT \'stock leftover on drop\', COUNT(*)';
$sql[] = 'FROM product_stocks ps';
$sql[] = 'JOIN tmp_variant_remap m ON m.drop_variant_id = ps.product_variant_id;';
$sql[] = '';
$sql[] = '-- Sample AIR AKI 1500';
$sql[] = 'SELECT pv.product_variant_id, pv.product_id, pv.product_variant_sku, pv.product_variant_name, pv.status,';
$sql[] = '       p.ref_product_id, p.product_name';
$sql[] = 'FROM product_variants pv';
$sql[] = 'JOIN products p ON p.product_id = pv.product_id';
$sql[] = 'WHERE pv.product_variant_id IN (14, 603)';
$sql[] = '   OR LOWER(pv.product_variant_sku) = \'aahk1500ml\'';
$sql[] = 'ORDER BY pv.product_variant_id;';
$sql[] = '';
$sql[] = 'SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;';
$sql[] = 'SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;';
$sql[] = '';
$sql[] = 'SELECT \'SELESAI — kalau OK: COMMIT;  kalau ragu: ROLLBACK;\' AS next_step;';
$sql[] = '-- COMMIT;';
$sql[] = '-- ROLLBACK;';

file_put_contents("$outDir/ALL_IN_ONE_phpmyadmin.sql", implode("\n", $sql));
file_put_contents('c:/Users/Ruben/Downloads/sku-sync-ALL_IN_ONE.sql', implode("\n", $sql));
file_put_contents('c:/Users/Ruben/Downloads/sku-sync-mapping.md', implode("\n", $mapLines));

echo "Wrote $outDir/ALL_IN_ONE_phpmyadmin.sql\n";
echo "pairs sample AIRAKI:\n";
foreach ($pairs as $p) {
    if ($p['sku_key'] === 'aahk1500ml') {
        print_r($p);
    }
}
