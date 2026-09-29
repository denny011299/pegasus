<?php
$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

$needles = ['Hikari', 'PEGASUS', 'Coolant', 'Grease', 'Greentech', 'Tyre', 'Polish', 'Silicone', 'Accu', 'Aki', 'Radiator'];
foreach ($needles as $n) {
    $cnt = ProductVariant::where('status', 1)
        ->where(function ($w) use ($n) {
            $w->where('product_variant_name', 'like', '%'.$n.'%')
                ->orWhere('product_variant_sku', 'like', '%'.$n.'%');
        })->count();
    echo "{$n}: {$cnt}\n";
}

echo "\nSample products matching coolant/grease/aki/silicone/polish:\n";
$rows = ProductVariant::where('status', 1)
    ->where(function ($w) {
        $w->where('product_variant_name', 'like', '%Coolant%')
            ->orWhere('product_variant_name', 'like', '%GREASE%')
            ->orWhere('product_variant_name', 'like', '%Grease%')
            ->orWhere('product_variant_name', 'like', '%AKI%')
            ->orWhere('product_variant_name', 'like', '%Aki%')
            ->orWhere('product_variant_name', 'like', '%SILICON%')
            ->orWhere('product_variant_name', 'like', '%TYRE%')
            ->orWhere('product_variant_name', 'like', '%POLISH%')
            ->orWhere('product_variant_name', 'like', '%GREENTECH%')
            ->orWhere('product_variant_name', 'like', '%GREEN TECH%');
    })
    ->orderBy('product_variant_name')
    ->limit(80)
    ->get(['product_variant_id', 'product_variant_sku', 'product_variant_name']);
foreach ($rows as $v) {
    echo "  {$v->product_variant_id} | {$v->product_variant_sku} | {$v->product_variant_name}\n";
}

echo "\nTotal active variants: ".ProductVariant::where('status', 1)->count()."\n";
echo "Sample 15:\n";
foreach (ProductVariant::where('status', 1)->orderBy('product_variant_id')->limit(15)->get(['product_variant_id', 'product_variant_sku', 'product_variant_name']) as $v) {
    echo "  {$v->product_variant_id} | {$v->product_variant_sku} | {$v->product_variant_name}\n";
}

// staff columns for insert
$cols = Schema::getColumnListing('staffs');
echo "\nstaffs cols: ".implode(', ', $cols)."\n";
$sample = App\Models\Staff::where('status', 1)->orderBy('staff_id')->first();
if ($sample) {
    echo "sample staff: ".json_encode($sample->only(['staff_id','staff_name','staff_code','staff_username','role_id','staff_email']))."\n";
}
