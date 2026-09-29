<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductVariant;
use App\Models\Product;

$q = ProductVariant::query()
    ->where(function ($w) {
        $w->where('product_variant_name', 'like', '%Audi Lube%')
            ->orWhere('product_variant_sku', 'like', '%Audi%')
            ->orWhere('product_variant_name', 'like', '%4T%');
    })
    ->orderBy('product_variant_id');

echo "variants matching Audi/4T:\n";
foreach ($q->get(['product_variant_id', 'product_id', 'product_variant_sku', 'product_variant_name', 'status']) as $r) {
    echo "{$r->product_variant_id}|pid={$r->product_id}|st={$r->status}|sku={$r->product_variant_sku}|name={$r->product_variant_name}\n";
}

$prods = Product::where('status', 1)->where('product_name', 'like', '%Audi%')->get(['product_id', 'product_name', 'status']);
echo "\nproducts Audi:\n";
foreach ($prods as $p) {
    echo "{$p->product_id}|{$p->product_name}\n";
    foreach (ProductVariant::where('product_id', $p->product_id)->get(['product_variant_id', 'product_variant_sku', 'product_variant_name', 'status']) as $v) {
        echo "  v{$v->product_variant_id}|st={$v->status}|sku={$v->product_variant_sku}|{$v->product_variant_name}\n";
    }
}
