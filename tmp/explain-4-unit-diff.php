<?php

$path = 'c:/Users/Ruben/Downloads/u906028329_dev (5).sql';
$pmoPath = 'c:/Users/Ruben/Downloads/u906028329_pmo_pegasus.sql';

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

$ids = [
    'aahk400ml' => ['keep' => 15, 'drop' => 604, 'pmo_prod' => 218],
    'azhk400ml' => ['keep' => 19, 'drop' => 619, 'pmo_prod' => 223],
    'hksg13kg' => ['keep' => 95, 'drop' => 439, 'pmo_prod' => 270],
    'hksg160kg' => ['keep' => 96, 'drop' => 440, 'pmo_prod' => 271],
];

$productsSql = loadInsert($path, 'products');
$variantsSql = loadInsert($path, 'product_variants');
$stocksSql = loadInsert($path, 'product_stocks');
$unitsSql = loadInsert($path, 'units');

$units = [];
preg_match_all("/\\((\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+)/", $unitsSql, $urows, PREG_SET_ORDER);
foreach ($urows as $r) {
    $units[(int) $r[1]] = stripcslashes($r[3]).'/'.stripcslashes($r[4]);
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
        'ref' => $r[2] === 'NULL' ? null : $r[2],
        'name' => stripcslashes($r[3]),
        'product_unit_json' => stripcslashes($r[6]),
        'unit_id' => (int) $r[8],
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
        'name' => stripcslashes($r[3]),
        'sku' => stripcslashes($r[4]),
        'unit_id' => $r[14] === 'NULL' ? null : (int) $r[14],
        'status' => (int) $r[16],
    ];
}

foreach ($ids as $key => $pair) {
    $k = $variants[$pair['keep']];
    $d = $variants[$pair['drop']];
    $kp = $products[$k['product_id']];
    $dp = $products[$pair['pmo_prod']];

    echo "======== $key ========\n";
    echo "IPM KEEP pv={$pair['keep']} sku={$k['sku']} name={$k['name']}\n";
    echo "  variant.unit_id={$k['unit_id']} (".($units[$k['unit_id']] ?? '?').")\n";
    echo "  product_id={$k['product_id']} name={$kp['name']} product.unit_id={$kp['unit_id']} (".($units[$kp['unit_id']] ?? '?').") product_unit={$kp['product_unit_json']}\n";

    echo "PMO DROP pv={$pair['drop']} sku={$d['sku']} name={$d['name']}\n";
    echo "  variant.unit_id={$d['unit_id']} (".($units[$d['unit_id']] ?? '?').")\n";
    echo "  product_id={$d['product_id']} name={$dp['name']} ref={$dp['ref']} product.unit_id={$dp['unit_id']} (".($units[$dp['unit_id']] ?? '?').") product_unit={$dp['product_unit_json']}\n";

    // stocks for keep by unit
    preg_match_all('/\((\d+),\s*'.$pair['keep'].',\s*(\d+),\s*(\d+),\s*(\d+),\s*([0-9.]+)/', $stocksSql, $sm, PREG_SET_ORDER);
    echo "Stok KEEP per unit:\n";
    foreach ($sm as $s) {
        $uid = (int) $s[3];
        echo "  wh={$s[4]} unit=$uid (".($units[$uid] ?? '?').") qty={$s[5]}\n";
    }
    if (! $sm) {
        echo "  (tidak ada / semua 0 parse)\n";
    }
    echo "\n";
}

// Peek PMO dump oms_product for these refs if file exists
if (is_file($pmoPath)) {
    $pmo = file_get_contents($pmoPath);
    echo "======== PMO dump oms_product (satuan string) ========\n";
    foreach ($ids as $key => $pair) {
        $ref = $products[$pair['pmo_prod']]['ref'] ?? null;
        if (! $ref) {
            continue;
        }
        if (preg_match("/\\('".$ref."'[^)]{0,400}\\)/", $pmo, $m)) {
            echo "$key ref=$ref => ".substr($m[0], 0, 350)."\n\n";
        } else {
            echo "$key ref=$ref => not found in pmo dump\n";
        }
    }
}
