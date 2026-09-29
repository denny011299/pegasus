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
$invoices = parseInsert($f, 'purchase_order_invoices');

$supName = [];
foreach ($suppliers as $s) {
    $supName[(int) $s['supplier_id']] = $s['supplier_name'] ?? '?';
}
$svById = [];
foreach ($variants as $v) {
    $svById[(int) $v['supplies_variant_id']] = $v;
}

$po = null;
foreach ($pos as $p) {
    if (($p['po_number'] ?? '') === 'PO0832') {
        $po = $p;
        break;
    }
}
$poId = (int) $po['po_id'];
$poSup = (int) $po['po_supplier'];

echo "PO0832 po_id=$poId status={$po['status']} supplier=$poSup (".$supName[$poSup].") created={$po['created_at']} updated={$po['updated_at']}\n\n";

echo "DETAILS:\n";
foreach ($details as $d) {
    if ((int) ($d['po_id'] ?? 0) !== $poId) {
        continue;
    }
    $svid = (int) ($d['supplies_variant_id'] ?? 0);
    $sv = $svById[$svid] ?? [];
    $svSup = (int) ($sv['supplier_id'] ?? 0);
    $flag = $svSup === $poSup ? 'OK' : 'SALAH_SUPPLIER';
    echo sprintf(
        "pod=%s sku=%s qty=%s | variant_id=%s variant_supplier=%s (%s) | created=%s | %s\n",
        $d['pod_id'],
        $d['pod_sku'] ?? '?',
        $d['pod_qty'] ?? '?',
        $svid,
        $svSup,
        $supName[$svSup] ?? '?',
        $d['created_at'] ?? '?',
        $flag
    );
}

echo "\nINVOICES for po:\n";
foreach ($invoices as $inv) {
    if ((int) ($inv['po_id'] ?? 0) !== $poId) {
        continue;
    }
    echo "poi={$inv['poi_id']} num=".($inv['poi_number'] ?? $inv['invoice_number'] ?? '?')." status={$inv['status']} created={$inv['created_at']}\n";
}

// Timeline: same variant on other POs that day
echo "\nSame SKU BTLMREM1LTRSUJS on other POs (new dump):\n";
foreach ($details as $d) {
    if (($d['pod_sku'] ?? '') !== 'BTLMREM1LTRSUJS') {
        continue;
    }
    $pid = (int) $d['po_id'];
    $p = null;
    foreach ($pos as $x) {
        if ((int) $x['po_id'] === $pid) {
            $p = $x;
            break;
        }
    }
    $ps = (int) ($p['po_supplier'] ?? 0);
    echo sprintf(
        "PO=%s status=%s po_supplier=%s (%s) qty=%s created=%s\n",
        $p['po_number'] ?? $pid,
        $p['status'] ?? '?',
        $ps,
        $supName[$ps] ?? '?',
        $d['pod_qty'],
        $d['created_at']
    );
}
