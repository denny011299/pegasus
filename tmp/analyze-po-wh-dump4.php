<?php
$dump = 'c:\\Users\\Ruben\\Downloads\\u906028329_pegasus (3).sql';

function extractInsertRows(string $path, string $table): array
{
    $fh = fopen($path, 'r');
    $collecting = false;
    $buf = '';
    $prefix = "INSERT INTO `{$table}`";
    $all = [];
    while (($line = fgets($fh)) !== false) {
        if (!$collecting) {
            if (str_starts_with($line, $prefix) || str_starts_with(ltrim($line), $prefix)) {
                $collecting = true;
                $buf = $line;
                if (str_ends_with(rtrim($line), ';')) {
                    $all[] = $buf;
                    $collecting = false;
                    $buf = '';
                }
            }
            continue;
        }
        $buf .= $line;
        if (str_ends_with(rtrim($line), ';')) {
            $all[] = $buf;
            $collecting = false;
            $buf = '';
        }
    }
    fclose($fh);

    $rows = [];
    foreach ($all as $buf) {
        if (!preg_match('/INSERT INTO `' . preg_quote($table, '/') . '`\s*\((.*?)\)\s*VALUES\s*(.*);/s', $buf, $m)) {
            continue;
        }
        $cols = array_map(fn ($c) => trim($c, " `\n\r\t"), explode(',', $m[1]));
        $valuesSql = $m[2];
        $len = strlen($valuesSql);
        $i = 0;
        while ($i < $len) {
            while ($i < $len && (ctype_space($valuesSql[$i] ?? ' ') || $valuesSql[$i] === ',')) $i++;
            if ($i >= $len) break;
            if ($valuesSql[$i] !== '(') { $i++; continue; }
            $i++;
            $fields = [];
            $cur = '';
            $inStr = false;
            while ($i < $len) {
                $ch = $valuesSql[$i];
                if ($inStr) {
                    if ($ch === '\\') { $cur .= $ch . ($valuesSql[$i+1] ?? ''); $i += 2; continue; }
                    if ($ch === "'") {
                        if (($valuesSql[$i+1] ?? '') === "'") { $cur .= "'"; $i += 2; continue; }
                        $inStr = false; $i++; continue;
                    }
                    $cur .= $ch; $i++; continue;
                }
                if ($ch === "'") { $inStr = true; $i++; continue; }
                if ($ch === ',') { $fields[] = trim($cur) === 'NULL' ? null : trim($cur); $cur = ''; $i++; continue; }
                if ($ch === ')') { $fields[] = trim($cur) === 'NULL' ? null : trim($cur); $i++; break; }
                $cur .= $ch; $i++;
            }
            if (count($fields) === count($cols)) $rows[] = array_combine($cols, $fields);
        }
    }
    return $rows;
}

foreach (['log_stocks','purchase_orders','purchase_order_deliveries','purchase_order_delivery_details','purchase_orders_details','stock_transfers','stock_transfer_details'] as $t) {
    $rows = extractInsertRows($dump, $t);
    echo "$t: " . count($rows) . " rows\n";
    if (!$rows) continue;
    // date-ish columns
    $dates = [];
    foreach ($rows as $r) {
        foreach (['log_date','created_at','updated_at','po_date','pdo_date','std_date'] as $c) {
            if (!empty($r[$c])) $dates[] = $r[$c];
        }
    }
    if ($dates) {
        sort($dates);
        echo "  min=" . $dates[0] . " max=" . end($dates) . "\n";
    }
}

echo "\n=== POD recent ===\n";
$pods = extractInsertRows($dump, 'purchase_order_deliveries');
usort($pods, fn($a,$b)=>strcmp($b['created_at']??$b['updated_at']??'', $a['created_at']??$a['updated_at']??''));
foreach (array_slice($pods, 0, 15) as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

echo "\n=== stock_transfers to/from WH2 ===\n";
$st = extractInsertRows($dump, 'stock_transfers');
echo "cols: " . ($st ? implode(',', array_keys($st[0])) : 'none') . "\n";
$to2 = 0; $from2 = 0;
foreach ($st as $r) {
    $from = (int)($r['from_warehouse_id'] ?? $r['source_warehouse_id'] ?? $r['warehouse_id'] ?? 0);
    $to = (int)($r['to_warehouse_id'] ?? $r['destination_warehouse_id'] ?? 0);
    if ($to === 2) $to2++;
    if ($from === 2) $from2++;
}
echo "transfers to WH2: $to2 from WH2: $from2 total ST: " . count($st) . "\n";
usort($st, fn($a,$b)=>strcmp($b['created_at']??'', $a['created_at']??''));
foreach (array_slice($st, 0, 10) as $r) {
    echo ($r['created_at']??'?') . ' from=' . ($r['from_warehouse_id']??$r['source_warehouse_id']??$r['warehouse_id']??'?') . ' to=' . ($r['to_warehouse_id']??$r['destination_warehouse_id']??'?') . ' status=' . ($r['status']??'?') . "\n";
}

// Initial WH2 seed: were stocks copied equal to WH1 at create time?
echo "\n=== WH2 rows created 2026-08-31 with stock>0 now: were they seeded? ===\n";
$ss = extractInsertRows($dump, 'supplies_stocks');
$seedPos = 0; $seedZero = 0;
foreach ($ss as $r) {
    if ((int)$r['warehouse_id'] !== 2) continue;
    if (!str_starts_with($r['created_at'] ?? '', '2026-08-31')) continue;
    if ((int)$r['ss_stock'] > 0) $seedPos++; else $seedZero++;
}
echo "created Aug31 WH2: positive_now=$seedPos zero_now=$seedZero\n";

// Name the Sep28 updated supplies
$supplies = extractInsertRows($dump, 'supplies');
$byId = [];
foreach ($supplies as $r) $byId[(int)$r['supplies_id']] = $r['supplies_name'];
echo "\n=== WH2 positive updated Sep28 (names) ===\n";
foreach ($ss as $r) {
    if ((int)$r['warehouse_id'] !== 2) continue;
    if ((int)$r['ss_stock'] <= 0) continue;
    if (!str_starts_with($r['updated_at'] ?? '', '2026-09-28')) continue;
    $name = $byId[(int)$r['supplies_id']] ?? '?';
    echo "supplies={$r['supplies_id']} {$name} unit={$r['unit_id']} stock={$r['ss_stock']} upd={$r['updated_at']}\n";
}
