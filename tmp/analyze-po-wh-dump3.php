<?php
$dump = 'c:\\Users\\Ruben\\Downloads\\u906028329_pegasus (3).sql';

function extractInsertRows(string $path, string $table): array
{
    $fh = fopen($path, 'r');
    $collecting = false;
    $buf = '';
    $prefix = "INSERT INTO `{$table}`";
    while (($line = fgets($fh)) !== false) {
        if (!$collecting) {
            if (str_starts_with($line, $prefix) || str_starts_with(ltrim($line), $prefix)) {
                $collecting = true;
                $buf = $line;
                if (str_ends_with(rtrim($line), ';')) break;
            }
            continue;
        }
        $buf .= $line;
        if (str_ends_with(rtrim($line), ';')) break;
    }
    fclose($fh);
    if ($buf === '' || !preg_match('/INSERT INTO `' . preg_quote($table, '/') . '`\s*\((.*?)\)\s*VALUES\s*(.*);/s', $buf, $m)) {
        return [];
    }
    $cols = array_map(fn ($c) => trim($c, " `\n\r\t"), explode(',', $m[1]));
    $valuesSql = $m[2];
    $rows = [];
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
    return $rows;
}

$pos = extractInsertRows($dump, 'purchase_orders');
usort($pos, fn($a,$b) => strcmp($b['updated_at']??'', $a['updated_at']??''));
echo "=== Latest 30 POs by updated_at ===\n";
foreach (array_slice($pos, 0, 30) as $r) {
    echo "{$r['updated_at']} {$r['po_number']} date={$r['po_date']} status={$r['status']} tt={$r['tt_id']} acc={$r['acc_by']}\n";
}

usort($pos, fn($a,$b) => strcmp($b['po_date']??'', $a['po_date']??''));
echo "\n=== Latest 20 POs by po_date ===\n";
foreach (array_slice($pos, 0, 20) as $r) {
    echo "{$r['po_date']} {$r['po_number']} status={$r['status']} upd={$r['updated_at']}\n";
}

$logs = extractInsertRows($dump, 'log_stocks');
usort($logs, fn($a,$b) => strcmp($b['log_date']??'', $a['log_date']??''));
echo "\n=== Latest 40 log_stocks ===\n";
foreach (array_slice($logs, 0, 40) as $r) {
    echo "{$r['log_date']} {$r['log_kode']} type={$r['log_type']} cat={$r['log_category']} item={$r['log_item_id']} qty={$r['log_jumlah']} wh={$r['warehouse_id']} | {$r['log_notes']}\n";
}

// WH2 stock created dates
$ss = extractInsertRows($dump, 'supplies_stocks');
$wh2 = array_filter($ss, fn($r)=>(int)$r['warehouse_id']===2);
$created = [];
foreach ($wh2 as $r) {
    $d = substr($r['created_at'] ?? '', 0, 10);
    $created[$d] = ($created[$d] ?? 0) + 1;
}
ksort($created);
echo "\n=== WH2 supplies_stocks created_at distribution ===\n";
foreach ($created as $d=>$c) echo "$d => $c\n";

$upd = [];
foreach ($wh2 as $r) {
    if ((int)$r['ss_stock'] <= 0) continue;
    $d = substr($r['updated_at'] ?? '', 0, 10);
    $upd[$d] = ($upd[$d] ?? 0) + 1;
}
ksort($upd);
echo "\n=== WH2 positive stock updated_at distribution ===\n";
foreach ($upd as $d=>$c) echo "$d => $c\n";

echo "\n=== WH2 positive stock updated recently (Sep 20+) ===\n";
foreach ($wh2 as $r) {
    if ((int)$r['ss_stock'] <= 0) continue;
    if (($r['updated_at'] ?? '') < '2026-09-20') continue;
    echo "ss={$r['ss_id']} supplies={$r['supplies_id']} unit={$r['unit_id']} stock={$r['ss_stock']} upd={$r['updated_at']} created={$r['created_at']}\n";
}

// Compare WH1 vs WH2 for same supplies that have positive on both
echo "\n=== Positive on BOTH WH1 and WH2 ===\n";
$idx = [];
foreach ($ss as $r) {
    if ((int)$r['status'] !== 1) continue;
    $k = $r['supplies_id'].'|'.$r['unit_id'];
    $idx[$k][(int)$r['warehouse_id']] = $r;
}
$both = 0;
foreach ($idx as $k => $byWh) {
    $s1 = (int)($byWh[1]['ss_stock'] ?? 0);
    $s2 = (int)($byWh[2]['ss_stock'] ?? 0);
    if ($s1 > 0 && $s2 > 0) {
        $both++;
        if ($both <= 40) {
            echo "$k wh1=$s1 wh2=$s2\n";
        }
    }
}
echo "total both-positive: $both\n";

// Check product_stocks similarly for WH2 positive
echo "\n=== product_stocks WH2 positive (if table exists) ===\n";
$ps = extractInsertRows($dump, 'product_stocks');
echo "product_stocks rows: " . count($ps) . "\n";
if ($ps) {
    $p2 = array_filter($ps, fn($r)=>(int)($r['warehouse_id']??0)===2 && (int)($r['ps_stock']??$r['stock']??0)>0);
    echo "WH2 positive: " . count($p2) . "\n";
    $sample = array_slice($p2, 0, 15);
    foreach ($sample as $r) {
        $stock = $r['ps_stock'] ?? $r['stock'] ?? '?';
        echo "id=".($r['ps_id']??'?')." pv=".($r['product_variant_id']??'?')." unit=".($r['unit_id']??'?')." stock=$stock upd=".($r['updated_at']??'?')."\n";
    }
}
