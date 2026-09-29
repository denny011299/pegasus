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
    $rows = []; $len = strlen($body); $i = 0;
    while ($i < $len) {
        while ($i < $len && ($body[$i] === ',' || ctype_space($body[$i]))) $i++;
        if ($i >= $len) break;
        if ($body[$i] !== '(') { $i++; continue; }
        $i++; $fields = []; $cur = ''; $inStr = false;
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

$products = [];
foreach (parseRows($prodIns) as $f) {
    $id = (int)$f[$pi['product_id']];
    $products[$id] = [
        'ref' => unq($f[$pi['ref_product_id']]),
        'name' => unq($f[$pi['product_name']]),
        'status' => (int)$f[$pi['status']],
    ];
}

echo "=== Variants on product 3 (IPM AIR AKI HIKARI) ===\n";
echo "=== Variants on products 216-219 (PMO) ===\n";
foreach (parseRows($varIns) as $f) {
    $pid = (int)$f[$vi['product_id']];
    $st = (int)$f[$vi['status']];
    if (!in_array($pid, [3,216,217,218,219], true)) continue;
    $vid = (int)$f[$vi['product_variant_id']];
    $sku = unq($f[$vi['product_variant_sku']]);
    $name = unq($f[$vi['product_variant_name']]);
    $unit = (int)$f[$vi['unit_id']];
    $ref = $products[$pid]['ref'] ?? '';
    echo "v$vid p$pid st=$st unit=$unit sku=$sku name=$name pref=$ref\n";
}

// Casefold all SKUs: how many would match across ref vs no-ref?
$locals = []; $pmos = [];
foreach (parseRows($varIns) as $f) {
    if ((int)$f[$vi['status']] !== 1) continue;
    $pid = (int)$f[$vi['product_id']];
    $sku = unq($f[$vi['product_variant_sku']]) ?? '';
    $k = strtolower(trim($sku));
    if ($k === '') continue;
    $vid = (int)$f[$vi['product_variant_id']];
    $hasRef = !empty($products[$pid]['ref']);
    $row = ['vid'=>$vid,'pid'=>$pid,'sku'=>$sku,'pname'=>$products[$pid]['name']];
    if ($hasRef) $pmos[$k][] = $row; else $locals[$k][] = $row;
}

$matches = [];
foreach ($locals as $k => $L) {
    if (!isset($pmos[$k])) continue;
    $matches[$k] = ['local'=>$L, 'pmo'=>$pmos[$k]];
}
echo "\ncasefold local↔pmo matches: ".count($matches)."\n";
foreach (array_slice($matches, 0, 10, true) as $k=>$m) {
    echo "$k local=".json_encode($m['local'])." pmo=".json_encode($m['pmo'])."\n";
}
echo "total local skus=".count($locals)." pmo skus=".count($pmos)."\n";

// Also check DEV5 for comparison
$d5 = file_get_contents('c:/Users/Ruben/Downloads/u906028329_dev (5).sql');
$varIns5 = parseInsert($d5, 'product_variants');
$prodIns5 = parseInsert($d5, 'products');
preg_match('/INSERT INTO `product_variants` \(([^)]+)\) VALUES/s', $varIns5, $vc5);
preg_match('/INSERT INTO `products` \(([^)]+)\) VALUES/s', $prodIns5, $pc5);
$vi5 = array_flip(array_map(fn($x)=>trim($x," `\n\r\t"), explode(',', $vc5[1])));
$pi5 = array_flip(array_map(fn($x)=>trim($x," `\n\r\t"), explode(',', $pc5[1])));
$p5 = [];
foreach (parseRows($prodIns5) as $f) {
    $p5[(int)$f[$pi5['product_id']]] = unq($f[$pi5['ref_product_id']]);
}
$by = [];
foreach (parseRows($varIns5) as $f) {
    if ((int)$f[$vi5['status']] !== 1) continue;
    $k = strtolower(trim(unq($f[$vi5['product_variant_sku']]) ?? ''));
    if ($k==='') continue;
    $by[$k][] = (int)$f[$vi5['product_variant_id']].'@p'.(int)$f[$vi5['product_id']].(empty($p5[(int)$f[$vi5['product_id']]])?'L':'P');
}
$d5d = array_filter($by, fn($x)=>count($x)>1);
echo "\nDEV5 active casefold dup skus: ".count($d5d)."\n";
$i=0;
foreach ($d5d as $k=>$ids) {
    if ($i++>=5) break;
    echo "  $k => ".implode(', ',$ids)."\n";
}
