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
                if (str_ends_with(rtrim($line), ';')) {
                    break;
                }
            }
            continue;
        }
        $buf .= $line;
        if (str_ends_with(rtrim($line), ';')) {
            break;
        }
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
        while ($i < $len && ctype_space($valuesSql[$i] ?? ' ') || ($i < $len && $valuesSql[$i] === ',')) {
            $i++;
        }
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
        if (count($fields) === count($cols)) {
            $rows[] = array_combine($cols, $fields);
        }
    }
    return $rows;
}

$samExclude = [248, 249]; // GEAR 90 20x1L, GEAR 140 20x1L — client already fixed
$samRelated = [248, 249, 252]; // also show 252 GEAR 140 18L

echo "=== SAM OIL stocks ===\n";
$ss = extractInsertRows($dump, 'supplies_stocks');
foreach ($ss as $r) {
    $sid = (int)$r['supplies_id'];
    if (in_array($sid, $samRelated, true)) {
        echo "ss_id={$r['ss_id']} supplies={$sid} unit={$r['unit_id']} wh={$r['warehouse_id']} stock={$r['ss_stock']} status={$r['status']} upd={$r['updated_at']}\n";
    }
}

echo "\n=== supplies_stocks on warehouse 2 (eceran) — top ===\n";
$eceran = array_filter($ss, fn($r) => (int)$r['warehouse_id'] === 2 && (int)$r['status'] === 1);
usort($eceran, fn($a,$b) => (int)$b['ss_stock'] <=> (int)$a['ss_stock']);
echo "count active eceran rows: " . count($eceran) . "\n";
foreach (array_slice($eceran, 0, 40) as $r) {
    echo "ss={$r['ss_id']} supplies={$r['supplies_id']} unit={$r['unit_id']} stock={$r['ss_stock']}\n";
}

echo "\n=== purchase_orders stats ===\n";
$pos = extractInsertRows($dump, 'purchase_orders');
echo "total PO rows: " . count($pos) . "\n";
$byStatus = [];
$sep28 = [];
foreach ($pos as $r) {
    $st = (string)$r['status'];
    $byStatus[$st] = ($byStatus[$st] ?? 0) + 1;
    $d = $r['po_date'] ?? '';
    $u = $r['updated_at'] ?? '';
    if (str_starts_with($d, '2026-09-2') || str_starts_with($u, '2026-09-2')) {
        if (str_contains($d, '2026-09-2') || str_contains($u, '2026-09-2')) {
            // keep Sep 26-29
            if (preg_match('/2026-09-2[6-9]/', $d.$u)) {
                $sep28[] = $r;
            }
        }
    }
}
ksort($byStatus);
echo "by status: ";
foreach ($byStatus as $k=>$v) echo "$k=>$v ";
echo "\n";
echo "POs touching Sep 26-29 (date or updated): " . count($sep28) . "\n";
foreach ($sep28 as $r) {
    echo "po_id={$r['po_id']} num={$r['po_number']} date={$r['po_date']} status={$r['status']} tt={$r['tt_id']} upd={$r['updated_at']} acc={$r['acc_by']}\n";
}

echo "\n=== log_stocks pembelian analysis ===\n";
$logs = extractInsertRows($dump, 'log_stocks');
echo "total logs: " . count($logs) . "\n";
$pembelian = [];
$wrongWh = [];
$productPembelianWrong = [];
foreach ($logs as $r) {
    $notes = (string)($r['log_notes'] ?? '');
    $kode = (string)($r['log_kode'] ?? '');
    $isPembelian = stripos($notes, 'Pembelian') !== false || preg_match('/^PO\d+/i', $kode);
    if (!$isPembelian) continue;
    $pembelian[] = $r;
    $wh = (int)($r['warehouse_id'] ?? 0);
    if ($wh !== 1 && $wh !== 0) {
        $item = (int)$r['log_item_id'];
        $type = (int)$r['log_type']; // 2=supplies
        $row = $r + ['_item'=>$item, '_type'=>$type];
        if ($type === 2 && in_array($item, $samExclude, true)) {
            $row['_sam_exclude'] = 1;
        }
        $wrongWh[] = $row;
        if ($type === 1) $productPembelianWrong[] = $r;
    }
}
echo "pembelian-like logs: " . count($pembelian) . "\n";
echo "pembelian logs NOT on WH1: " . count($wrongWh) . "\n";
echo "of which product (type1): " . count($productPembelianWrong) . "\n";

$byWh = [];
foreach ($wrongWh as $r) {
    $byWh[(string)$r['warehouse_id']] = ($byWh[(string)$r['warehouse_id']] ?? 0) + 1;
}
echo "wrong WH breakdown: ";
foreach ($byWh as $k=>$v) echo "wh$k=>$v ";
echo "\n";

// Sep 26-29 wrong WH
$recentWrong = array_filter($wrongWh, fn($r) => preg_match('/2026-09-2[6-9]/', ($r['log_date']??'').($r['created_at']??'')));
echo "wrong WH logs Sep26-29: " . count($recentWrong) . "\n";

$samWrong = array_filter($wrongWh, fn($r) => !empty($r['_sam_exclude']));
$nonSamWrong = array_filter($wrongWh, fn($r) => empty($r['_sam_exclude']));
echo "wrong WH SAM exclude (248/249): " . count($samWrong) . "\n";
echo "wrong WH to fix (excl SAM 248/249): " . count($nonSamWrong) . "\n";

echo "\n--- Recent wrong-WH pembelian (last 50 by date) ---\n";
usort($wrongWh, fn($a,$b) => strcmp($b['log_date']??'', $a['log_date']??''));
foreach (array_slice($wrongWh, 0, 50) as $r) {
    $flag = !empty($r['_sam_exclude']) ? ' [SAM-EXCLUDE]' : '';
    echo "{$r['log_date']} {$r['log_kode']} type={$r['log_type']} item={$r['log_item_id']} qty={$r['log_jumlah']} wh={$r['warehouse_id']} unit={$r['unit_id']}{$flag} | {$r['log_notes']}\n";
}

// Aggregate qty to move from WH2->WH1 for supplies (type2), excl SAM
echo "\n=== Aggregated supplies qty to move WH2->WH1 (excl 248,249) ===\n";
$agg = [];
foreach ($nonSamWrong as $r) {
    if ((int)$r['log_type'] !== 2) continue;
    if ((int)$r['warehouse_id'] !== 2) continue;
    if ((int)$r['log_category'] !== 1) continue; // masuk only
    $key = $r['log_item_id'] . '|' . $r['unit_id'];
    if (!isset($agg[$key])) $agg[$key] = ['supplies_id'=>(int)$r['log_item_id'],'unit_id'=>(int)$r['unit_id'],'qty'=>0,'logs'=>0,'kodes'=>[]];
    $agg[$key]['qty'] += (int)$r['log_jumlah'];
    $agg[$key]['logs']++;
    $agg[$key]['kodes'][$r['log_kode']] = true;
}
uasort($agg, fn($a,$b) => $b['qty'] <=> $a['qty']);
echo "distinct supplies/unit: " . count($agg) . "\n";
foreach ($agg as $a) {
    $kodes = implode(',', array_keys($a['kodes']));
    echo "supplies={$a['supplies_id']} unit={$a['unit_id']} qty={$a['qty']} logs={$a['logs']} POs={$kodes}\n";
}

// Also check product_stocks misplacement for pembelian
echo "\n=== Product pembelian wrong WH ===\n";
$aggP = [];
foreach ($nonSamWrong as $r) {
    if ((int)$r['log_type'] !== 1) continue;
    if ((int)$r['warehouse_id'] !== 2) continue;
    if ((int)$r['log_category'] !== 1) continue;
    $key = $r['log_item_id'] . '|' . $r['unit_id'];
    if (!isset($aggP[$key])) $aggP[$key] = ['item'=>(int)$r['log_item_id'],'unit'=>(int)$r['unit_id'],'qty'=>0,'logs'=>0];
    $aggP[$key]['qty'] += (int)$r['log_jumlah'];
    $aggP[$key]['logs']++;
}
echo "distinct product/unit: " . count($aggP) . "\n";
foreach ($aggP as $a) {
    echo "variant={$a['item']} unit={$a['unit']} qty={$a['qty']} logs={$a['logs']}\n";
}

// Current eceran stock for aggregated supplies
echo "\n=== Current WH2 stock vs qty to move (supplies) ===\n";
$ssIdx = [];
foreach ($ss as $r) {
    $ssIdx[$r['supplies_id'].'|'.$r['unit_id'].'|'.$r['warehouse_id']] = $r;
}
foreach ($agg as $a) {
    $k2 = $a['supplies_id'].'|'.$a['unit_id'].'|2';
    $k1 = $a['supplies_id'].'|'.$a['unit_id'].'|1';
    $s2 = $ssIdx[$k2]['ss_stock'] ?? 'MISSING';
    $s1 = $ssIdx[$k1]['ss_stock'] ?? 'MISSING';
    $ok = is_numeric($s2) && (int)$s2 >= $a['qty'] ? 'OK' : 'SHORT?';
    echo "sup={$a['supplies_id']} unit={$a['unit_id']} move={$a['qty']} wh2={$s2} wh1={$s1} {$ok}\n";
}

// SAM exclude current stocks detail + their wrong logs
echo "\n=== SAM exclude wrong logs detail ===\n";
foreach ($samWrong as $r) {
    echo "{$r['log_date']} {$r['log_kode']} item={$r['log_item_id']} qty={$r['log_jumlah']} wh={$r['warehouse_id']} unit={$r['unit_id']}\n";
}

// PO count: all need warehouse_id since column missing
echo "\n=== PO warehouse_id fix count ===\n";
echo "All POs need warehouse_id column + backfill to 1: " . count($pos) . "\n";
$accLike = array_filter($pos, fn($r) => in_array((int)$r['status'], [2,3], true) || (int)$r['status'] === 2);
echo "status=2 (confirmed/completed-ish): " . count(array_filter($pos, fn($r)=>(int)$r['status']===2)) . "\n";
echo "status=3: " . count(array_filter($pos, fn($r)=>(int)$r['status']===3)) . "\n";
echo "status=-1: " . count(array_filter($pos, fn($r)=>(int)$r['status']===-1)) . "\n";
echo "status=0: " . count(array_filter($pos, fn($r)=>(int)$r['status']===0)) . "\n";
echo "status=1: " . count(array_filter($pos, fn($r)=>(int)$r['status']===1)) . "\n";
