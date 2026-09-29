<?php

$path = 'c:/Users/Ruben/Downloads/u906028329_dev (5).sql';
$csv = dirname(__DIR__).'/database/scripts/sync-sku-duplicates/35-pasangan-PMO-vs-IPM.csv';

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
$unitsSql = loadInsert($path, 'units');

$units = [];
preg_match_all(
    "/\\((\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+)/",
    $unitsSql,
    $urows,
    PREG_SET_ORDER
);
foreach ($urows as $r) {
    $units[(int) $r[1]] = stripcslashes($r[3]).' ('.$r[5].')';
}

$products = [];
preg_match_all(
    "/\\((\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*(\\d+),\\s*(\\d+)/",
    $productsSql,
    $prows,
    PREG_SET_ORDER
);
foreach ($prows as $r) {
    $products[(int) $r[1]] = [
        'name' => stripcslashes($r[3]),
        'unit_id' => (int) $r[8], // default unit on product
        'ref' => $r[2] === 'NULL' ? null : (int) $r[2],
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
    $variants[(int) $r[1]] = [
        'product_id' => (int) $r[2],
        'sku' => stripcslashes($r[4]),
        'unit_id' => $r[14] === 'NULL' ? null : (int) $r[14],
        'retail_unit' => $r[8] === 'NULL' ? null : (int) $r[8],
        'status' => (int) $r[16],
    ];
}

// Read pairs from CSV (semicolon)
$pairs = [];
$fh = fopen($csv, 'r');
fgetcsv($fh, 0, ';'); // header
while (($row = fgetcsv($fh, 0, ';')) !== false) {
    if (count($row) < 11) {
        continue;
    }
    $pairs[] = [
        'no' => $row[0],
        'sku_key' => $row[1],
        'keep' => (int) $row[2],
        'drop' => (int) $row[5],
        'pmo_product' => (int) $row[9],
    ];
}
fclose($fh);

$sameVariantUnit = 0;
$diffVariantUnit = 0;
$sameProductDefault = 0;
$diffProductDefault = 0;
$diffLines = [];

echo "No | SKU | KEEP var.unit | DROP var.unit | KEEP prod.default | PMO prod.default | var_unit? | prod_default?\n";
echo str_repeat('-', 120)."\n";

foreach ($pairs as $p) {
    $k = $variants[$p['keep']] ?? null;
    $d = $variants[$p['drop']] ?? null;
    if (! $k || ! $d) {
        echo "MISSING {$p['sku_key']}\n";
        continue;
    }
    $kp = $products[$k['product_id']] ?? null;
    $dp = $products[$p['pmo_product']] ?? ($products[$d['product_id']] ?? null);

    $ku = $k['unit_id'];
    $du = $d['unit_id'];
    $kpd = $kp['unit_id'] ?? null;
    $dpd = $dp['unit_id'] ?? null;

    $varOk = ($ku === $du);
    $prodOk = ($kpd === $dpd);
    if ($varOk) {
        $sameVariantUnit++;
    } else {
        $diffVariantUnit++;
    }
    if ($prodOk) {
        $sameProductDefault++;
    } else {
        $diffProductDefault++;
    }

    $ulabel = function ($id) use ($units) {
        if ($id === null) {
            return 'NULL';
        }

        return $id.':'.($units[$id] ?? '?');
    };

    $flagV = $varOk ? 'SAMA' : 'BEDA';
    $flagP = $prodOk ? 'SAMA' : 'BEDA';
    $line = sprintf(
        "%s | %s | %s | %s | %s | %s | %s | %s",
        $p['no'],
        $p['sku_key'],
        $ulabel($ku),
        $ulabel($du),
        $ulabel($kpd),
        $ulabel($dpd),
        $flagV,
        $flagP
    );
    echo $line."\n";
    if (! $varOk || ! $prodOk) {
        $diffLines[] = $line;
    }
}

echo "\n=== RINGKAS ===\n";
echo "variant unit_id SAMA: $sameVariantUnit / ".count($pairs)."\n";
echo "variant unit_id BEDA: $diffVariantUnit\n";
echo "product default unit SAMA: $sameProductDefault / ".count($pairs)."\n";
echo "product default unit BEDA: $diffProductDefault\n";

$out = dirname(__DIR__).'/database/scripts/sync-sku-duplicates/unit-compare-keep-vs-drop.md';
$md = ["# Banding unit KEEP (IPM) vs DROP (PMO)", "", "Sumber: DEV dump (5) + 35 pasangan CSV", "", "## Ringkas", "", "- Varian `unit_id` sama: **$sameVariantUnit**/".count($pairs), "- Varian `unit_id` beda: **$diffVariantUnit**", "- Product default `unit_id` sama: **$sameProductDefault**/".count($pairs), "- Product default `unit_id` beda: **$diffProductDefault**", ""];
if ($diffLines) {
    $md[] = '## Yang BEDA';
    $md[] = '';
    $md[] = '```';
    $md = array_merge($md, $diffLines);
    $md[] = '```';
}
file_put_contents($out, implode("\n", $md));
echo "Wrote $out\n";
