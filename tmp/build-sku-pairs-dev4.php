<?php
/**
 * Build SKU twin pairs from DEV(4) dump — keep IPM (no ref), drop PMO (has ref).
 * Output mapping for correct merge: stay on IPM product, attach ref.
 */
$dump = 'c:/Users/Ruben/Downloads/u906028329_dev (4).sql';
$d = file_get_contents($dump);

function parseInsert(string $d, string $table): ?string {
    if (!preg_match('/INSERT INTO `'.preg_quote($table,'/').'`[^;]+;/s', $d, $m)) {
        return null;
    }
    return $m[0];
}

// products: (product_id, ..., product_name, ..., status, ..., ref_product_id?, ...)
// Need flexible parse — inspect one row shape from CREATE
if (!preg_match('/CREATE TABLE `products`.*?ENGINE=/s', $d, $c)) {
    fwrite(STDERR, "no products CREATE\n"); exit(1);
}
// Get column list from INSERT if present
$prodIns = parseInsert($d, 'products');
$varIns = parseInsert($d, 'product_variants');
if (!$prodIns || !$varIns) {
    fwrite(STDERR, "missing INSERT products or product_variants\n");
    exit(1);
}

// Find column order from INSERT INTO `products` (`col`,...) VALUES
preg_match('/INSERT INTO `products` \(([^)]+)\) VALUES/s', $prodIns, $pc);
preg_match('/INSERT INTO `product_variants` \(([^)]+)\) VALUES/s', $varIns, $vc);
$pCols = array_map(fn($x) => trim($x, " `\n\r\t"), explode(',', $pc[1]));
$vCols = array_map(fn($x) => trim($x, " `\n\r\t"), explode(',', $vc[1]));
echo "products cols: ".implode(',', $pCols)."\n";
echo "variants cols: ".implode(',', $vCols)."\n";

$pi = array_flip($pCols);
$vi = array_flip($vCols);

function parseRows(string $ins): array {
    // split values tuples — careful with quotes
    if (!preg_match('/VALUES\s*(.+);\s*$/s', $ins, $m)) return [];
    $body = $m[1];
    $rows = [];
    $len = strlen($body);
    $i = 0;
    while ($i < $len) {
        while ($i < $len && ($body[$i] === ',' || ctype_space($body[$i]))) $i++;
        if ($i >= $len) break;
        if ($body[$i] !== '(') { $i++; continue; }
        $i++; // skip (
        $fields = [];
        $cur = '';
        $inStr = false;
        while ($i < $len) {
            $ch = $body[$i];
            if ($inStr) {
                if ($ch === '\\') { $cur .= $ch.$body[$i+1]; $i += 2; continue; }
                if ($ch === "'") {
                    // '' escape
                    if ($i+1 < $len && $body[$i+1] === "'") { $cur .= "''"; $i += 2; continue; }
                    $inStr = false; $cur .= $ch; $i++; continue;
                }
                $cur .= $ch; $i++; continue;
            }
            if ($ch === "'") { $inStr = true; $cur .= $ch; $i++; continue; }
            if ($ch === ',') { $fields[] = $cur; $cur = ''; $i++; continue; }
            if ($ch === ')') { $fields[] = $cur; $i++; break; }
            $cur .= $ch; $i++;
        }
        $rows[] = $fields;
    }
    return $rows;
}

function unq(?string $v): ?string {
    if ($v === null) return null;
    $v = trim($v);
    if (strcasecmp($v, 'NULL') === 0) return null;
    if (strlen($v) >= 2 && $v[0] === "'" && substr($v,-1) === "'") {
        return stripcslashes(str_replace("''", "'", substr($v, 1, -1)));
    }
    return $v;
}

$products = [];
foreach (parseRows($prodIns) as $f) {
    $id = (int)$f[$pi['product_id']];
    $products[$id] = [
        'id' => $id,
        'name' => unq($f[$pi['product_name']] ?? null),
        'status' => (int)($f[$pi['status']] ?? 1),
        'ref' => unq($f[$pi['ref_product_id']] ?? null),
    ];
}

$variants = [];
foreach (parseRows($varIns) as $f) {
    $id = (int)$f[$vi['product_variant_id']];
    $sku = unq($f[$vi['product_variant_sku']] ?? null) ?? '';
    $variants[$id] = [
        'id' => $id,
        'product_id' => (int)$f[$vi['product_id']],
        'sku' => $sku,
        'name' => unq($f[$vi['product_variant_name']] ?? null),
        'status' => (int)($f[$vi['status']] ?? 1),
        'unit_id' => (int)($f[$vi['unit_id']] ?? 0),
    ];
}

echo "products=".count($products)." variants=".count($variants)."\n";

// group active by casefold sku
$bySku = [];
foreach ($variants as $v) {
    if ($v['status'] !== 1) continue;
    $k = strtolower(trim($v['sku']));
    if ($k === '') continue;
    $bySku[$k][] = $v;
}

$pairs = [];
$ambiguous = [];
foreach ($bySku as $skuKey => $list) {
    if (count($list) < 2) continue;
    $locals = [];
    $pmos = [];
    foreach ($list as $v) {
        $p = $products[$v['product_id']] ?? null;
        $hasRef = $p && $p['ref'] !== null && $p['ref'] !== '';
        if ($hasRef) $pmos[] = $v + ['product' => $p];
        else $locals[] = $v + ['product' => $p];
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
            'drop_vid' => $drop['id'],
            'drop_pid' => $drop['product_id'],
            'drop_sku' => $drop['sku'], // canonical PMO spelling
            'drop_ref' => $drop['product']['ref'],
            'drop_pname' => $drop['product']['name'] ?? '',
            'keep_unit' => $keep['unit_id'],
            'drop_unit' => $drop['unit_id'],
        ];
    } else {
        $ambiguous[] = [
            'sku' => $skuKey,
            'n_local' => count($locals),
            'n_pmo' => count($pmos),
            'ids' => array_map(fn($v) => $v['id'].'@p'.$v['product_id'], $list),
        ];
    }
}

usort($pairs, fn($a,$b) => $a['keep_vid'] <=> $b['keep_vid']);
echo "pairs=".count($pairs)." ambiguous=".count($ambiguous)."\n";

$outDir = __DIR__ . '/../database/scripts/sync-sku-duplicates';
@mkdir($outDir, 0777, true);

$md = "# Mapping SKU twin — keep IPM, drop PMO (dari DEV dump 4)\n\n";
$md .= "Strategi: varian keep **tetap di product_id IPM**; `ref_product_id` PMO dipasang ke produk IPM (jika masih NULL);\n";
$md .= "varian+produk PMO dimatikan; SO/dokumen remap drop→keep.\n\n";
$md .= "| # | SKU | keep_vid | keep_pid | IPM product | drop_vid | drop_pid | ref | canonical_sku |\n|---|---|---|---|---|---|---|---|---|\n";
$sqlValues = [];
foreach ($pairs as $i => $p) {
    $n = $i + 1;
    $md .= "| {$n} | `{$p['sku_key']}` | {$p['keep_vid']} | {$p['keep_pid']} | {$p['keep_pname']} | {$p['drop_vid']} | {$p['drop_pid']} | {$p['drop_ref']} | {$p['drop_sku']} |\n";
    $note = addslashes("{$p['sku_key']} keep={$p['keep_vid']}@p{$p['keep_pid']} drop={$p['drop_vid']}@p{$p['drop_pid']}");
    $sqlValues[] = sprintf(
        "  (%d, %d, %d, %d, %s, '%s', '%s')",
        $p['drop_vid'],
        $p['keep_vid'],
        $p['keep_pid'],
        $p['drop_pid'],
        $p['drop_ref'] === null ? 'NULL' : ("'".addslashes($p['drop_ref'])."'"),
        addslashes($p['drop_sku']),
        $note
    );
}

file_put_contents($outDir.'/mapping.md', $md);
file_put_contents($outDir.'/_pairs_values.sql', implode(",\n", $sqlValues)."\n");

if ($ambiguous) {
    $am = "# Ambiguous SKU twins (skip)\n\n";
    foreach ($ambiguous as $a) {
        $am .= "- {$a['sku']}: local={$a['n_local']} pmo={$a['n_pmo']} ids=".implode(',', $a['ids'])."\n";
    }
    file_put_contents($outDir.'/ambiguous.md', $am);
}

// ref attach plan: keep_pid => first drop_ref (only if one unique? or first)
$refPlan = [];
foreach ($pairs as $p) {
    $pid = $p['keep_pid'];
    if (!isset($refPlan[$pid])) {
        $refPlan[$pid] = ['ref' => $p['drop_ref'], 'from_drop_pid' => $p['drop_pid'], 'extras' => []];
    } else {
        $refPlan[$pid]['extras'][] = $p['drop_ref'].' from p'.$p['drop_pid'];
    }
}
$rp = "# Ref attach plan (1 ref per IPM product — UNIQUE)\n\n";
$rp .= "Produk IPM multi-varian hanya bisa pegang **satu** ref. Sisanya tetap dimatikan; sync lanjutan lewat SKU.\n\n";
foreach ($refPlan as $pid => $info) {
    $rp .= "- product_id {$pid}: set ref={$info['ref']} (dari PMO p{$info['from_drop_pid']})";
    if ($info['extras']) $rp .= " | unused refs: ".implode('; ', $info['extras']);
    $rp .= "\n";
}
file_put_contents($outDir.'/ref-attach-plan.md', $rp);

echo "Wrote mapping + values\n";
echo "ref_plan products=".count($refPlan)." with extras=".count(array_filter($refPlan, fn($x)=>$x['extras']))."\n";
