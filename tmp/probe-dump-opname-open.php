<?php

$f = 'c:/Users/Ruben/Downloads/u906028329_pegasus (4).sql';

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
    if ($buf === '') {
        return [[], []];
    }
    if (! preg_match('/INSERT INTO `'.$table.'` \(([^)]+)\) VALUES/s', $buf, $hm)) {
        return [[], []];
    }
    $cols = array_map(fn ($c) => trim($c, " `"), explode(',', $hm[1]));
    $valuesPart = substr($buf, strpos($buf, 'VALUES') + 6);
    $valuesPart = rtrim($valuesPart, ";\r\n \t");
    $rows = splitSqlTuples($valuesPart);
    $out = [];
    foreach ($rows as $row) {
        $fields = splitSqlFields($row);
        $assoc = [];
        foreach ($cols as $i => $col) {
            $assoc[$col] = trim($fields[$i] ?? '', " '\"");
        }
        $out[] = $assoc;
    }

    return [$cols, $out];
}

function splitSqlTuples(string $s): array
{
    $out = [];
    $len = strlen($s);
    $i = 0;
    while ($i < $len) {
        while ($i < $len && ctype_space($s[$i]) || ($i < $len && $s[$i] === ',')) {
            if ($i < $len && ($s[$i] === ',' || ctype_space($s[$i]))) {
                $i++;
                continue;
            }
            break;
        }
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

[, $produk] = parseInsert($f, 'stock_opnames');
[, $bahan] = parseInsert($f, 'stock_opname_bahans');

$todayCandidates = [];
echo "=== stock_opnames status=1 (open) ===\n";
foreach ($produk as $r) {
    if ((int) ($r['status'] ?? 0) !== 1) {
        continue;
    }
    echo "{$r['sto_code']} date={$r['sto_date']} wh={$r['warehouse_id']} draft={$r['is_draft']} staff={$r['sto_staff_name']}\n";
}
echo "=== stock_opname_bahans status=1 (open) ===\n";
foreach ($bahan as $r) {
    if ((int) ($r['status'] ?? 0) !== 1) {
        continue;
    }
    echo "{$r['stob_code']} date={$r['stob_date']} wh={$r['warehouse_id']} draft={$r['is_draft']} staff={$r['stob_staff_name']}\n";
}
