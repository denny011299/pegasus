<?php
$path = 'c:/Users/Ruben/Downloads/u906028329_dev (5).sql';
$fh = fopen($path, 'r');
$ps = '';
$cap = false;
while (($line = fgets($fh)) !== false) {
    if (preg_match('/^INSERT INTO `product_stocks`/', $line)) {
        $cap = true;
    }
    if ($cap) {
        $ps .= $line;
        if (str_ends_with(rtrim($line), ';')) {
            $cap = false;
        }
    }
}
fclose($fh);

foreach ([33, 217, 603] as $vid) {
    preg_match_all('/\((\d+),\s*'.$vid.',\s*(\d+),\s*(\d+),\s*(\d+),\s*([0-9.]+)/', $ps, $m, PREG_SET_ORDER);
    $sum = [];
    $nonzero = 0;
    foreach ($m as $r) {
        $k = 'wh='.$r[4].' unit='.$r[3];
        $sum[$k] = ($sum[$k] ?? 0) + (float) $r[5];
        if ((float) $r[5] > 0) {
            $nonzero++;
        }
    }
    echo "variant $vid: rows=".count($m)." nonzero=$nonzero\n";
    $any = false;
    foreach ($sum as $k => $q) {
        if ($q != 0) {
            echo "  $k => $q\n";
            $any = true;
        }
    }
    if ($sum && ! $any) {
        echo "  (all zero)\n";
    }
}

// Also check product id 217 stocks if variant filter wrong
preg_match_all('/\((\d+),\s*(\d+),\s*217,\s*(\d+),\s*(\d+),\s*([0-9.]+)/', $ps, $m2, PREG_SET_ORDER);
echo "\nrows with product_id=217: ".count($m2)."\n";
$nz = 0;
foreach ($m2 as $r) {
    if ((float) $r[5] > 0) {
        $nz++;
        echo "  ps={$r[1]} variant={$r[2]} unit={$r[3]} wh={$r[4]} stock={$r[5]}\n";
    }
}
echo "nonzero product 217: $nz\n";
