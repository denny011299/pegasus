<?php
// Minimal seed — bypass artisan (hang-prone). Local only.
$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\ShipmentShortageDocument;
use App\Models\Unit;
use Illuminate\Support\Facades\Schema;

if (! Schema::hasTable('shipment_shortage_documents')) {
    fwrite(STDERR, "missing table shipment_shortage_documents\n");
    exit(1);
}

$armadas = Customer::query()
    ->where('status', 1)
    ->whereNotNull('customer_code')
    ->where('customer_code', '!=', '')
    ->orderBy('customer_id')
    ->limit(30)
    ->get();
$variants = ProductVariant::query()
    ->where('status', 1)
    ->whereNotNull('product_variant_sku')
    ->where('product_variant_sku', '!=', '')
    ->orderBy('product_variant_id')
    ->limit(20)
    ->get();

if ($armadas->isEmpty() || $variants->isEmpty()) {
    fwrite(STDERR, "missing armada/variant\n");
    exit(1);
}

$unit = Unit::query()->where('status', 1)->orderBy('unit_id')->first();
$unitId = $unit?->unit_id ?? 1;
$refUnitId = (int) ($unit?->ref_unit_id ?: $unitId);
$count = 25;
$created = 0;
$skipped = 0;

for ($i = 1; $i <= $count; $i++) {
    $ref = 'DEMO-SHORTAGE-LOCAL-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
    if (SalesOrder::where('ref_shipment_id', $ref)->exists()) {
        $skipped++;
        continue;
    }

    $armada = $armadas[$i % $armadas->count()];
    $variant = $variants[$i % $variants->count()];
    $variant2 = $variants[($i + 3) % $variants->count()];
    $soDate = now()->subDays($i % 13)->toDateString();
    $requested = 12 + ($i * 3);
    $available = max(0, (int) floor($requested * 0.3) - ($i % 4));
    $shortage = max(1, $requested - $available);

    $so = (new SalesOrder())->insertSalesOrder([
        'so_customer' => (string) $armada->customer_id,
        'so_date' => $soDate,
        'so_total' => 0,
        'so_img' => json_encode([]),
    ]);
    $so->ref_shipment_id = $ref;
    $so->so_ref_number = 'DEMO-SHORTAGE';
    $so->status = 4;
    $so->save();

    (new SalesOrderDetail())->insertSalesOrderDetail([
        'so_id' => $so->so_id,
        'product_variant_id' => $variant->product_variant_id,
        'product_name' => $variant->product_variant_name ?: 'Demo',
        'product_variant_name' => $variant->product_variant_name ?: '',
        'product_variant_sku' => $variant->product_variant_sku,
        'unit_id' => $unitId,
        'warehouse_id' => 1,
        'product_variant_price' => 0,
        'so_qty' => $requested,
        'so_subtotal' => 0,
    ]);

    $items = [[
        'sku' => $variant->product_variant_sku,
        'unit_id' => $refUnitId,
        'requested' => $requested,
        'available' => $available,
        'shortage' => $shortage,
    ]];
    if ($i % 2 === 0 && $variant2->product_variant_id !== $variant->product_variant_id) {
        $items[] = [
            'sku' => $variant2->product_variant_sku,
            'unit_id' => $refUnitId,
            'requested' => 8 + $i,
            'available' => 0,
            'shortage' => 8 + $i,
        ];
    }

    ShipmentShortageDocument::createForShortage((int) $so->so_id, $ref, $items, null);
    $created++;
    echo "created {$ref} so={$so->so_id}\n";
}

echo "DONE created={$created} skipped={$skipped}\n";
