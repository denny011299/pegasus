<?php
$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\ProductionSkala;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\Warehouse;

echo "SKALA:\n";
foreach (ProductionSkala::where('status', 1)->get(['production_skala_id', 'code', 'name']) as $s) {
    echo "  {$s->production_skala_id} | {$s->code} | {$s->name}\n";
}

$needles = [
    'Greentech',
    'Super grease',
    'Super Grease',
    'Tyre polish',
    'Tyre Polish',
    'Radiator Coolant 12',
    'Radiator Coolant 4',
    'Air Accu 12',
    'Air Accu 20',
    'Air Aki 12',
    'Air Aki 20',
    'Silicone 30',
    'Silicone',
];
foreach ($needles as $n) {
    $q = ProductVariant::where('status', 1)
        ->where(function ($w) use ($n) {
            $w->where('product_variant_name', 'like', '%'.$n.'%')
                ->orWhere('product_variant_sku', 'like', '%'.$n.'%');
        })
        ->limit(8)
        ->get(['product_variant_id', 'product_variant_sku', 'product_variant_name']);
    echo "\n--- {$n} ({$q->count()}) ---\n";
    foreach ($q as $v) {
        echo "  {$v->product_variant_id} | {$v->product_variant_sku} | {$v->product_variant_name}\n";
    }
}

$pics = ['Supri', 'Aina', 'Aini', 'Saka', 'Riyanto', 'Linn', 'Aki'];
echo "\nSTAFF:\n";
foreach ($pics as $p) {
    $s = Staff::where('status', 1)->where('staff_name', 'like', '%'.$p.'%')->first(['staff_id', 'staff_name', 'role_id']);
    echo $s ? "  {$s->staff_id} {$s->staff_name} role={$s->role_id}\n" : "  MISSING {$p}\n";
}

echo "\nWH main: ".Warehouse::firstMainId()."\n";
foreach (Unit::where('status', 1)->orderBy('unit_id')->limit(10)->get(['unit_id', 'unit_name']) as $u) {
    echo "  unit {$u->unit_id} {$u->unit_name}\n";
}
$armada = Customer::where('status', 1)->whereNotNull('customer_code')->where('customer_code', '!=', '')
    ->orderBy('customer_id')->first(['customer_id', 'customer_name', 'customer_notes', 'customer_code']);
echo $armada
    ? "ARMADA {$armada->customer_id} {$armada->customer_code} ".($armada->customer_notes ?: $armada->customer_name)."\n"
    : "NO ARMADA\n";

$roles = Illuminate\Support\Facades\DB::table('roles')->orderBy('role_id')->limit(15)->get(['role_id', 'role_name']);
echo "\nROLES:\n";
foreach ($roles as $r) {
    echo "  {$r->role_id} {$r->role_name}\n";
}
