<?php

$f = 'c:/Users/Ruben/Downloads/u906028329_pegasus (4).sql';
$fh = fopen($f, 'rb');
if (! $fh) {
    fwrite(STDERR, "cannot open\n");
    exit(1);
}

$inInsert = false;
$buf = '';
$trading = [];
$supply = 0;
$other = 0;
$total = 0;

while (($line = fgets($fh)) !== false) {
    if (! $inInsert) {
        if (str_starts_with($line, 'INSERT INTO `supplies`')) {
            $inInsert = true;
            $buf = $line;
            if (str_ends_with(rtrim($line), ';')) {
                // single-line insert
            } else {
                continue;
            }
        } else {
            continue;
        }
    } else {
        $buf .= $line;
        if (! str_ends_with(rtrim($line), ';')) {
            continue;
        }
    }

    // Parse tuples roughly: (...),(...);
    if (! preg_match_all('/\(([^()]*|(?:\([^()]*\))*)\)/', $buf, $m)) {
        // fallback: split by ),(
        $body = $buf;
        $pos = strpos($body, 'VALUES');
        $body = substr($body, $pos + 6);
        $body = trim($body);
        $body = rtrim($body, ";\r\n ");
    }

    // Column order from INSERT header:
    // supplies_id, ref_supplies_id, supplies_name, supplies_kind, trading_product_variant_id, ...
    if (! preg_match('/INSERT INTO `supplies` \(([^)]+)\) VALUES/s', $buf, $hm)) {
        fwrite(STDERR, "no header\n");
        exit(1);
    }
    $cols = array_map(fn ($c) => trim($c, " `"), explode(',', $hm[1]));
    $kindIdx = array_search('supplies_kind', $cols, true);
    $idIdx = array_search('supplies_id', $cols, true);
    $nameIdx = array_search('supplies_name', $cols, true);
    $pvIdx = array_search('trading_product_variant_id', $cols, true);
    $statusIdx = array_search('status', $cols, true);

    $valuesPart = substr($buf, strpos($buf, 'VALUES') + 6);
    $valuesPart = rtrim($valuesPart, ";\r\n \t");

    $rows = splitSqlTuples($valuesPart);
    foreach ($rows as $row) {
        $fields = splitSqlFields($row);
        $total++;
        $kind = trim($fields[$kindIdx] ?? '', " '\"");
        $status = (int) ($fields[$statusIdx] ?? 0);
        if ($status !== 1) {
            continue;
        }
        if ($kind === 'trading') {
            $trading[] = [
                'id' => (int) ($fields[$idIdx] ?? 0),
                'name' => trim($fields[$nameIdx] ?? '', " '\""),
                'pv' => trim($fields[$pvIdx] ?? 'NULL', " '\""),
            ];
        } elseif ($kind === 'supply' || $kind === '') {
            $supply++;
        } else {
            $other++;
            echo "other_kind={$kind} id=".($fields[$idIdx] ?? '?')."\n";
        }
    }

    echo "active_supply={$supply}\n";
    echo 'active_trading='.count($trading)."\n";
    echo "active_other={$other}\n";
    echo "rows_parsed={$total}\n";
    foreach ($trading as $t) {
        echo "#{$t['id']} {$t['name']} pv={$t['pv']}\n";
    }
    exit(0);
}

fwrite(STDERR, "supplies INSERT not found\n");
exit(1);

function splitSqlTuples(string $s): array
{
    $out = [];
    $len = strlen($s);
    $i = 0;
    while ($i < $len) {
        while ($i < $len && ($s[$i] === ' ' || $s[$i] === ',' || $s[$i] === "\n" || $s[$i] === "\r")) {
            $i++;
        }
        if ($i >= $len) {
            break;
        }
        if ($s[$i] !== '(') {
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
                    // mysql '' escape
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
    $i = 0;
    $cur = '';
    $inStr = false;
    $quote = '';
    for (; $i < $len; $i++) {
        $ch = $row[$i];
        if ($inStr) {
            $cur .= $ch;
            if ($ch === '\\') {
                if ($i + 1 < $len) {
                    $cur .= $row[$i + 1];
                    $i++;
                }
                continue;
            }
            if ($ch === $quote) {
                if ($i + 1 < $len && $row[$i + 1] === $quote) {
                    $cur .= $row[$i + 1];
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
