<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SalesOrder;
use App\Models\ShipmentShortageDocument;
use Illuminate\Support\Facades\DB;

$mode = $argv[1] ?? 'diagnose';

$demoQuery = SalesOrder::query()
    ->where(function ($q) {
        $q->where('so_ref_number', 'DEMO-SHORTAGE')
            ->orWhere('ref_shipment_id', 'like', 'DEMO-SHORTAGE%');
    });

$ids = (clone $demoQuery)->pluck('so_id');
echo 'DEMO SO count: ' . $ids->count() . PHP_EOL;

if ($ids->isEmpty()) {
    exit(0);
}

$passNonRetail = DB::table('sales_orders as so')
    ->whereIn('so.so_id', $ids)
    ->where('so.status', '>=', 1)
    ->whereExists(function ($sub) {
        $sub->from('sales_order_details as sod')
            ->join('product_variants as pv', 'pv.product_variant_id', '=', 'sod.product_variant_id')
            ->whereColumn('sod.so_id', 'so.so_id')
            ->where('sod.status', 1)
            ->where(function ($w) {
                $w->whereNull('pv.retail_unit')
                    ->orWhere('pv.retail_unit', 0)
                    ->orWhereColumn('sod.unit_id', '!=', 'pv.retail_unit');
            });
    })
    ->count();
echo "Pass main-warehouse non-retail filter: {$passNonRetail}" . PHP_EOL;

$sample = DB::table('sales_order_details as sod')
    ->join('product_variants as pv', 'pv.product_variant_id', '=', 'sod.product_variant_id')
    ->whereIn('sod.so_id', $ids->take(5))
    ->select('sod.so_id', 'sod.status', 'sod.warehouse_id', 'sod.unit_id', 'pv.retail_unit', 'pv.product_variant_sku')
    ->get();
echo 'Sample details:' . PHP_EOL;
foreach ($sample as $row) {
    echo json_encode((array) $row) . PHP_EOL;
}

foreach ([
    'search DEMO-SHORTAGE' => ['search' => ['value' => 'DEMO-SHORTAGE']],
    'date 2026-09-02 only' => ['date_from' => '2026-09-02', 'date_to' => '2026-09-02'],
    'date 2026-09-02 + DEMO search' => [
        'date_from' => '2026-09-02',
        'date_to' => '2026-09-02',
        'search' => ['value' => 'DEMO-SHORTAGE'],
    ],
] as $label => $extra) {
    $dt = (new SalesOrder())->getSalesOrderDataTable(array_merge([
        'active_warehouse_id' => 1,
        'start' => 0,
        'length' => 100,
    ], $extra));
    echo "DataTable (wh=1, {$label}): filtered={$dt['recordsFiltered']}, page=" . count($dt['data']) . PHP_EOL;
}

$dates = DB::table('sales_orders')->whereIn('so_id', $ids)->selectRaw('so_date, count(*) as c')->groupBy('so_date')->orderBy('so_date')->get();
echo 'DEMO so_date distribution:' . PHP_EOL;
foreach ($dates as $d) {
    echo "  {$d->so_date}: {$d->c}" . PHP_EOL;
}

if ($mode !== 'fix') {
    echo "Run: php storage/fix_demo_visibility.php fix" . PHP_EOL;
    exit(0);
}

echo 'Applying fix...' . PHP_EOL;

// Pick unit_id that differs from retail_unit for each variant (non-retail path)
$now = now();
$fixedDetails = 0;
$fixedSos = 0;

foreach ($ids as $soId) {
    $so = SalesOrder::find($soId);
    if (! $so) {
        continue;
    }

    $so->so_date = '2026-09-02';
    $so->so_ref_number = 'DEMO-SHORTAGE';
    $so->status = 4;
    $so->created_at = $now;
    $so->updated_at = $now;
    $so->save();
    $fixedSos++;

    $details = DB::table('sales_order_details')->where('so_id', $soId)->get();
    foreach ($details as $d) {
        $pv = DB::table('product_variants')->where('product_variant_id', $d->product_variant_id)->first();
        $retailUnit = (int) ($pv->retail_unit ?? 0);
        $unitId = (int) $d->unit_id;

        if ($retailUnit > 0 && $unitId === $retailUnit) {
            $alt = DB::table('units')
                ->where('status', 1)
                ->where('unit_id', '!=', $retailUnit)
                ->orderBy('unit_id')
                ->value('unit_id');
            if (! $alt && ! empty($pv->ref_unit_id)) {
                $alt = (int) $pv->ref_unit_id;
            }
            if ($alt && (int) $alt !== $retailUnit) {
                $unitId = (int) $alt;
            }
        }

        DB::table('sales_order_details')
            ->where('so_id', $soId)
            ->where('product_variant_id', $d->product_variant_id)
            ->update([
                'status' => 1,
                'warehouse_id' => 1,
                'unit_id' => $unitId,
                'updated_at' => $now,
            ]);
        $fixedDetails++;
    }

    $ref = $so->ref_shipment_id ?: ('DEMO-SHORTAGE-LOCAL-' . str_pad((string) $soId, 3, '0', STR_PAD_LEFT));
    if (! ShipmentShortageDocument::where('so_id', $soId)->where('status', 1)->exists()) {
        $items = DB::table('sales_order_details as sod')
            ->join('product_variants as pv', 'pv.product_variant_id', '=', 'sod.product_variant_id')
            ->where('sod.so_id', $soId)
            ->where('sod.status', 1)
            ->get(['pv.product_variant_sku as sku', 'sod.unit_id', 'sod.so_qty as requested']);
        $payload = [];
        foreach ($items as $it) {
            $req = (int) $it->requested;
            $payload[] = [
                'sku' => $it->sku,
                'unit_id' => (int) $it->unit_id,
                'requested' => $req,
                'available' => max(0, (int) floor($req * 0.3)),
                'shortage' => max(1, $req - max(0, (int) floor($req * 0.3))),
            ];
        }
        if ($payload !== []) {
            ShipmentShortageDocument::createForShortage((int) $soId, $ref, $payload, null);
        }
    }
}

echo "Fixed SOs: {$fixedSos}, detail updates: {$fixedDetails}" . PHP_EOL;

$dtAfter = (new SalesOrder())->getSalesOrderDataTable([
    'active_warehouse_id' => 1,
    'search' => ['value' => 'DEMO-SHORTAGE'],
    'start' => 0,
    'length' => 100,
]);
echo 'After fix — filtered=' . $dtAfter['recordsFiltered']
    . ', page rows=' . count($dtAfter['data']) . PHP_EOL;
