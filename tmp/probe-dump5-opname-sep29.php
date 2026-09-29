<?php

$f = 'c:/Users/Ruben/Downloads/u906028329_pegasus (5).sql';

function splitSqlTuples(string $s): array
{
    $out = [];
    $len = strlen($s);
    $i = 0;
    while ($i < $len) {
        while ($i < $len && (ctype_space($s[$i]) || $s[$i] === ',')) {
            $i++;
        }
        if ($i >= $len || $s[$i] !== '(') {
            break;
        }
        $i++;
        $start = $i;
        $depth = 1;
        $inStr = false;
        $quote = '';
        for (; $i < $len; $i++) {
            $ch = $s[$i];
            if ($inStr) {
                if ($ch === '\\') {
                    $i++;
                    continue;
                }
                if ($ch === $quote) {
                    if ($i + 1 < $len && $s[$i + 1] === $quote) {
                        $i++;
                        continue;
                    }
                    $inStr = false;
                }
                continue;
            }
            if ($ch === "'" || $ch === '"') {
                $inStr = true;
                $quote = $ch;
                continue;
            }
            if ($ch === '(') {
                $depth++;
            } elseif ($ch === ')') {
                $depth--;
                if ($depth === 0) {
                    $out[] = substr($s, $start, $i - $start);
                    $i++;
                    break;
                }
            }
        }
    }

    return $out;
}

function splitSqlFields(string $row): array
{
    $fields = [];
    $len = strlen($row);
    $cur = '';
    $inStr = false;
    $quote = '';
    for ($i = 0; $i < $len; $i++) {
        $ch = $row[$i];
        if ($inStr) {
            $cur .= $ch;
            if ($ch === '\\') {
                if ($i + 1 < $len) {
                    $cur .= $row[++$i];
                }
                continue;
            }
            if ($ch === $quote) {
                if ($i + 1 < $len && $row[$i + 1] === $quote) {
                    $cur .= $row[++$i];
                    continue;
                }
                $inStr = false;
            }
            continue;
        }
        if ($ch === "'" || $ch === '"') {
            $inStr = true;
            $quote = $ch;
            $cur .= $ch;
            continue;
        }
        if ($ch === ',') {
            $fields[] = trim($cur);
            $cur = '';
            continue;
        }
        $cur .= $ch;
    }
    $fields[] = trim($cur);

    return $fields;
}

function parseInsert(string $f, string $table): array
{
    $fh = fopen($f, 'rb');
    $needle = "INSERT INTO `{$table}`";
    $buf = '';
    $in = false;
    while (($line = fgets($fh)) !== false) {
        if (! $in) {
            if (! str_starts_with($line, $needle)) {
                continue;
            }
            $in = true;
            $buf = $line;
        } else {
            $buf .= $line;
        }
        if (str_ends_with(rtrim($line), ';')) {
            break;
        }
    }
    fclose($fh);
    if ($buf === '' || ! preg_match('/INSERT INTO `'.$table.'` \(([^)]+)\) VALUES/s', $buf, $hm)) {
        return [];
    }
    $cols = array_map(fn ($c) => trim($c, " `"), explode(',', $hm[1]));
    $valuesPart = substr($buf, strpos($buf, 'VALUES') + 6);
    $valuesPart = rtrim($valuesPart, ";\r\n \t");
    $out = [];
    foreach (splitSqlTuples($valuesPart) as $row) {
        $fields = splitSqlFields($row);
        $assoc = [];
        foreach ($cols as $i => $col) {
            $assoc[$col] = trim($fields[$i] ?? '', " '\"");
        }
        $out[] = $assoc;
    }

    return $out;
}

$rows = parseInsert($f, 'stock_opnames');
$sep29 = array_values(array_filter($rows, fn ($r) => str_starts_with((string) ($r['sto_date'] ?? ''), '2026-09-29')));

echo "total_stock_opnames=".count($rows)."\n";
echo "on_2026-09-29=".count($sep29)."\n\n";

usort($sep29, function ($a, $b) {
    $sa = (int) ($a['status'] ?? 0);
    $sb = (int) ($b['status'] ?? 0);
    if ($sa !== $sb) {
        return $sa <=> $sb;
    }

    return strcmp((string) ($a['sto_code'] ?? ''), (string) ($b['sto_code'] ?? ''));
});

echo "=== 29 Sep (sorted like UI intent: status asc, then code) ===\n";
foreach ($sep29 as $r) {
    echo sprintf(
        "%s id=%s status=%s draft=%s wh=%s created_at=%s updated_at=%s staff=%s created_by=%s\n",
        $r['sto_code'] ?? '?',
        $r['sto_id'] ?? '?',
        $r['status'] ?? '?',
        $r['is_draft'] ?? '?',
        $r['warehouse_id'] ?? '?',
        $r['created_at'] ?? '?',
        $r['updated_at'] ?? '?',
        $r['sto_staff_name'] ?? ($r['staff_id'] ?? '?'),
        $r['created_by'] ?? '?'
    );
}

echo "\n=== list query simulation: status>=1, wh=1, order status asc, sto_date desc ===\n";
$list = array_values(array_filter($rows, function ($r) {
    return (int) ($r['status'] ?? 0) >= 1
        && (int) ($r['warehouse_id'] ?? 0) === 1;
}));
usort($list, function ($a, $b) {
    $sa = (int) ($a['status'] ?? 0);
    $sb = (int) ($b['status'] ?? 0);
    if ($sa !== $sb) {
        return $sa <=> $sb;
    }
    $da = (string) ($a['sto_date'] ?? '');
    $db = (string) ($b['sto_date'] ?? '');
    if ($da !== $db) {
        return strcmp($db, $da); // desc
    }

    // MySQL without secondary key: typically primary key / insert order (sto_id asc)
    return ((int) ($a['sto_id'] ?? 0)) <=> ((int) ($b['sto_id'] ?? 0));
});

foreach (array_slice($list, 0, 15) as $r) {
    echo sprintf(
        "%s date=%s status=%s draft=%s sto_id=%s created_at=%s\n",
        $r['sto_code'],
        $r['sto_date'],
        $r['status'],
        $r['is_draft'],
        $r['sto_id'],
        $r['created_at']
    );
}
