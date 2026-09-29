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
        fwrite(STDERR, "no insert for $table\n");

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
            $v = $fields[$i] ?? 'NULL';
            $assoc[$col] = ($v === 'NULL') ? null : trim($v, " '\"");
        }
        $out[] = $assoc;
    }

    return $out;
}

$pos = parseInsert($f, 'purchase_orders');
$details = parseInsert($f, 'purchase_orders_details');
$variants = parseInsert($f, 'supplies_variants');
$suppliers = parseInsert($f, 'suppliers');
$supplies = parseInsert($f, 'supplies');
$logs = parseInsert($f, 'log_stocks');

$poByNum = [];
foreach ($pos as $p) {
    $poByNum[$p['po_number'] ?? ''] = $p;
}
$supName = [];
foreach ($suppliers as $s) {
    $supName[(int) $s['supplier_id']] = $s['supplier_name'] ?? '?';
}
$svById = [];
foreach ($variants as $v) {
    $svById[(int) $v['supplies_variant_id']] = $v;
}
$supById = [];
foreach ($supplies as $s) {
    $supById[(int) $s['supplies_id']] = $s;
}

$target = 'PO0832';
$po = $poByNum[$target] ?? null;
if (! $po) {
    echo "PO0832 not found\n";
    // show nearby
    foreach ($poByNum as $n => $p) {
        if (preg_match('/PO083[0-9]/', (string) $n)) {
            echo "found $n status={$p['status']} supplier={$p['po_supplier']}\n";
        }
    }
    exit(1);
}

$poId = (int) $po['po_id'];
$poSup = (int) $po['po_supplier'];
echo "=== $target ===\n";
echo "po_id=$poId status={$po['status']} supplier_id=$poSup name=".($supName[$poSup] ?? '?')." warehouse=".($po['warehouse_id'] ?? 'null')." date={$po['po_date']}\n\n";

echo "=== details ===\n";
$mismatch = 0;
foreach ($details as $d) {
    if ((int) ($d['po_id'] ?? 0) !== $poId) {
        continue;
    }
    $svid = (int) ($d['supplies_variant_id'] ?? 0);
    $sv = $svById[$svid] ?? null;
    $svSup = $sv ? (int) ($sv['supplier_id'] ?? 0) : 0;
    $sku = $d['pod_sku'] ?? ($sv['supplies_variant_sku'] ?? '?');
    $name = $sv['supplies_variant_name'] ?? '?';
    $sid = $sv ? (int) $sv['supplies_id'] : 0;
    $sname = $supById[$sid]['supplies_name'] ?? '?';
    $ok = ($svSup === $poSup) ? 'OK' : 'MISMATCH';
    if ($ok === 'MISMATCH') {
        $mismatch++;
    }
    echo sprintf(
        "pod_id=%s sku=%s qty=%s unit=%s | variant_supplier=%s (%s) | supplies=%s | %s\n",
        $d['pod_id'] ?? '?',
        $sku,
        $d['pod_qty'] ?? '?',
        $d['unit_id'] ?? '?',
        $svSup,
        $supName[$svSup] ?? '?',
        $sname,
        $ok
    );
}
echo "mismatch_lines=$mismatch\n\n";

echo "=== log_stocks for PO0832 ===\n";
$n = 0;
foreach ($logs as $l) {
    $kode = (string) ($l['log_kode'] ?? '');
    $notes = (string) ($l['log_notes'] ?? '');
    if ($kode !== 'PO0832' && ! str_contains($notes, 'PO0832') && ! (str_contains($notes, 'Pembelian') && $kode === 'PO0832')) {
        if ($kode !== 'PO0832') {
            continue;
        }
    }
    if ($kode !== 'PO0832') {
        continue;
    }
    $n++;
    echo sprintf(
        "log_id=%s type=%s cat=%s item=%s qty=%s unit=%s wh=%s notes=%s created=%s\n",
        $l['log_id'] ?? '?',
        $l['log_type'] ?? '?',
        $l['log_category'] ?? '?',
        $l['log_item_id'] ?? '?',
        $l['log_jumlah'] ?? '?',
        $l['unit_id'] ?? '?',
        $l['warehouse_id'] ?? '?',
        $notes,
        $l['created_at'] ?? '?'
    );
}
echo "log_count=$n\n";

// Also list other POs same day with SUJS / Mulia
echo "\n=== other POs 2026-09-29 ===\n";
foreach ($pos as $p) {
    if (! str_starts_with((string) ($p['po_date'] ?? ''), '2026-09-29')) {
        continue;
    }
    $sid = (int) $p['po_supplier'];
    echo sprintf(
        "%s status=%s supplier=%s (%s)\n",
        $p['po_number'],
        $p['status'],
        $sid,
        $supName[$sid] ?? '?'
    );
}
