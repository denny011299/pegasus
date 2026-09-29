<?php
$p = 'c:/Users/Ruben/Downloads/u906028329_dev (4).sql';
echo 'size=' . filesize($p) . PHP_EOL;
$d = file_get_contents($p);
if (!preg_match('/INSERT INTO `units`[^;]+;/s', $d, $m)) {
    fwrite(STDERR, "units INSERT not found\n");
    exit(1);
}
preg_match_all(
    "/\\((\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+)/",
    $m[0],
    $rows,
    PREG_SET_ORDER
);
foreach ([7, 9, 126, 127] as $id) {
    foreach ($rows as $r) {
        if ((int) $r[1] === $id) {
            echo "unit {$id} name={$r[3]} code={$r[4]} status={$r[5]} ref={$r[2]}\n";
        }
    }
}
// stock still on DOS/Piece?
foreach (['product_variant_stock', 'product_variant_price'] as $t) {
    if (!preg_match("/INSERT INTO `{$t}`[^;]+;/s", $d, $ins)) {
        echo "{$t}: no INSERT\n";
        continue;
    }
    // rough: count unit_id 7 and 9
    $c7 = preg_match_all('/,7,|,7\)/', $ins[0]);
    // better parse later
    echo "{$t} blob len=" . strlen($ins[0]) . "\n";
}
echo "OK\n";
