<?php
/**
 * Analyze staging dump for PO warehouse_id / pembelian stock misplacement.
 */
$dump = 'c:\\Users\\Ruben\\Downloads\\u906028329_pegasus (3).sql';
if (!is_file($dump)) {
    fwrite(STDERR, "Dump not found\n");
    exit(1);
}

echo "=== Dump size: " . filesize($dump) . " ===\n";

function extractInsertRows(string $path, string $table): array
{
    $fh = fopen($path, 'r');
    if (!$fh) {
        return [];
    }
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
    if ($buf === '') {
        return [];
    }

    // columns from INSERT INTO `t` (`a`,`b`) VALUES
    if (!preg_match('/INSERT INTO `' . preg_quote($table, '/') . '`\s*\((.*?)\)\s*VALUES\s*(.*);/s', $buf, $m)) {
        return [];
    }
    $cols = array_map(fn ($c) => trim($c, " `\n\r\t"), explode(',', $m[1]));
    $valuesSql = $m[2];

    $rows = [];
    $len = strlen($valuesSql);
    $i = 0;
    while ($i < $len) {
        while ($i < $len && ($valuesSql[$i] === ' ' || $valuesSql[$i] === "\n" || $valuesSql[$i] === "\r" || $valuesSql[$i] === ',')) {
            $i++;
        }
        if ($i >= $len) {
            break;
        }
        if ($valuesSql[$i] !== '(') {
            $i++;
            continue;
        }
        $i++; // skip (
        $fields = [];
        $cur = '';
        $inStr = false;
        $esc = false;
        while ($i < $len) {
            $ch = $valuesSql[$i];
            if ($inStr) {
                if ($esc) {
                    $cur .= $ch;
                    $esc = false;
                } elseif ($ch === '\\') {
                    $cur .= $ch;
                    $esc = true;
                } elseif ($ch === "'") {
                    // look ahead for '' escape
                    if ($i + 1 < $len && $valuesSql[$i + 1] === "'") {
                        $cur .= "''";
                        $i++;
                    } else {
                        $inStr = false;
                    }
                } else {
                    $cur .= $ch;
                }
                $i++;
                continue;
            }
            if ($ch === "'") {
                $inStr = true;
                $i++;
                continue;
            }
            if ($ch === ',') {
                $fields[] = $cur === 'NULL' ? null : $cur;
                $cur = '';
                $i++;
                continue;
            }
            if ($ch === ')') {
                $fields[] = $cur === 'NULL' ? null : $cur;
                $i++;
                break;
            }
            $cur .= $ch;
            $i++;
        }
        if (count($fields) === count($cols)) {
            $rows[] = array_combine($cols, $fields);
        }
    }
    return $rows;
}

function tableHasColumn(string $path, string $table, string $col): bool
{
    $fh = fopen($path, 'r');
    $in = false;
    while (($line = fgets($fh)) !== false) {
        if (preg_match('/^CREATE TABLE `' . preg_quote($table, '/') . '`/', $line)) {
            $in = true;
            continue;
        }
        if ($in) {
            if (str_starts_with($line, ') ENGINE')) {
                break;
            }
            if (preg_match('/`' . preg_quote($col, '/') . '`/', $line)) {
                fclose($fh);
                return true;
            }
        }
    }
    fclose($fh);
    return false;
}

echo "PO has warehouse_id: " . (tableHasColumn($dump, 'purchase_orders', 'warehouse_id') ? 'YES' : 'NO') . "\n";
echo "supplies_stocks has warehouse_id: " . (tableHasColumn($dump, 'supplies_stocks', 'warehouse_id') ? 'YES' : 'NO') . "\n";
echo "log_stocks has warehouse_id: " . (tableHasColumn($dump, 'log_stocks', 'warehouse_id') ? 'YES' : 'NO') . "\n";

echo "\n=== Warehouses ===\n";
$wh = extractInsertRows($dump, 'warehouses');
foreach ($wh as $r) {
    echo "id={$r['id']} type={$r['warehouse_type_id']} status={$r['status']} name={$r['warehouse_name']}\n";
}
$wt = extractInsertRows($dump, 'warehouse_types');
echo "\n=== Warehouse types ===\n";
foreach ($wt as $r) {
    echo "id={$r['id']} main={$r['is_main_warehouse']} name={$r['warehouse_type_name']}\n";
}

echo "\n=== SAM OIL supplies ===\n";
$supplies = extractInsertRows($dump, 'supplies');
$samIds = [];
foreach ($supplies as $r) {
    $name = $r['supplies_name'] ?? ($r['name'] ?? '');
    // try common columns
    $blob = implode('|', array_map('strval', $r));
    if (stripos($blob, 'SAM OIL') !== false || stripos($blob, 'GEAR 140') !== false || stripos($blob, 'GEAR 90') !== false) {
        if (stripos($blob, 'SAM OIL') !== false || stripos($blob, 'OIL GEAR') !== false) {
            echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
            $id = $r['supplies_id'] ?? $r['id'] ?? null;
            if ($id !== null && (stripos($blob, 'GEAR 140') !== false || stripos($blob, 'GEAR 90') !== false || stripos($blob, 'SAM OIL') !== false)) {
                if (stripos($blob, 'SAM OIL GEAR') !== false) {
                    $samIds[] = (int) $id;
                }
            }
        }
    }
}
echo "SAM ids candidate: " . implode(',', $samIds) . "\n";

echo "\n=== supplies columns sample ===\n";
if ($supplies) {
    echo implode(', ', array_keys($supplies[0])) . "\n";
    // print any with OIL GEAR
    foreach ($supplies as $r) {
        $nameCol = null;
        foreach ($r as $k => $v) {
            if (is_string($v) && stripos($v, 'SAM OIL') !== false) {
                echo "{$k}={$v} row_id=" . ($r['supplies_id'] ?? '?') . "\n";
            }
        }
    }
}
