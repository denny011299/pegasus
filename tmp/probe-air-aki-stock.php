<?php

$path = 'c:/Users/Ruben/Downloads/u906028329_dev (5).sql';

function extractInsert(string $path, string $table): string
{
    $out = '';
    $fh = fopen($path, 'r');
    $capturing = false;
    while (($line = fgets($fh)) !== false) {
        if (preg_match('/^INSERT INTO `'.preg_quote($table, '/').'`/', $line)) {
            $capturing = true;
        }
        if ($capturing) {
            $out .= $line;
            if (str_ends_with(rtrim($line), ';')) {
                $capturing = false;
            }
        }
    }
    fclose($fh);

    return $out;
}

function extractCreate(string $path, string $table): string
{
    $fh = fopen($path, 'r');
    $buf = '';
    $capturing = false;
    while (($line = fgets($fh)) !== false) {
        if (preg_match('/^CREATE TABLE `'.preg_quote($table, '/').'`/', $line)) {
            $capturing = true;
        }
        if ($capturing) {
            $buf .= $line;
            if (str_contains($line, ';')) {
                break;
            }
        }
    }
    fclose($fh);

    return $buf;
}

$variantId = 603;
$unitIds = [7, 9, 126, 127];

echo "=== product_variants CREATE ===\n";
echo extractCreate($path, 'product_variants')."\n";

echo "=== product_stocks CREATE ===\n";
echo extractCreate($path, 'product_stocks')."\n";

$ps = extractInsert($path, 'product_stocks');
// Rows mentioning ,603, as product_variant_id - format typically:
// (ps_id, product_variant_id, product_id, unit_id, warehouse_id, ps_stock, ...)
preg_match_all('/\((\d+),\s*'.$variantId.',\s*(\d+),\s*(\d+),\s*(\d+),\s*([0-9.]+)/', $ps, $m, PREG_SET_ORDER);
echo "=== product_stocks for variant $variantId ===\n";
if ($m === []) {
    echo "(none found with pattern ps_id,variant,product,unit,wh,stock)\n";
    // fallback: any tuple containing , 603,
    preg_match_all('/\([^\)]*\b603\b[^\)]*\)/', $ps, $m2);
    echo "loose hits: ".count($m2[0])."\n";
    foreach (array_slice($m2[0], 0, 20) as $h) {
        echo substr($h, 0, 180)."\n";
    }
} else {
    $sum = [];
    foreach ($m as $r) {
        $unit = (int) $r[3];
        $wh = (int) $r[4];
        $qty = (float) $r[5];
        $key = "wh=$wh unit=$unit";
        $sum[$key] = ($sum[$key] ?? 0) + $qty;
        echo "ps_id={$r[1]} product={$r[2]} unit=$unit wh=$wh stock=$qty\n";
    }
    echo "--- sums ---\n";
    foreach ($sum as $k => $q) {
        echo "$k => $q\n";
    }
}

// SO 937 details
$sod = extractInsert($path, 'sales_order_details');
preg_match_all('/\((\d+),\s*937,\s*(\d+),\s*(\d+),\s*(\d+),/', $sod, $soRows, PREG_SET_ORDER);
echo "\n=== sales_order_details so_id=937 (pattern) ===\n";
echo "hits: ".count($soRows)."\n";
foreach ($soRows as $r) {
    echo "sod={$r[1]} variant={$r[2]} unit={$r[3]} wh={$r[4]}\n";
}

// Better parse line 153458 style known
if (preg_match_all('/\((\d+),\s*937,[^;]+?\)/', $sod, $all937)) {
    echo "\n=== raw so 937 tuples (trunc) ===\n";
    foreach ($all937[0] as $t) {
        if (stripos($t, 'AKI') !== false || str_contains($t, ', 603,')) {
            echo substr($t, 0, 250)."\n";
        }
    }
}

// product_relations for variant 603
$pr = extractInsert($path, 'product_relations');
preg_match_all('/\([^\)]*\b603\b[^\)]*\)/', $pr, $prHits);
echo "\n=== product_relations mentioning 603 ===\n";
foreach ($prHits[0] as $h) {
    echo $h."\n";
}

// warehouses
$wh = extractInsert($path, 'warehouses');
echo "\n=== warehouses (first 800 chars) ===\n";
echo substr($wh, 0, 800)."\n";
