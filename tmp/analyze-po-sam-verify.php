<?php
// Find SAM OIL pembelian logs + distinct wrong-WH PO numbers + verify stock vs aggregate
ini_set('memory_limit', '512M');
$dump = 'c:\\Users\\Ruben\\Downloads\\u906028329_pegasus (3).sql';
$SAM = [248, 249];

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

$fh = fopen($dump, 'r');
$in = false; $cols = null; $buf = '';
$samLogs = [];
$wrongPo = [];
$agg = [];
while (($line = fgets($fh)) !== false) {
    if (!$in) {
        if (preg_match('/^INSERT INTO `log_stocks`\s*\((.*?)\)\s*VALUES\s*/', $line, $m)) {
            $in = true;
            $cols = array_map(fn($c)=>trim($c," `"), explode(',', $m[1]));
            $buf = substr($line, strlen($m[0]));
            if (str_ends_with(rtrim($buf), ';')) { $buf = substr(rtrim($buf),0,-1); goto proc; }
        }
        continue;
    }
    $buf .= $line;
    if (!str_ends_with(rtrim($line), ';')) continue;
    $buf = substr(rtrim($buf), 0, -1);
    proc:
    foreach (parseValuesChunk($buf, $cols) as $r) {
        $notes = (string)($r['log_notes'] ?? '');
        $kode = (string)($r['log_kode'] ?? '');
        $isPembelian = stripos($notes, 'Pembelian') !== false || preg_match('/^PO\d+/i', $kode);
        if (!$isPembelian) continue;
        $wh = (int)($r['warehouse_id'] ?? 0);
        $item = (int)$r['log_item_id'];
        $type = (int)$r['log_type'];
        $cat = (int)$r['log_category'];
        if ($type === 2 && in_array($item, $SAM, true)) {
            $samLogs[] = $r;
        }
        if ($wh !== 2) continue;
        if ($cat !== 1) continue;
        $wrongPo[$kode] = true;
        if (in_array($item, $SAM, true) && $type === 2) continue;
        $key = $item . '|' . $r['unit_id'];
        if (!isset($agg[$key])) $agg[$key] = ['supplies_id'=>$item,'unit_id'=>(int)$r['unit_id'],'qty'=>0];
        $agg[$key]['qty'] += (int)$r['log_jumlah'];
    }
    $in = false; $buf = ''; $cols = null;
}
fclose($fh);

echo "=== SAM OIL pembelian logs (all WH) ===\n";
foreach ($samLogs as $r) {
    echo "{$r['log_date']} {$r['log_kode']} item={$r['log_item_id']} qty={$r['log_jumlah']} wh={$r['warehouse_id']} unit={$r['unit_id']} cat={$r['log_category']} | {$r['log_notes']}\n";
}
echo "\nDistinct wrong-WH (eceran) POs: " . count($wrongPo) . "\n";
ksort($wrongPo);
echo implode(', ', array_keys($wrongPo)) . "\n";

// Compare agg to current WH2 stock
function extractTable($path, $table) {
    // reuse light extractor for small tables only
    $fh = fopen($path, 'r'); $collecting=false; $bufs=[]; $buf=''; $prefix="INSERT INTO `$table`";
    while (($line=fgets($fh))!==false) {
        if (!$collecting) {
            if (str_starts_with($line,$prefix)||str_starts_with(ltrim($line),$prefix)) {
                $collecting=true; $buf=$line;
                if (str_ends_with(rtrim($line),';')) { $bufs[]=$buf; $collecting=false; $buf=''; }
            }
            continue;
        }
        $buf.=$line;
        if (str_ends_with(rtrim($line),';')) { $bufs[]=$buf; $collecting=false; $buf=''; }
    }
    fclose($fh);
    $rows=[];
    foreach ($bufs as $buf) {
        if (!preg_match('/INSERT INTO `'.preg_quote($table,'/').'`\s*\((.*?)\)\s*VALUES\s*(.*);/s',$buf,$m)) continue;
        $cols=array_map(fn($c)=>trim($c," `\n\r\t"), explode(',',$m[1]));
        foreach (parseValuesChunk($m[2], $cols) as $r) $rows[]=$r;
    }
    return $rows;
}
$ss = extractTable($dump, 'supplies_stocks');
$idx=[];
foreach ($ss as $r) {
    $idx[$r['supplies_id'].'|'.$r['unit_id'].'|'.$r['warehouse_id']] = (int)$r['ss_stock'];
}
echo "\n=== WH2 stock vs pembelian credit qty (excl SAM) ===\n";
$short=0; $ok=0; $exact=0;
foreach ($agg as $a) {
    $s2 = $idx[$a['supplies_id'].'|'.$a['unit_id'].'|2'] ?? null;
    $s1 = $idx[$a['supplies_id'].'|'.$a['unit_id'].'|1'] ?? null;
    $flag = 'OK';
    if ($s2 === null) $flag = 'MISSING_WH2';
    elseif ($s2 < $a['qty']) { $flag = 'SHORT'; $short++; }
    elseif ($s2 === $a['qty']) { $flag = 'EXACT'; $exact++; $ok++; }
    else { $flag = 'EXTRA'; $ok++; } // leftover beyond ACC
    echo "sup={$a['supplies_id']} unit={$a['unit_id']} credit={$a['qty']} wh2=" . ($s2??'NULL') . " wh1=" . ($s1??'NULL') . " $flag\n";
}
echo "exact=$exact short=$short\n";
