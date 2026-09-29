<?php
/**
 * Generate safe unit remapping SQL: IPM legacy → PMO-synced unit_id.
 * Source: DEV dump units + PMO oms_product_unit.
 */
$devPath = 'c:/Users/Ruben/Downloads/u906028329_dev (2).sql';
$outSql = 'c:/Users/Ruben/Downloads/migrate-units-ipm-to-pmo.sql';
$outMap = 'c:/Users/Ruben/Downloads/migrate-units-ipm-to-pmo-map.md';

$dev = file_get_contents($devPath);
preg_match('/INSERT INTO `units`[^;]+;/s', $dev, $d);
preg_match_all(
    "/\\((\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+)/",
    $d[0],
    $rows,
    PREG_SET_ORDER
);

$units = [];
foreach ($rows as $r) {
    $units[(int) $r[1]] = [
        'unit_id' => (int) $r[1],
        'ref' => $r[2] === 'NULL' ? null : (int) $r[2],
        'name' => stripcslashes($r[3]),
        'short' => stripcslashes($r[4]),
        'status' => (int) $r[5],
    ];
}

$norm = static fn (string $s): string => str_replace([' ', '-', '_'], '', mb_strtolower(trim($s)));

// PMO canonical titles → preferred keep unit_id (ref_unit_id = PMO id, else best name match)
$pmoCanon = [
    '9506012026014611' => 'Dus',
    '9506012026014612' => 'Pcs',
    '9506012026014613' => 'Pack',
    '9506012026014614' => 'Pail',
    '9506012026014615' => 'Drum',
    '9506012026014616' => 'Jerigen',
    '5013082026141116' => 'Lusinan',
    '5017092026160218' => 'Satuann',
    '9417092026160619' => 'satuann', // duplicate title family
    '1717092026163307' => 'gelasin',
    '718092026120640' => 'SATUANBARU',
    '817092026163245' => 'dummy',
];

// Explicit keep targets (PMO-synced rows we already saw)
$keepByPmoRef = [];
foreach ($units as $u) {
    if ($u['ref'] !== null) {
        $keepByPmoRef[(string) $u['ref']] = $u['unit_id'];
    }
}

// Aliases: legacy name → PMO title key for grouping
$aliasToPmoTitle = [
    'dos' => 'Dus',
    'dus' => 'Dus',
    'pcs' => 'Pcs',
    'pc' => 'Pcs',
    'piece' => 'Pcs',
    'pieces' => 'Pcs',
    'pack' => 'Pack',
    'pail' => 'Pail',
    'drum' => 'Drum',
    'jerigen' => 'Jerigen',
    'lusinan' => 'Lusinan',
    'satuann' => 'Satuann',
    'satuan' => 'Satuann',
    'gelasin' => 'gelasin',
    'satuanbaru' => 'SATUANBARU',
    'dummy' => 'dummy',
];

// Group unit_ids: never merge two different non-null PMO refs.
$groups = []; // key = pmoRef or 'alias:Title'
foreach ($units as $u) {
    if (str_starts_with($u['name'], 'IPMTEST') || preg_match('/^(test|probe|baru|TEST)/i', $u['name'])) {
        continue;
    }

    if ($u['ref'] !== null && isset($pmoCanon[(string) $u['ref']])) {
        $key = 'ref:'.$u['ref'];
        $groups[$key][] = $u;
        continue;
    }

    $k = $norm($u['name']);
    $k2 = $norm($u['short']);
    $title = $aliasToPmoTitle[$k] ?? $aliasToPmoTitle[$k2] ?? null;
    if ($title === null) {
        continue; // local-only
    }
    // Attach orphan legacy to the PMO-synced group for that title (if exactly one PMO unit)
    $pmoRefsForTitle = [];
    foreach ($pmoCanon as $rid => $t) {
        if ($norm($t) === $norm($title) || ($norm($title) === 'pcs' && $norm($t) === 'pcs')) {
            $pmoRefsForTitle[] = $rid;
        }
    }
    // dos/dus special
    if (in_array($norm($title), ['dus', 'dos'], true)) {
        $pmoRefsForTitle = ['9506012026014611'];
    }
    if (count($pmoRefsForTitle) === 1) {
        $key = 'ref:'.$pmoRefsForTitle[0];
        $groups[$key][] = $u;
    } else {
        // ambiguous / multi PMO — leave orphans alone (deactivate only if junk)
        continue;
    }
}

$mappings = [];
$mapNotes = [];
foreach ($groups as $key => $list) {
    if (count($list) < 2 && ! str_starts_with($key, 'ref:')) {
        continue;
    }

    // Prefer keep = has this PMO ref
    $wantRef = str_starts_with($key, 'ref:') ? (int) substr($key, 4) : null;
    $keep = null;
    foreach ($list as $u) {
        if ($wantRef && $u['ref'] === $wantRef) {
            $keep = $u;
            break;
        }
    }
    if (! $keep) {
        foreach ($list as $u) {
            if ($u['ref'] !== null) {
                $keep = $u;
                break;
            }
        }
    }
    if (! $keep) {
        $keep = $list[0];
    }

    // Hard preference known IDs
    if ($wantRef === 9506012026014611) {
        foreach ($list as $u) {
            if ($u['unit_id'] === 126) {
                $keep = $u;
                break;
            }
        }
    }
    if ($wantRef === 9506012026014612) {
        foreach ($list as $u) {
            if ($u['unit_id'] === 127) {
                $keep = $u;
                break;
            }
        }
    }

    foreach ($list as $u) {
        if ($u['unit_id'] === $keep['unit_id']) {
            continue;
        }
        // Never remap away a unit that has a DIFFERENT PMO ref
        if ($u['ref'] !== null && $keep['ref'] !== null && $u['ref'] !== $keep['ref']) {
            continue;
        }
        $mappings[$u['unit_id']] = $keep['unit_id'];
        $mapNotes[$u['unit_id']] = sprintf(
            '%s (%s) → keep %d %s (%s) [PMO:%s]',
            $u['unit_id'],
            $u['name'],
            $keep['unit_id'],
            $keep['name'],
            $keep['short'],
            $keep['ref'] ?? '—'
        );
    }
}

// Junk units: deactivate only (no remap) — listed separately
$junkIds = [];
foreach ($units as $u) {
    if (str_starts_with($u['name'], 'IPMTEST') || preg_match('/^(test|probe|baru|TEST)/i', $u['name'])) {
        if ($u['status'] === 1) {
            $junkIds[] = $u['unit_id'];
        }
    }
}

// --- SQL generation ---
$cols = [
    // table => [columns...]
    'product_stocks' => ['unit_id'],
    'supplies_stocks' => ['unit_id'],
    'log_stocks' => ['unit_id'],
    'product_variants' => ['unit_id', 'retail_unit', 'safety_unit_id'],
    'product_relations' => ['pr_unit_id_1', 'pr_unit_id_2'],
    'sales_order_details' => ['unit_id'],
    'sales_delivery_orders_details' => ['unit_id'],
    'stock_transfer_details' => ['unit_id', 'received_unit_id'],
    'purchase_orders_details' => ['unit_id'],
    'production_details' => ['unit_id'],
    'product_issues_details' => ['unit_id'],
    'customer_product_return_details' => ['unit_id'],
    'customer_supply_return_details' => ['unit_id'],
    'return_supplies_detail' => ['unit_id'],
    'stock_opname_lines' => ['unit_id'],
    'stock_opname_bahan_lines' => ['unit_id'],
    'bom_details' => ['unit_id'], // may not exist / different name
    'boms' => ['unit_id'],
];

// Known unique conflict tables needing merge
$stockMergeTables = [
    'product_stocks' => [
        'keys' => ['warehouse_id', 'product_variant_id'],
        'qty' => 'ps_stock',
        'unit' => 'unit_id',
    ],
    'supplies_stocks' => [
        'keys' => ['warehouse_id', 'supplies_id'],
        'qty' => 'ss_stock',
        'unit' => 'unit_id',
    ],
];

$sql = [];
$sql[] = '-- =============================================================================';
$sql[] = '-- MIGRATE IPM units → PMO-synced units (SAFE, run on DEV first)';
$sql[] = '-- Generated: '.date('c');
$sql[] = '-- Source of truth: PMO oms_product_unit';
$sql[] = '--';
$sql[] = '-- RULES:';
$sql[] = '--  1. Keep unit_id that already has ref_unit_id = PMO id (e.g. Dus=126).';
$sql[] = '--  2. Remap legacy IPM duplicates (DOS=7 → Dus=126, Piece=9 → Pcs=127, …).';
$sql[] = '--  3. Merge product_stocks / supplies_stocks when unique key would collide.';
$sql[] = '--  4. Soft-deactivate (status=0) legacy + junk IPMTEST units.';
$sql[] = '--  5. Local-only units (kg, liter, sak, …) NOT touched.';
$sql[] = '--';
$sql[] = '-- BEFORE PROD: run STEP 0 dry-run, backup DB, review conflict counts.';
$sql[] = '-- =============================================================================';
$sql[] = '';
$sql[] = 'SET NAMES utf8mb4;';
$sql[] = 'SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;';
$sql[] = 'SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;';
$sql[] = '';

$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = '-- STEP 0 — mapping table';
$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = 'DROP TEMPORARY TABLE IF EXISTS tmp_unit_remap;';
$sql[] = 'CREATE TEMPORARY TABLE tmp_unit_remap (';
$sql[] = '  from_unit_id INT NOT NULL PRIMARY KEY,';
$sql[] = '  to_unit_id INT NOT NULL,';
$sql[] = '  note VARCHAR(255) NULL';
$sql[] = ') ENGINE=Memory;';
$sql[] = '';

foreach ($mappings as $from => $to) {
    $note = addslashes($mapNotes[$from] ?? '');
    $sql[] = "INSERT INTO tmp_unit_remap (from_unit_id, to_unit_id, note) VALUES ({$from}, {$to}, '{$note}');";
}
$sql[] = '';

$sql[] = '-- Dry-run: berapa baris terdampak per mapping';
$sql[] = 'SELECT m.from_unit_id, m.to_unit_id, m.note,';
$sql[] = '  (SELECT COUNT(*) FROM product_stocks ps WHERE ps.unit_id = m.from_unit_id) AS product_stocks,';
$sql[] = '  (SELECT COUNT(*) FROM log_stocks ls WHERE ls.unit_id = m.from_unit_id) AS log_stocks,';
$sql[] = '  (SELECT COUNT(*) FROM sales_order_details sod WHERE sod.unit_id = m.from_unit_id) AS so_details,';
$sql[] = '  (SELECT COUNT(*) FROM stock_transfer_details std WHERE std.unit_id = m.from_unit_id OR std.received_unit_id = m.from_unit_id) AS st_details,';
$sql[] = '  (SELECT COUNT(*) FROM product_variants pv WHERE pv.unit_id = m.from_unit_id OR pv.retail_unit = m.from_unit_id OR IFNULL(pv.safety_unit_id,0) = m.from_unit_id) AS variants,';
$sql[] = '  (SELECT COUNT(*) FROM product_relations pr WHERE pr.pr_unit_id_1 = m.from_unit_id OR pr.pr_unit_id_2 = m.from_unit_id) AS relations';
$sql[] = 'FROM tmp_unit_remap m';
$sql[] = 'ORDER BY m.from_unit_id;';
$sql[] = '';

$sql[] = '-- Konflik stok (baris from+to hidup di gudang+varian yang sama) — HARUS merge';
$sql[] = 'SELECT m.from_unit_id, m.to_unit_id, ps_from.warehouse_id, ps_from.product_variant_id,';
$sql[] = '       ps_from.ps_stock AS stock_from, ps_to.ps_stock AS stock_to';
$sql[] = 'FROM tmp_unit_remap m';
$sql[] = 'JOIN product_stocks ps_from ON ps_from.unit_id = m.from_unit_id';
$sql[] = 'JOIN product_stocks ps_to';
$sql[] = '  ON ps_to.unit_id = m.to_unit_id';
$sql[] = ' AND ps_to.warehouse_id = ps_from.warehouse_id';
$sql[] = ' AND ps_to.product_variant_id = ps_from.product_variant_id;';
$sql[] = '';

$sql[] = '-- >>> STOP di sini kalau dry-run aneh. Backup dulu. Lanjut START TRANSACTION. <<<';
$sql[] = '';
$sql[] = 'START TRANSACTION;';
$sql[] = '';

$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = '-- STEP 1 — merge product_stocks collisions lalu remap';
$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = 'UPDATE product_stocks ps_to';
$sql[] = 'JOIN tmp_unit_remap m ON m.to_unit_id = ps_to.unit_id';
$sql[] = 'JOIN product_stocks ps_from';
$sql[] = '  ON ps_from.unit_id = m.from_unit_id';
$sql[] = ' AND ps_from.warehouse_id = ps_to.warehouse_id';
$sql[] = ' AND ps_from.product_variant_id = ps_to.product_variant_id';
$sql[] = 'SET ps_to.ps_stock = IFNULL(ps_to.ps_stock,0) + IFNULL(ps_from.ps_stock,0),';
$sql[] = '    ps_to.updated_at = NOW();';
$sql[] = '';
$sql[] = 'DELETE ps_from FROM product_stocks ps_from';
$sql[] = 'JOIN tmp_unit_remap m ON m.from_unit_id = ps_from.unit_id';
$sql[] = 'JOIN product_stocks ps_to';
$sql[] = '  ON ps_to.unit_id = m.to_unit_id';
$sql[] = ' AND ps_to.warehouse_id = ps_from.warehouse_id';
$sql[] = ' AND ps_to.product_variant_id = ps_from.product_variant_id;';
$sql[] = '';
$sql[] = 'UPDATE product_stocks ps';
$sql[] = 'JOIN tmp_unit_remap m ON m.from_unit_id = ps.unit_id';
$sql[] = 'SET ps.unit_id = m.to_unit_id, ps.updated_at = NOW();';
$sql[] = '';

$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = '-- STEP 2 — merge supplies_stocks (jika kolom ss_stock ada)';
$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = 'UPDATE supplies_stocks ss_to';
$sql[] = 'JOIN tmp_unit_remap m ON m.to_unit_id = ss_to.unit_id';
$sql[] = 'JOIN supplies_stocks ss_from';
$sql[] = '  ON ss_from.unit_id = m.from_unit_id';
$sql[] = ' AND ss_from.warehouse_id <=> ss_to.warehouse_id';
$sql[] = ' AND ss_from.supplies_id = ss_to.supplies_id';
$sql[] = 'SET ss_to.ss_stock = IFNULL(ss_to.ss_stock,0) + IFNULL(ss_from.ss_stock,0);';
$sql[] = '';
$sql[] = 'DELETE ss_from FROM supplies_stocks ss_from';
$sql[] = 'JOIN tmp_unit_remap m ON m.from_unit_id = ss_from.unit_id';
$sql[] = 'JOIN supplies_stocks ss_to';
$sql[] = '  ON ss_to.unit_id = m.to_unit_id';
$sql[] = ' AND ss_to.warehouse_id <=> ss_from.warehouse_id';
$sql[] = ' AND ss_to.supplies_id = ss_from.supplies_id;';
$sql[] = '';
$sql[] = 'UPDATE supplies_stocks ss';
$sql[] = 'JOIN tmp_unit_remap m ON m.from_unit_id = ss.unit_id';
$sql[] = 'SET ss.unit_id = m.to_unit_id;';
$sql[] = '';

$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = '-- STEP 3 — product_relations: hapus duplikat pair lalu remap';
$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = 'DELETE pr_from FROM product_relations pr_from';
$sql[] = 'JOIN tmp_unit_remap m1 ON m1.from_unit_id IN (pr_from.pr_unit_id_1, pr_from.pr_unit_id_2)';
$sql[] = 'JOIN product_relations pr_to';
$sql[] = '  ON pr_to.product_variant_id = pr_from.product_variant_id';
$sql[] = ' AND pr_to.pr_unit_id_1 = IF(pr_from.pr_unit_id_1 = m1.from_unit_id, m1.to_unit_id, pr_from.pr_unit_id_1)';
$sql[] = ' AND pr_to.pr_unit_id_2 = IF(pr_from.pr_unit_id_2 = m1.from_unit_id, m1.to_unit_id, pr_from.pr_unit_id_2)';
$sql[] = ' AND pr_to.pr_id <> pr_from.pr_id';
$sql[] = 'WHERE pr_from.pr_unit_id_1 = m1.from_unit_id OR pr_from.pr_unit_id_2 = m1.from_unit_id;';
$sql[] = '';
$sql[] = '-- Simple remap (may need second pass if both ends remap)';
$sql[] = 'UPDATE product_relations pr';
$sql[] = 'JOIN tmp_unit_remap m ON m.from_unit_id = pr.pr_unit_id_1';
$sql[] = 'SET pr.pr_unit_id_1 = m.to_unit_id;';
$sql[] = 'UPDATE product_relations pr';
$sql[] = 'JOIN tmp_unit_remap m ON m.from_unit_id = pr.pr_unit_id_2';
$sql[] = 'SET pr.pr_unit_id_2 = m.to_unit_id;';
$sql[] = '';

$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = '-- STEP 4 — remap FK biasa (tanpa unique merge)';
$sql[] = '-- -----------------------------------------------------------------------------';

$simpleUpdates = [
    ['log_stocks', 'unit_id'],
    ['sales_order_details', 'unit_id'],
    ['sales_delivery_orders_details', 'unit_id'],
    ['stock_transfer_details', 'unit_id'],
    ['stock_transfer_details', 'received_unit_id'],
    ['purchase_orders_details', 'unit_id'],
    ['production_details', 'unit_id'],
    ['product_issues_details', 'unit_id'],
    ['customer_product_return_details', 'unit_id'],
    ['customer_supply_return_details', 'unit_id'],
    ['return_supplies_detail', 'unit_id'],
    ['stock_opname_lines', 'unit_id'],
    ['stock_opname_bahan_lines', 'unit_id'],
    ['product_variants', 'unit_id'],
    ['product_variants', 'retail_unit'],
    ['product_variants', 'safety_unit_id'],
    ['bom_details', 'unit_id'],
];

foreach ($simpleUpdates as [$table, $col]) {
    $sql[] = "-- {$table}.{$col}";
    $sql[] = "UPDATE {$table} t";
    $sql[] = 'JOIN tmp_unit_remap m ON m.from_unit_id = t.'.$col;
    $sql[] = 'SET t.'.$col.' = m.to_unit_id;';
    $sql[] = '';
}

$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = '-- STEP 5 — soft-deactivate legacy remapped units + junk IPMTEST';
$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = 'UPDATE units u';
$sql[] = 'JOIN tmp_unit_remap m ON m.from_unit_id = u.unit_id';
$sql[] = 'SET u.status = 0, u.updated_at = NOW();';
$sql[] = '';

if ($junkIds !== []) {
    $sql[] = 'UPDATE units SET status = 0, updated_at = NOW() WHERE unit_id IN ('.implode(',', $junkIds).') AND status = 1;';
    $sql[] = '';
}

$sql[] = '-- Pastikan KEEP units aktif + nama mengikuti PMO (opsional rename)';
$sql[] = "UPDATE units SET unit_name = 'Dus', unit_short_name = 'Dus', status = 1, updated_at = NOW() WHERE unit_id = 126;";
$sql[] = "UPDATE units SET unit_name = 'Pcs', unit_short_name = 'Pcs', status = 1, updated_at = NOW() WHERE unit_id = 127;";
$sql[] = '';

$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = '-- STEP 6 — verify';
$sql[] = '-- -----------------------------------------------------------------------------';
$sql[] = 'SELECT unit_id, ref_unit_id, unit_name, unit_short_name, status FROM units';
$sql[] = 'WHERE unit_name IN (\'Dus\',\'DOS\',\'Dos\',\'Pcs\',\'Piece\',\'Pack\',\'Pail\',\'Drum\',\'Jerigen\')';
$sql[] = '   OR unit_short_name IN (\'Dus\',\'DOS\',\'Pcs\',\'pcs\')';
$sql[] = 'ORDER BY unit_id;';
$sql[] = '';
$sql[] = 'SELECT COUNT(*) AS leftover_from_units FROM product_stocks ps';
$sql[] = 'JOIN tmp_unit_remap m ON m.from_unit_id = ps.unit_id;';
$sql[] = '-- harus 0';
$sql[] = '';
$sql[] = 'COMMIT;';
$sql[] = '-- Kalau ada error: ROLLBACK;';
$sql[] = '';
$sql[] = 'SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;';
$sql[] = 'SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;';

file_put_contents($outSql, implode("\n", $sql));

$md = [];
$md[] = '# Mapping migrasi unit IPM → PMO (kanonik)';
$md[] = '';
$md[] = '**Keep** = unit IPM yang punya `ref_unit_id` PMO (atau nama PMO).';
$md[] = '**From → To** = legacy IPM yang di-remap lalu `status=0`.';
$md[] = '';
$md[] = '| from_unit_id | from name | to_unit_id | to name | PMO ref |';
$md[] = '|--------------|-----------|------------|---------|---------|';
foreach ($mappings as $from => $to) {
    $f = $units[$from];
    $t = $units[$to];
    $md[] = '| **'.$from.'** | '.$f['name'].' / '.$f['short'].' | **'.$to.'** | '.$t['name'].' / '.$t['short'].' | '.($t['ref'] ?? '—').' |';
}
$md[] = '';
$md[] = '## Keep (tidak di-remap)';
$md[] = '';
$keeps = array_unique(array_values($mappings));
foreach ($keeps as $kid) {
    $u = $units[$kid];
    $md[] = '- **'.$kid.'** '.$u['name'].' (`'.$u['short'].'`) ref=`'.($u['ref'] ?? '—').'`';
}
$md[] = '';
$md[] = '## Junk aktif yang hanya di-nonaktifkan (tanpa remap)';
$md[] = implode(', ', $junkIds) ?: '(none)';
$md[] = '';
$md[] = 'File SQL: `migrate-units-ipm-to-pmo.sql`';
$md[] = '';
$md[] = '### Cara jalanin (AMAN)';
$md[] = '1. Backup DB DEV';
$md[] = '2. Jalankan sampai query dry-run (STEP 0) — **jangan** START TRANSACTION dulu kalau mau review';
$md[] = '3. Cek konflik stok; kalau OK, jalankan penuh termasuk COMMIT';
$md[] = '4. Uji: stok produk, SO, stock transfer, relasi satuan';
$md[] = '5. Baru pertimbangkan PROD';

file_put_contents($outMap, implode("\n", $md));

echo "SQL: $outSql\n";
echo "MAP: $outMap\n";
echo 'mappings: '.count($mappings)."\n";
foreach ($mapNotes as $n) {
    echo "  - $n\n";
}
echo 'junk deactivate: '.count($junkIds)."\n";
