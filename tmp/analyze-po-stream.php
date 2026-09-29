<?php
// Stream purchase_orders all inserts — count + PO08xx presence + warehouse_id
ini_set('memory_limit', '256M');
$dump = 'c:\\Users\\Ruben\\Downloads\\u906028329_pegasus (3).sql';
$fh = fopen($dump, 'r');
$in = false;
$cols = null;
$buf = '';
$total = 0;
$status = [];
$po08 = [];
$hasWhCol = false;
$maxNum = '';
$maxId = 0;

function parseValuesChunk(string $chunk, array $cols): array {
    $rows = []; $len = strlen($chunk); $i = 0;
    while ($i < $len) {
        while ($i < $len && (ctype_space($chunk[$i]) || $chunk[$i] === ',')) $i++;
        if ($i >= $len) break;
        if ($chunk[$i] !== '(') { $i++; continue; }
        $i++; $fields = []; $cur = ''; $inStr = false;
        while ($i < $len) {
            $ch = $chunk[$i];
            if ($inStr) {
                if ($ch === '\\') { $cur .= $ch . ($chunk[$i+1] ?? ''); $i += 2; continue; }
                if ($ch === "'") {
                    if (($chunk[$i+1] ?? '') === "'") { $cur .= "'"; $i += 2; continue; }
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

while (($line = fgets($fh)) !== false) {
    if (!$in) {
        if (preg_match('/^INSERT INTO `purchase_orders`\s*\((.*?)\)\s*VALUES\s*/', $line, $m)) {
            $in = true;
            $cols = array_map(fn($c)=>trim($c," `"), explode(',', $m[1]));
            $hasWhCol = in_array('warehouse_id', $cols, true);
            $buf = substr($line, strlen($m[0]));
            if (str_ends_with(rtrim($buf), ';')) {
                $buf = substr(rtrim($buf), 0, -1);
                goto proc;
            }
        }
        continue;
    }
    $buf .= $line;
    if (!str_ends_with(rtrim($line), ';')) continue;
    $buf = substr(rtrim($buf), 0, -1);
    proc:
    foreach (parseValuesChunk($buf, $cols) as $r) {
        $total++;
        $st = (string)$r['status'];
        $status[$st] = ($status[$st] ?? 0) + 1;
        $num = $r['po_number'] ?? '';
        if ($num > $maxNum) $maxNum = $num;
        $id = (int)$r['po_id'];
        if ($id > $maxId) $maxId = $id;
        if (preg_match('/^PO08/', $num)) {
            $po08[] = $r;
        }
    }
    $in = false; $buf = ''; $cols = null;
}
fclose($fh);
echo "PO total=$total max_id=$maxId max_num=$maxNum has_warehouse_id_in_insert=" . ($hasWhCol?'YES':'NO') . "\n";
echo "status: "; foreach ($status as $k=>$v) echo "$k=>$v "; echo "\n";
echo "PO08xx count=" . count($po08) . "\n";
usort($po08, fn($a,$b)=>strcmp($a['po_number'],$b['po_number']));
foreach ($po08 as $r) {
    $wh = $hasWhCol ? ($r['warehouse_id'] ?? 'n/a') : 'NO_COL';
    echo "{$r['po_number']} id={$r['po_id']} date={$r['po_date']} status={$r['status']} upd={$r['updated_at']} wh=$wh acc={$r['acc_by']}\n";
}
