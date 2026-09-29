<?php
/**
 * Stream-parse dump for pembelian logs on non-main warehouse.
 * Memory-safe: does not load all log_stocks.
 */
ini_set('memory_limit', '512M');
$dump = 'c:\\Users\\Ruben\\Downloads\\u906028329_pegasus (3).sql';

$MAIN = 1;
$ECERAN = 2;
$SAM_EXCLUDE = [248, 249]; // GEAR 90 / GEAR 140 20x1L

$fh = fopen($dump, 'r');
$inLogInsert = false;
$stats = [
    'log_rows' => 0,
    'pembelian' => 0,
    'pembelian_wh1' => 0,
    'pembelian_wh2' => 0,
    'pembelian_other' => 0,
    'pembelian_wh2_sam' => 0,
    'pembelian_wh2_fix' => 0,
    'trading_wh2' => 0,
    'supply_wh2' => 0,
];
$agg = []; // key supplies|unit => qty, logs, kodes, types
$recent = [];
$sep28 = [];
$maxLogDate = '';
$minPembelianWrong = null;

function parseValuesChunk(string $chunk, array $cols): array
{
    $rows = [];
    $len = strlen($chunk);
    $i = 0;
    while ($i < $len) {
        while ($i < $len && (ctype_space($chunk[$i]) || $chunk[$i] === ',')) $i++;
        if ($i >= $len) break;
        if ($chunk[$i] !== '(') { $i++; continue; }
        $i++;
        $fields = [];
        $cur = '';
        $inStr = false;
        while ($i < $len) {
            $ch = $chunk[$i];
            if ($inStr) {
                if ($ch === '\\') { $cur .= $ch . ($chunk[$i + 1] ?? ''); $i += 2; continue; }
                if ($ch === "'") {
                    if (($chunk[$i + 1] ?? '') === "'") { $cur .= "'"; $i += 2; continue; }
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

$cols = null;
$buf = '';
while (($line = fgets($fh)) !== false) {
    if (!$inLogInsert) {
        if (preg_match('/^INSERT INTO `log_stocks`\s*\((.*?)\)\s*VALUES\s*/', $line, $m)) {
            $inLogInsert = true;
            $cols = array_map(fn ($c) => trim($c, " `"), explode(',', $m[1]));
            $buf = substr($line, strlen($m[0]));
            if (str_ends_with(rtrim($buf), ';')) {
                $buf = rtrim($buf);
                $buf = substr($buf, 0, -1);
                $rows = parseValuesChunk($buf, $cols);
                goto process;
            }
        }
        continue;
    }

    $buf .= $line;
    if (!str_ends_with(rtrim($line), ';')) {
        continue;
    }
    $buf = rtrim($buf);
    if (str_ends_with($buf, ';')) $buf = substr($buf, 0, -1);
    $rows = parseValuesChunk($buf, $cols);
    process:
    foreach ($rows as $r) {
        $stats['log_rows']++;
        $date = $r['log_date'] ?? '';
        if ($date > $maxLogDate) $maxLogDate = $date;

        $notes = (string)($r['log_notes'] ?? '');
        $kode = (string)($r['log_kode'] ?? '');
        $isPembelian = (stripos($notes, 'Pembelian') !== false)
            || preg_match('/^PO\d+/i', $kode);
        if (!$isPembelian) continue;

        $stats['pembelian']++;
        $wh = (int)($r['warehouse_id'] ?? 0);
        if ($wh === 1) $stats['pembelian_wh1']++;
        elseif ($wh === 2) $stats['pembelian_wh2']++;
        else $stats['pembelian_other']++;

        if ($wh === 1 || $wh === 0) continue;

        $item = (int)$r['log_item_id'];
        $unit = (int)$r['unit_id'];
        $type = (int)$r['log_type'];
        $cat = (int)$r['log_category'];
        $qty = (int)$r['log_jumlah'];
        $isSam = in_array($item, $SAM_EXCLUDE, true) && $type === 2;

        if ($wh === 2 && $isSam) $stats['pembelian_wh2_sam']++;
        if ($wh === 2 && !$isSam) $stats['pembelian_wh2_fix']++;
        if ($wh === 2 && $type === 1) $stats['trading_wh2']++;
        if ($wh === 2 && $type === 2) $stats['supply_wh2']++;

        // only masuk credits for move aggregate
        if ($cat !== 1) continue;
        if ($isSam) continue;

        $key = $type . '|' . $item . '|' . $unit;
        if (!isset($agg[$key])) {
            $agg[$key] = [
                'log_type' => $type,
                'item_id' => $item,
                'unit_id' => $unit,
                'qty' => 0,
                'logs' => 0,
                'kodes' => [],
                'wh' => $wh,
            ];
        }
        $agg[$key]['qty'] += $qty;
        $agg[$key]['logs']++;
        $agg[$key]['kodes'][$kode] = true;

        if (count($recent) < 80) {
            $recent[] = sprintf(
                "%s %s type=%d item=%d qty=%d wh=%d unit=%d | %s",
                $date, $kode, $type, $item, $qty, $wh, $unit, $notes
            );
        } else {
            // keep last-ish: replace if newer
        }

        if (preg_match('/2026-09-2[6-9]/', $date)) {
            $sep28[] = sprintf(
                "%s %s type=%d item=%d qty=%d wh=%d unit=%d%s | %s",
                $date, $kode, $type, $item, $qty, $wh, $unit,
                $isSam ? ' [SAM]' : '', $notes
            );
        }
    }
    $inLogInsert = false;
    $buf = '';
    $cols = null;
}
fclose($fh);

echo "=== log_stocks stream ===\n";
echo "max log_date: $maxLogDate\n";
foreach ($stats as $k => $v) echo "$k: $v\n";

echo "\n=== Sep26-29 wrong-WH pembelian ===\n";
echo "count: " . count($sep28) . "\n";
foreach ($sep28 as $l) echo "$l\n";

echo "\n=== Aggregate move WH!=1 masuk (excl SAM 248/249) ===\n";
uasort($agg, fn($a, $b) => $b['qty'] <=> $a['qty']);
echo "groups: " . count($agg) . "\n";
foreach ($agg as $a) {
    $kind = $a['log_type'] === 1 ? 'PRODUCT' : 'SUPPLY';
    $kodes = implode(',', array_keys($a['kodes']));
    echo "$kind item={$a['item_id']} unit={$a['unit_id']} qty={$a['qty']} logs={$a['logs']} wh={$a['wh']} POs=$kodes\n";
}

echo "\n=== Sample wrong-WH pembelian (first 40 captured) ===\n";
foreach (array_slice($recent, 0, 40) as $l) echo "$l\n";
