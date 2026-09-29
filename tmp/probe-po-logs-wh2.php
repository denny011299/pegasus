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

$unquote = function ($v) {
    $v = trim($v);
    if ($v === 'NULL') {
        return null;
    }
    if (strlen($v) >= 2 && $v[0] === "'" && substr($v, -1) === "'") {
        return stripcslashes(substr($v, 1, -1));
    }

    return $v;
};

$since = '2026-09-01';
$poWh2 = [];
$poWh2Recent = [];
foreach (parseInsertTuples($dump, 'log_stocks') as $tuple) {
    $c = splitCsv($tuple);
    if (count($c) < 13) {
        continue;
    }
    $logType = (int) $c[3];
    $wh = (int) $c[12];
    $kode = $unquote($c[2]);
    $date = $unquote($c[1]);
    $notes = (string) $unquote($c[6]);
    if ($logType !== 2 || $wh !== 2) {
        continue;
    }
    $isPo = str_starts_with((string) $kode, 'PO') || stripos($notes, 'Pembelian') !== false;
    if (! $isPo) {
        continue;
    }
    $poWh2[] = [$date, $kode, (int) $c[5], (int) $c[7], (int) $c[9], $notes];
    if ($date >= $since) {
        $poWh2Recent[] = end($poWh2);
    }
}

echo 'PO/pembelian logs on WH2 (all time): '.count($poWh2)."\n";
echo "PO/pembelian logs on WH2 since $since: ".count($poWh2Recent)."\n";
$kodes = [];
foreach ($poWh2Recent as $r) {
    $kodes[$r[1]] = true;
}
echo 'Distinct PO kode recent WH2: '.implode(', ', array_keys($kodes))."\n\n";
foreach (array_slice($poWh2Recent, 0, 40) as $r) {
    echo implode(' | ', $r)."\n";
}
