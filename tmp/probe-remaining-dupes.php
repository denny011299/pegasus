<?php
$path = 'c:/Users/Ruben/Downloads/u906028329_dev (5).sql';
// Prefer latest if user re-dumped - still use (5) for structure; I'll query pattern from known IDs

function loadInsert($path, $table) {
    $fh = fopen($path, 'r'); $out=''; $cap=false;
    while (($line=fgets($fh))!==false) {
        if (preg_match('/^INSERT INTO `'.preg_quote($table,'/').'`/', $line)) $cap=true;
        if ($cap) { $out.=$line; if (str_ends_with(rtrim($line),';')) $cap=false; }
    }
    fclose($fh); return $out;
}

$productsSql = loadInsert($path, 'products');
$variantsSql = loadInsert($path, 'product_variants');
$stocksSql = loadInsert($path, 'product_stocks');

$products = [];
preg_match_all("/\\((\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*(\\d+),\\s*(\\d+)/", $productsSql, $prows, PREG_SET_ORDER);
foreach ($prows as $r) {
    $products[(int)$r[1]] = ['ref'=>$r[2]==='NULL'?null:$r[2],'name'=>stripcslashes($r[3]),'status'=>(int)$r[9]];
}

$variants = [];
preg_match_all("/\\((\\d+),\\s*(\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*(NULL|'((?:\\\\'|[^'])*)'),\\s*(NULL|\\d+),\\s*(NULL|\\d+),\\s*(NULL|\\d+),\\s*(\\d+),\\s*(\\d+),\\s*(NULL|\\d+),\\s*(NULL|\\d+),\\s*(NULL|\\d+),\\s*(\\d+)/", $variantsSql, $vrows, PREG_SET_ORDER);
foreach ($vrows as $r) {
    $variants[(int)$r[1]] = [
        'product_id'=>(int)$r[2],'name'=>stripcslashes($r[3]),'sku'=>stripcslashes($r[4]),
        'unit_id'=>$r[14]==='NULL'?null:(int)$r[14],'status'=>(int)$r[16],
    ];
}

$stock = [];
preg_match_all('/\((\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*([0-9.]+)/', $stocksSql, $srows, PREG_SET_ORDER);
foreach ($srows as $r) $stock[(int)$r[2]] = ($stock[(int)$r[2]]??0)+(float)$r[6];

$ids = [92,179,114,116,549,709];
foreach ($ids as $id) {
    $v = $variants[$id] ?? null;
    if (!$v) { echo "pv $id NOT IN DUMP (5) — cek live DB\n"; continue; }
    $p = $products[$v['product_id']] ?? null;
    echo sprintf(
        "pv=%d sku=%s name=%s status=%d product=%d (%s) ref=%s prod_status=%s stok=%s\n",
        $id, $v['sku'], $v['name'], $v['status'], $v['product_id'],
        $p['name']??'?', $p['ref']??'NULL', $p['status']??'?', $stock[$id]??0
    );
}
