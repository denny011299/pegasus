<?php
$dump = 'c:/Users/Ruben/Downloads/u906028329_pegasus (3).sql';

function parseInsertTuples(string $dump, string $table): array
{
    $fh = fopen($dump, 'r');
    $in = false;
    $buf = '';
    $rows = [];
    $needle = "INSERT INTO `{$table}`";
    while (($line = fgets($fh)) !== false) {
        if (str_starts_with($line, $needle)) {
            $in = true;
            $buf = $line;
            continue;
        }
        if (! $in) {
            continue;
        }
        $buf .= $line;
        if (! str_contains($line, ';')) {
            continue;
        }
        // Extract parenthesized tuples
        if (preg_match_all('/\(([^()]*(?:\([^()]*\)[^()]*)*)\)/', $buf, $matches)) {
            foreach ($matches[1] as $tuple) {
                $rows[] = $tuple;
            }
        }
        $in = false;
        $buf = '';
    }
    fclose($fh);

    return $rows;
}

function splitCsv(string $tuple): array
{
    $out = [];
    $cur = '';
    $inQ = false;
    $len = strlen($tuple);
    for ($i = 0; $i < $len; $i++) {
        $ch = $tuple[$i];
        if ($ch === "'" && ($i === 0 || $tuple[$i - 1] !== '\\')) {
            // handle doubled quotes ''
            if ($inQ && ($i + 1) < $len && $tuple[$i + 1] === "'") {
                $cur .= "'";
                $i++;
                continue;
            }
            $inQ = ! $inQ;
            $cur .= $ch;
            continue;
        }
        if ($ch === ',' && ! $inQ) {
            $out[] = trim($cur);
            $cur = '';
            continue;
        }
        $cur .= $ch;
    }
    $out[] = trim($cur);

    return $out;
}

$names = [];
foreach (parseInsertTuples($dump, 'supplies') as $tuple) {
    $c = splitCsv($tuple);
    if (count($c) < 3) {
        continue;
    }
    $id = (int) $c[0];
    $name = trim($c[2], "'");
    $name = stripcslashes($name);
    $names[$id] = $name;
}

$stocks = [];
foreach (parseInsertTuples($dump, 'supplies_stocks') as $tuple) {
    $c = splitCsv($tuple);
    if (count($c) < 6) {
        continue;
    }
    $stocks[] = [
        'ss_id' => (int) $c[0],
        'supplies_id' => (int) $c[1],
        'unit_id' => (int) $c[2],
        'warehouse_id' => (int) $c[3],
        'ss_stock' => (int) $c[4],
        'status' => (int) $c[5],
    ];
}

$eceran = array_values(array_filter(
    $stocks,
    fn ($r) => $r['warehouse_id'] === 2 && $r['status'] === 1 && $r['ss_stock'] != 0
));
usort($eceran, fn ($a, $b) => abs($b['ss_stock']) <=> abs($a['ss_stock']));

echo 'eceran nonzero active rows: '.count($eceran)."\n";
echo 'sum abs qty: '.array_sum(array_map(fn ($r) => abs($r['ss_stock']), $eceran))."\n\n";

foreach (array_slice($eceran, 0, 50) as $r) {
    $n = $names[$r['supplies_id']] ?? '?';
    echo sprintf(
        "ss=%d sid=%d unit=%d qty=%d | %s\n",
        $r['ss_id'],
        $r['supplies_id'],
        $r['unit_id'],
        $r['ss_stock'],
        $n
    );
}

echo "\n==== SAM OIL GEAR names ====\n";
foreach ($names as $id => $n) {
    if (stripos($n, 'SAM OIL GEAR') !== false) {
        echo "$id => $n\n";
    }
}

echo "\n==== ALL WH stocks for SAM OIL GEAR ====\n";
foreach ($stocks as $r) {
    $n = $names[$r['supplies_id']] ?? '';
    if ($r['status'] !== 1) {
        continue;
    }
    if (stripos($n, 'SAM OIL GEAR') === false) {
        continue;
    }
    echo sprintf(
        "wh=%d ss=%d sid=%d unit=%d qty=%d | %s\n",
        $r['warehouse_id'],
        $r['ss_id'],
        $r['supplies_id'],
        $r['unit_id'],
        $r['ss_stock'],
        $n
    );
}

$c1 = $c2 = $nz1 = $nz2 = 0;
foreach ($stocks as $r) {
    if ($r['status'] !== 1) {
        continue;
    }
    if ($r['warehouse_id'] === 1) {
        $c1++;
        if ($r['ss_stock'] != 0) {
            $nz1++;
        }
    }
    if ($r['warehouse_id'] === 2) {
        $c2++;
        if ($r['ss_stock'] != 0) {
            $nz2++;
        }
    }
}
echo "\nWH1 active=$c1 nonzero=$nz1\nWH2 active=$c2 nonzero=$nz2\n";

// Also: recent PO ACC log into WH2? log_stocks category pembelian
echo "\n==== Recent log_stocks WH2 bahan (sample) ====\n";
// log_type/category — skip heavy parse; just note eceran stock state
$skipIds = [];
foreach ($names as $id => $n) {
    if (preg_match('/SAM OIL GEAR\s*(140|90)\b/i', $n)) {
        $skipIds[$id] = $n;
    }
}
echo "Exclude IDs:\n";
foreach ($skipIds as $id => $n) {
    echo "  $id => $n\n";
}

$toMove = array_values(array_filter(
    $eceran,
    fn ($r) => ! isset($skipIds[$r['supplies_id']])
));
echo 'Rows to move (eceran→besar, exclude SAM 140/90): '.count($toMove)."\n";
echo 'Rows skipped (SAM 140/90 still on eceran): '.count(array_filter(
    $eceran,
    fn ($r) => isset($skipIds[$r['supplies_id']])
))."\n";
