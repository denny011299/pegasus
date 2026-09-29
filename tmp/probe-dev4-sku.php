<?php
$dump = 'c:/Users/Ruben/Downloads/u906028329_dev (4).sql';
$d = file_get_contents($dump);

function parseInsert(string $d, string $table): ?string {
    if (!preg_match('/INSERT INTO `'.preg_quote($table,'/').'`[^;]+;/s', $d, $m)) return null;
    return $m[0];
}
function parseRows(string $ins): array {
    if (!preg_match('/VALUES\s*(.+);\s*$/s', $ins, $m)) return [];
    $body = $m[1];
    $rows = [];
    $len = strlen($body);
    $i = 0;
    while ($i < $len) {
        while ($i < $len && ($body[$i] === ',' || ctype_space($body[$i]))) $i++;
        if ($i >= $len) break;
        if ($body[$i] !== '(') { $i++; continue; }
        $i++;
        $fields = [];
        $cur = '';
        $inStr = false;
        while ($i < $len) {
            $ch = $body[$i];
            if ($inStr) {
                if ($ch === '\\') { $cur .= $ch.$body[$i+1]; $i += 2; continue; }
                if ($ch === "'") {
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

$prodIns = parseInsert($d, 'products');
$varIns = parseInsert($d, 'product_variants');
preg_match('/INSERT INTO `products` \(([^)]+)\) VALUES/s', $prodIns, $pc);
preg_match('/INSERT INTO `product_variants` \(([^)]+)\) VALUES/s', $varIns, $vc);
$pi = array_flip(array_map(fn($x)=>trim($x," `\n\r\t"), explode(',', $pc[1])));
$vi = array_flip(array_map(fn($x)=>trim($x," `\n\r\t"), explode(',', $vc[1])));

$withRef = 0; $noRef = 0; $maxId = 0;
foreach (parseRows($prodIns) as $f) {
    $id = (int)$f[$pi['product_id']];
    $maxId = max($maxId, $id);
    $ref = unq($f[$pi['ref_product_id']] ?? null);
    if ($ref !== null) $withRef++; else $noRef++;
}
echo "products withRef=$withRef noRef=$noRef maxId=$maxId\n";

$bySku = [];
$active = 0;
foreach (parseRows($varIns) as $f) {
    $st = (int)($f[$vi['status']] ?? 1);
    if ($st !== 1) continue;
    $active++;
    $sku = strtolower(trim(unq($f[$vi['product_variant_sku']] ?? '') ?? ''));
    if ($sku === '') continue;
    $bySku[$sku][] = [
        'vid'=>(int)$f[$vi['product_variant_id']],
        'pid'=>(int)$f[$vi['product_id']],
        'sku'=>unq($f[$vi['product_variant_sku']]),
    ];
}
$dups = array_filter($bySku, fn($x)=>count($x)>1);
echo "active variants=$active dup_skus=".count($dups)."\n";
foreach ($dups as $k=>$list) {
    echo "DUP $k: ".json_encode($list)."\n";
}

// sample products around AIR AKI
foreach (parseRows($prodIns) as $f) {
    $name = unq($f[$pi['product_name']] ?? '');
    if ($name && stripos($name, 'AIR AKI') !== false) {
        echo "P {$f[$pi['product_id']]} ref=".unq($f[$pi['ref_product_id']])." name=$name st={$f[$pi['status']]}\n";
    }
}

// check dump 5
$d5 = 'c:/Users/Ruben/Downloads/u906028329_dev (5).sql';
if (is_file($d5)) {
    echo "DEV5 size=".filesize($d5)."\n";
} else {
    echo "DEV5 missing\n";
    // list downloads
    foreach (glob('c:/Users/Ruben/Downloads/u906028329_dev*.sql') as $f) {
        echo basename($f).' '.filesize($f)."\n";
    }
}
