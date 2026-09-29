<?php
// Light: supplies_stocks WH2 Sep28 + SAM + PO count only
ini_set('memory_limit', '256M');
$dump = 'c:\\Users\\Ruben\\Downloads\\u906028329_pegasus (3).sql';

function extractInsertRows(string $path, string $table): array
{
    $fh = fopen($path, 'r');
    $collecting = false;
    $buf = '';
    $prefix = "INSERT INTO `{$table}`";
    $allBufs = [];
    while (($line = fgets($fh)) !== false) {
        if (!$collecting) {
            if (str_starts_with($line, $prefix) || str_starts_with(ltrim($line), $prefix)) {
                $collecting = true;
                $buf = $line;
                if (str_ends_with(rtrim($line), ';')) {
                    $allBufs[] = $buf;
                    $collecting = false;
                    $buf = '';
                }
            }
            continue;
        }
        $buf .= $line;
        if (str_ends_with(rtrim($line), ';')) {
            $allBufs[] = $buf;
            $collecting = false;
            $buf = '';
        }
    }
    fclose($fh);
    $rows = [];
    foreach ($allBufs as $buf) {
        if (!preg_match('/INSERT INTO `' . preg_quote($table, '/') . '`\s*\((.*?)\)\s*VALUES\s*(.*);/s', $buf, $m)) continue;
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

$supplies = extractInsertRows($dump, 'supplies');
$byId = [];
foreach ($supplies as $r) $byId[(int)$r['supplies_id']] = trim($r['supplies_name']);

$ss = extractInsertRows($dump, 'supplies_stocks');
echo "=== SAM OIL GEAR stocks ===\n";
foreach ([248,249,252] as $sid) {
    echo "--- $sid {$byId[$sid]} ---\n";
    foreach ($ss as $r) {
        if ((int)$r['supplies_id'] !== $sid) continue;
        echo "  ss={$r['ss_id']} unit={$r['unit_id']} wh={$r['warehouse_id']} stock={$r['ss_stock']} status={$r['status']} upd={$r['updated_at']}\n";
    }
}

echo "\n=== WH2 positive updated Sep 28 ===\n";
foreach ($ss as $r) {
    if ((int)$r['warehouse_id'] !== 2) continue;
    if ((int)$r['ss_stock'] <= 0) continue;
    if (!str_starts_with($r['updated_at'] ?? '', '2026-09-28')) continue;
    $n = $byId[(int)$r['supplies_id']] ?? '?';
    echo "supplies={$r['supplies_id']} [$n] unit={$r['unit_id']} stock={$r['ss_stock']} upd={$r['updated_at']}\n";
}

$pos = extractInsertRows($dump, 'purchase_orders');
echo "\nPO total=" . count($pos) . " (no warehouse_id column in dump)\n";
echo "PO status2=" . count(array_filter($pos, fn($r)=>(int)$r['status']===2)) . "\n";
