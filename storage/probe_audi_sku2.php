<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductVariant;

$needles = ['800ml', '800 ml', '1000ml', '1000 ml', 'AUDI LUBE 4T', 'Audi Lube 4T'];
foreach ($needles as $n) {
    $rows = ProductVariant::where(function ($q) use ($n) {
        $q->where('product_variant_name', 'like', '%'.$n.'%')
            ->orWhere('product_variant_sku', 'like', '%'.$n.'%');
    })->get(['product_variant_id', 'product_id', 'status', 'product_variant_sku', 'product_variant_name']);
    echo "=== {$n} ({$rows->count()}) ===\n";
    foreach ($rows as $r) {
        echo "  id={$r->product_variant_id} pid={$r->product_id} st={$r->status} sku={$r->product_variant_sku} name={$r->product_variant_name}\n";
    }
}
