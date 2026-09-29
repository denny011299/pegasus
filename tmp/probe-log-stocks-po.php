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

// log_stocks columns from CREATE
$createPos = null;
$fh = fopen($dump, 'r');
$ln = 0;
while (($line = fgets($fh)) !== false) {
    $ln++;
    if (str_contains($line, 'CREATE TABLE `log_stocks`')) {
        $createPos = $ln;
        break;
    }
}
rewind($fh);
for ($i = 0; $i < $createPos - 1; $i++) {
    fgets($fh);
}
$cols = [];
for ($i = 0; $i < 40; $i++) {
    $line = fgets($fh);
    echo $line;
    if (str_starts_with(trim($line), ')')) {
        break;
    }
}
fclose($fh);

echo "\n==== WH2 log since 2026-09-01 with PO kode ====\n";
$cut = '2026-09-01';
$count = 0;
$byKode = [];
foreach (parseInsertTuples($dump, 'log_stocks') as $tuple) {
    $c = splitCsv($tuple);
    // guess: log_id, log_date, log_kode, log_type, log_category, log_item_id, ..., warehouse_id
    // print first few field counts
    if ($count === 0) {
        echo 'fields='.count($c)." sample: ".implode(' | ', array_slice($c, 0, 12))." ... last=".end($c)."\n";
    }
    $count++;
}
echo "total log rows parsed: $count\n";
