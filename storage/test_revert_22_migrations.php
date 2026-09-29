<?php
/**
 * Sandbox test: reverse the 22 live path-migrations, then re-run them.
 * DB: pegasus_live_mig_sandbox (imported live dump). Does NOT touch live/local app DB.
 */
putenv('DB_DATABASE=pegasus_live_mig_sandbox');
$_ENV['DB_DATABASE'] = 'pegasus_live_mig_sandbox';
$_SERVER['DB_DATABASE'] = 'pegasus_live_mig_sandbox';

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Force connection DB name even if config cached
config(['database.connections.mysql.database' => 'pegasus_live_mig_sandbox']);
DB::purge('mysql');
DB::reconnect('mysql');

$names = [
    '2026_04_25_123000_add_so_ref_number_to_sales_orders_table',
    '2026_07_29_170000_convert_supplies_safety_stock_to_integer',
    '2026_07_30_010000_move_supplies_minimum_order_fields_to_supplies_table',
    '2026_07_31_010000_add_is_draft_to_stock_opnames_table',
    '2026_07_31_020000_add_is_draft_to_stock_opname_bahans_table',
    '2026_08_03_020000_add_unit_id_to_sales_delivery_orders_details',
    '2026_08_03_160000_make_customer_supply_returns_so_id_nullable',
    '2026_08_05_010000_add_resolved_by_system_to_productions_table',
    '2026_08_05_020000_add_source_cgd_id_to_cash_armadas_table',
    '2026_08_07_120000_add_return_group_to_customer_returns',
    '2026_08_11_120000_add_unique_index_to_customers_customer_code',
    '2026_08_11_130100_create_shipment_shortage_documents_table',
    '2026_08_14_010100_add_activity_tracking_to_dashboard_change_logs_table',
    '2026_08_15_161200_add_qc_staff_id_to_customer_returns',
    '2026_08_15_221500_add_destination_warehouse_id_to_customer_product_return_details',
    '2026_08_17_090100_make_customer_return_details_warehouse_id_nullable',
    '2026_08_21_010000_backfill_stod_touched_from_real_vs_system',
    '2026_08_22_090000_add_armada_fields_to_customers_table',
    '2026_08_23_090100_create_armada_match_reviews_table',
    '2026_08_27_010000_add_is_old_version_to_stock_opname_tables',
    '2026_08_27_020000_add_snapshot_columns_to_stock_opnames_table',
    '2026_08_27_040000_add_snapshot_columns_to_stock_opname_bahans_table',
];

function stCounts(): string {
    $rows = DB::table('stock_transfers')->selectRaw('status, count(*) c')->groupBy('status')->orderBy('status')->get();
    return $rows->map(fn ($r) => "{$r->status}={$r->c}")->implode(', ');
}

echo "DB=" . DB::connection()->getDatabaseName() . PHP_EOL;
echo "BEFORE ST: " . stCounts() . PHP_EOL;

$reverseOk = 0;
$reverseFail = [];
foreach (array_reverse($names) as $name) {
    $file = database_path("migrations/{$name}.php");
    if (! is_file($file)) {
        $reverseFail[] = "MISSING FILE $name";
        continue;
    }
    try {
        $migration = require $file;
        if (! is_object($migration) || ! method_exists($migration, 'down')) {
            $reverseFail[] = "NO DOWN $name";
            continue;
        }
        $migration->down();
        DB::table('migrations')->where('migration', $name)->delete();
        echo "DOWN OK  $name" . PHP_EOL;
        $reverseOk++;
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        echo "DOWN FAIL $name :: $msg" . PHP_EOL;
        $reverseFail[] = "$name :: $msg";
        // still remove migration row so re-up can be attempted? No — leave as-is for failed downs
    }
}

echo "==== REVERSE summary ok=$reverseOk fail=" . count($reverseFail) . " ====" . PHP_EOL;
echo "AFTER DOWN ST: " . stCounts() . PHP_EOL;

$upOk = 0;
$upFail = [];
foreach ($names as $name) {
    // skip if still marked ran (down failed)
    if (DB::table('migrations')->where('migration', $name)->exists()) {
        echo "SKIP UP (still Ran / down failed) $name" . PHP_EOL;
        continue;
    }
    $rel = "database/migrations/{$name}.php";
    try {
        $code = Artisan::call('migrate', ['--path' => $rel, '--force' => true]);
        $out = trim(Artisan::output());
        echo "UP code=$code $name\n$out" . PHP_EOL;
        if ($code === 0) {
            $upOk++;
        } else {
            $upFail[] = "$name exit=$code $out";
        }
    } catch (Throwable $e) {
        echo "UP FAIL $name :: " . $e->getMessage() . PHP_EOL;
        $upFail[] = "$name :: " . $e->getMessage();
    }
}

echo "==== UP summary ok=$upOk fail=" . count($upFail) . " ====" . PHP_EOL;
echo "FINAL ST: " . stCounts() . PHP_EOL;

$stillPending = [];
foreach ($names as $name) {
    if (! DB::table('migrations')->where('migration', $name)->exists()) {
        $stillPending[] = $name;
    }
}
echo "PENDING after test: " . (count($stillPending) ? implode(', ', $stillPending) : 'NONE') . PHP_EOL;

if ($reverseFail) {
    echo "REVERSE FAILURES:\n- " . implode("\n- ", $reverseFail) . PHP_EOL;
}
if ($upFail) {
    echo "UP FAILURES:\n- " . implode("\n- ", $upFail) . PHP_EOL;
}

echo (empty($reverseFail) && empty($upFail) && empty($stillPending) ? "VERDICT: SAFE (revert+rerun OK, ST unchanged intent)" : "VERDICT: NOT FULLY SAFE — see failures") . PHP_EOL;
