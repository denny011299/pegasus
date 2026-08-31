<?php
/**
 * Bandingkan data dump production vs DB setelah import + patch.
 *
 * Usage:
 *   php docs/scripts/verify_production_import.php [dump.sql] [database_name]
 *
 * Default dump: pegasuso_pegasus_production 31-08-26.sql di Downloads
 * Default DB: pegasus_production_local
 */

declare(strict_types=1);

$dumpPath = $argv[1] ?? 'C:/Users/Ruben/Downloads/pegasuso_pegasus_production 31-08-26.sql';
$dbName = $argv[2] ?? 'pegasus_production_local';

if (!is_file($dumpPath)) {
    fwrite(STDERR, "Dump tidak ditemukan: {$dumpPath}\n");
    exit(1);
}

/** @var array<string, int> */
$dumpCounts = countDumpRows($dumpPath);

/** @var array<string, string> */
$dumpPkColumns = [
    'sales_orders' => 'so_id',
    'sales_order_details' => 'sod_id',
    'purchase_orders' => 'po_id',
    'products' => 'product_id',
    'product_stocks' => 'ps_id',
    'supplies_stocks' => 'ss_id',
    'staffs' => 'staff_id',
    'customers' => 'customer_id',
    'log_stocks' => 'log_id',
];

/** @var array<string, int> */
$dumpMaxIds = maxDumpPrimaryKeys($dumpPath, $dumpPkColumns);

function countInsertBlockRows(string $block): int
{
    $rows = 0;
    foreach (explode("\n", $block) as $line) {
        $line = ltrim($line);
        if ($line === '' || $line[0] !== '(') {
            continue;
        }
        $rows += substr_count($line, '),(') + 1;
    }
    return $rows;
}

function countDumpRows(string $path): array
{
    $counts = [];
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        exit(1);
    }

    $currentTable = null;
    $buffer = '';

    while (($line = fgets($handle)) !== false) {
        if (preg_match('/^INSERT INTO `([^`]+)`/i', $line, $m)) {
            if ($currentTable !== null && $buffer !== '') {
                $counts[$currentTable] = ($counts[$currentTable] ?? 0) + countInsertBlockRows($buffer);
            }
            $currentTable = $m[1];
            $buffer = $line;
            continue;
        }

        if ($currentTable === null) {
            continue;
        }

        $buffer .= $line;
        if (str_contains(rtrim($line), ';')) {
            $counts[$currentTable] = ($counts[$currentTable] ?? 0) + countInsertBlockRows($buffer);
            $currentTable = null;
            $buffer = '';
        }
    }

    if ($currentTable !== null && $buffer !== '') {
        $counts[$currentTable] = ($counts[$currentTable] ?? 0) + countInsertBlockRows($buffer);
    }

    fclose($handle);
    return $counts;
}

/** @return array<string, int> */
function maxDumpPrimaryKeys(string $path, array $pkMap): array
{
    $max = array_fill_keys(array_keys($pkMap), 0);
    $handle = fopen($path, 'rb');
    if ($handle === false) {
        return $max;
    }

    $currentTable = null;
    while (($line = fgets($handle)) !== false) {
        if (preg_match('/^INSERT INTO `([^`]+)`/i', $line, $m)) {
            $currentTable = $m[1];
            continue;
        }
        if ($currentTable === null || !isset($pkMap[$currentTable])) {
            continue;
        }
        $line = ltrim($line);
        if ($line === '' || $line[0] !== '(') {
            continue;
        }
        if (preg_match('/^\((\d+),/', $line, $m)) {
            $id = (int) $m[1];
            if ($id > $max[$currentTable]) {
                $max[$currentTable] = $id;
            }
        }
    }
    fclose($handle);
    return $max;
}

$mysqli = @new mysqli('127.0.0.1', 'root', '', $dbName);
if ($mysqli->connect_error) {
    fwrite(STDERR, "DB connect gagal: {$mysqli->connect_error}\n");
    exit(1);
}

/** @var array<string, int> */
$dbCounts = [];
$res = $mysqli->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . $mysqli->real_escape_string($dbName) . "' AND TABLE_TYPE = 'BASE TABLE'");
while ($row = $res->fetch_assoc()) {
    $t = $row['TABLE_NAME'];
    $r = $mysqli->query("SELECT COUNT(*) AS c FROM `{$t}`");
    $dbCounts[$t] = (int) $r->fetch_assoc()['c'];
}

$allTables = array_unique(array_merge(array_keys($dumpCounts), array_keys($dbCounts)));
sort($allTables);

$ok = [];
$extra = [];
$missing = [];
$less = [];

foreach ($allTables as $table) {
    $dump = $dumpCounts[$table] ?? 0;
    $db = $dbCounts[$table] ?? 0;

    if ($dump === 0 && $db > 0) {
        $extra[$table] = $db;
        continue;
    }
    if ($dump > 0 && $db === 0) {
        $missing[$table] = $dump;
        continue;
    }
    if ($db < $dump) {
        $less[$table] = ['dump' => $dump, 'db' => $db, 'diff' => $dump - $db];
    } elseif ($db === $dump) {
        $ok[$table] = $dump;
    } else {
        // db > dump — expected for product_stocks, supplies_stocks (retail seed)
        $extra[$table] = ['dump' => $dump, 'db' => $db, 'diff' => $db - $dump];
    }
}

echo "=== VERIFY PRODUCTION IMPORT ===\n";
echo "Dump: {$dumpPath}\n";
echo "DB:   {$dbName}\n\n";

echo 'Identik (' . count($ok) . " tabel): " . implode(', ', array_slice(array_keys($ok), 0, 15));
if (count($ok) > 15) {
    echo ' ... +' . (count($ok) - 15);
}
echo "\n\n";

if ($missing !== []) {
    echo "HILANG (ada di dump, 0 di DB):\n";
    foreach ($missing as $t => $c) {
        echo "  - {$t}: dump={$c}\n";
    }
    echo "\n";
}

if ($less !== []) {
    echo "KURANG (db < dump):\n";
    foreach ($less as $t => $info) {
        echo "  - {$t}: dump={$info['dump']} db={$info['db']} kurang={$info['diff']}\n";
    }
    echo "\n";
}

echo "TAMBAH (db > dump — cek apakah expected):\n";
foreach ($extra as $t => $info) {
    if (is_int($info)) {
        echo "  - {$t}: +{$info} (tabel baru)\n";
        continue;
    }
    $flag = in_array($t, ['product_stocks', 'supplies_stocks'], true) ? ' [EXPECTED: gudang eceran stok 0]' : '';
    echo "  - {$t}: dump={$info['dump']} db={$info['db']} tambah={$info['diff']}{$flag}\n";
}

// Schema check — kolom kritis fase2
$critical = [
    'product_stocks' => ['warehouse_id', 'ps_safety_stock', 'ps_alert_stock', 'ps_min_order'],
    'stock_transfers' => ['qc_approved_by', 'ops_approved_by'],
    'stock_transfer_details' => ['received_unit_id'],
    'sales_orders' => ['retail_warehouse_id', 'ref_shipment_id', 'notes', 'cancel_reason'],
    'product_variants' => ['retail_unit', 'safety_stock', 'qty_per_pallet'],
];

echo "\n=== SCHEMA KRITIS ===\n";
$schemaOk = true;
foreach ($critical as $table => $cols) {
    foreach ($cols as $col) {
        $q = $mysqli->prepare('SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
        $q->bind_param('sss', $dbName, $table, $col);
        $q->execute();
        $exists = (int) $q->get_result()->fetch_assoc()['c'] > 0;
        $status = $exists ? 'OK' : 'MISSING';
        if (!$exists) {
            $schemaOk = false;
        }
        echo "  [{$status}] {$table}.{$col}\n";
    }
}

// MAX PK — pastikan tidak ada baris live yang hilang
echo "\n=== MAX PRIMARY KEY (dump vs db) ===\n";
$pkOk = true;
foreach ($dumpMaxIds as $table => $dumpMax) {
    if ($dumpMax === 0) {
        continue;
    }
    $col = $dumpPkColumns[$table];
    $where = in_array($table, ['product_stocks', 'supplies_stocks'], true) ? ' WHERE warehouse_id = 1' : '';
    $r = $mysqli->query("SELECT MAX(`{$col}`) AS m FROM `{$table}`{$where}");
    $dbMax = (int) ($r->fetch_assoc()['m'] ?? 0);
    $status = $dbMax >= $dumpMax ? 'OK' : 'KURANG';
    if ($dbMax < $dumpMax) {
        $pkOk = false;
    }
    echo "  [{$status}] {$table}.{$col}: dump={$dumpMax} db={$dbMax}\n";
}

// Stok gudang utama harus identik dengan dump
echo "\n=== INTEGRITAS STOK GUDANG UTAMA (warehouse_id=1) ===\n";
$stockOk = true;
foreach (['product_stocks' => 'ps_stock', 'supplies_stocks' => 'ss_stock'] as $table => $col) {
    $dumpRows = $dumpCounts[$table] ?? 0;
    $r = $mysqli->query("SELECT COUNT(*) AS c, COALESCE(SUM(`{$col}`),0) AS s FROM `{$table}` WHERE warehouse_id = 1");
    $row = $r->fetch_assoc();
    $mainCount = (int) $row['c'];
    $mainSum = $row['s'];
    $countOk = $mainCount === $dumpRows;
    $status = $countOk ? 'OK' : 'KURANG';
    if (!$countOk) {
        $stockOk = false;
    }
    echo "  [{$status}] {$table}: dump_rows={$dumpRows} main_wh_rows={$mainCount} sum_stock={$mainSum}\n";
}

$exit = ($missing === [] && $less === [] && $schemaOk && $pkOk && $stockOk) ? 0 : 1;
echo "\n" . ($exit === 0 ? "RESULT: PASS — data live utuh, schema siap fase2.\n" : "RESULT: FAIL — ada yang perlu diperbaiki.\n");
exit($exit);
