<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$db = config('database.connections.mysql.database');
$host = config('database.connections.mysql.host');
echo "DB={$db} HOST={$host}\n";

if (! Schema::hasColumn('supplies', 'supplies_kind')) {
    echo "NO COLUMN supplies_kind\n";
    exit(0);
}

$total = DB::table('supplies')->where('status', 1)->count();
echo "active_supplies={$total}\n";

$byKind = DB::table('supplies')
    ->where('status', 1)
    ->selectRaw("CASE WHEN supplies_kind IS NULL OR supplies_kind = '' THEN 'supply' ELSE supplies_kind END as k, COUNT(*) as c")
    ->groupBy('k')
    ->get();

foreach ($byKind as $r) {
    echo "kind={$r->k} count={$r->c}\n";
}

$trading = DB::table('supplies')
    ->where('status', 1)
    ->where('supplies_kind', 'trading')
    ->orderBy('supplies_id')
    ->limit(50)
    ->get(['supplies_id', 'supplies_name', 'trading_product_variant_id']);

echo 'trading_count=' . DB::table('supplies')->where('status', 1)->where('supplies_kind', 'trading')->count() . "\n";
foreach ($trading as $t) {
    echo "#{$t->supplies_id} {$t->supplies_name} pv={$t->trading_product_variant_id}\n";
}
