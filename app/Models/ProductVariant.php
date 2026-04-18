<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class ProductVariant extends Model
{
    protected $table = "product_variants";
    protected $primaryKey = "product_variant_id";
    public $timestamps = true;
    public $incrementing = true;

    public function getProductVariant($data = [])
    {
        $data = array_merge([
            "product_id" => null,
            "product_variant_sku" => null,
            "product_variant_id" => null,
            "status" => 1,
            "search_product" => null,
            "category_id" => null,
        ], $data);

        $result = self::where("product_variants.status", "=", 1);
        $result->join("products as pr", 'pr.product_id','product_variants.product_id');
        $result->where("pr.status", "=", 1);

        // Filter berdasarkan product_id
        if ($data["product_id"]) {
            $result->where("product_variants.product_id", "=", $data["product_id"]);
        }

        // Filter berdasarkan SKU
        if ($data["product_variant_sku"]) {
            $result->where("product_variants.product_variant_sku", "like", "%" . $data["product_variant_sku"] . "%");
        }

        if ($data["search_product"]){
            $result->whereAny([
                "pr.product_name",
                "product_variants.product_variant_name"
            ], "like", "%" . $data["search_product"] . "%");
        }

        // Filter berdasarkan product_variant_id
        if ($data["product_variant_id"]) {
            $result->where("product_variants.product_variant_id", "=", $data["product_variant_id"]);
        }
        

        // Filter berdasarkan product_variant_id
        if ($data["category_id"]) {
            $result->join("products as p", 'p.product_id','product_variants.product_id');
            $result->where('category_id','=',$data["category_id"]);
        }
        
        $result->select('product_variants.*');
        $result->orderBy("created_at", "asc");
        $variants = $result->get();
        
        // Menambahkan nama produk dari relasi
        foreach ($variants as $variant) {
            $p = Product::find($variant->product_id);
            $u =  Unit::whereIn('unit_id', json_decode($p->product_unit,true))->first();

            $variant->pr_name = $p ? $p->product_name : "-";
            $variant->product_unit = $u ? $u->unit_name : "-";
            $variant->product_category = Category::find($p->category_id)->category_name ?? "-";
            $variant->category_id = $p->category_id;
            $variant->pr_unit = Unit::whereIn('unit_id', json_decode($p->product_unit,true))->get();
            $variant->relasi = (new ProductRelation())->getProductRelation(['product_variant_id' =>$variant->product_variant_id]);
            $variant->stock = (new ProductStock())->getProductStock(["product_variant_id"=>$variant->product_variant_id]);
            
            // Get nama unit default

            // Get stock
            $s = ProductStock::where('product_variant_id', $variant->product_variant_id)
                ->where('unit_id', $variant->unit_id)
                ->first();
            $variant->ps_stock = $s ? $s->ps_stock : 0;
        }

        return $variants;
    }

    /**
     * Same enrichment as getProductVariant(), but one round-trip for many IDs
     * (used by stock opname list to avoid N+1).
     *
     * @param  array<int>  $productVariantIds
     * @return \Illuminate\Support\Collection<string|int, self>
     */
    public function getProductVariantBulk(array $productVariantIds)
    {
        $productVariantIds = array_values(array_unique(array_filter(array_map('intval', $productVariantIds))));
        if ($productVariantIds === []) {
            return collect();
        }

        $result = self::where('product_variants.status', '=', 1)
            ->join('products as pr', 'pr.product_id', '=', 'product_variants.product_id')
            ->where('pr.status', '=', 1)
            ->whereIn('product_variants.product_variant_id', $productVariantIds)
            ->select('product_variants.*')
            ->orderBy('product_variants.created_at', 'asc')
            ->get();

        if ($result->isEmpty()) {
            return collect();
        }

        $productIds = $result->pluck('product_id')->unique()->filter()->all();
        $products = Product::whereIn('product_id', $productIds)->get()->keyBy('product_id');

        $allUnitIds = [];
        foreach ($products as $p) {
            foreach (json_decode($p->product_unit, true) ?: [] as $uid) {
                $allUnitIds[(int) $uid] = true;
            }
        }

        $relations = ProductRelation::where('status', '=', 1)
            ->whereIn('product_variant_id', $productVariantIds)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($relations as $rel) {
            $allUnitIds[(int) $rel->pr_unit_id_1] = true;
            $allUnitIds[(int) $rel->pr_unit_id_2] = true;
        }

        $stocks = ProductStock::where('status', '=', 1)
            ->whereIn('product_variant_id', $productVariantIds)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($stocks as $stk) {
            $allUnitIds[(int) $stk->unit_id] = true;
        }

        $units = $allUnitIds !== []
            ? Unit::whereIn('unit_id', array_keys($allUnitIds))->get()->keyBy('unit_id')
            : collect();

        foreach ($relations as $rel) {
            $u1 = $units->get($rel->pr_unit_id_1);
            $u2 = $units->get($rel->pr_unit_id_2);
            $rel->pr_unit_name_1 = $u1 ? $u1->unit_short_name : '-';
            $rel->pr_unit_name_2 = $u2 ? $u2->unit_short_name : '-';
        }

        foreach ($stocks as $stk) {
            $u = $units->get($stk->unit_id);
            $stk->unit_name = $u ? $u->unit_name : '-';
            $stk->unit_short_name = $u ? $u->unit_short_name : '-';
        }

        $relationsGrouped = $relations->groupBy('product_variant_id');
        $stocksGrouped = $stocks->groupBy('product_variant_id');

        $categoryIds = $products->pluck('category_id')->unique()->filter()->all();
        $categories = $categoryIds !== []
            ? Category::whereIn('category_id', $categoryIds)->pluck('category_name', 'category_id')
            : collect();

        $prUnitsByProductId = [];
        foreach ($products as $pid => $p) {
            $uids = json_decode($p->product_unit, true) ?: [];
            $prUnitsByProductId[$pid] = $uids !== []
                ? Unit::whereIn('unit_id', $uids)->get()
                : collect();
        }

        $out = collect();
        foreach ($result as $variant) {
            $clone = clone $variant;
            $p = $products->get($clone->product_id);
            if (! $p) {
                $clone->pr_name = '-';
                $clone->product_unit = '-';
                $clone->product_category = '-';
                $clone->category_id = null;
                $clone->pr_unit = collect();
                $clone->relasi = collect();
                $clone->stock = collect();
                $clone->ps_stock = 0;
                $out->put($clone->product_variant_id, $clone);

                continue;
            }

            $prUnitCol = $prUnitsByProductId[$p->product_id] ?? collect();
            $uFirst = $prUnitCol->first();
            $clone->pr_name = $p->product_name ?? '-';
            $clone->product_unit = $uFirst ? $uFirst->unit_name : '-';
            $clone->product_category = $categories->get($p->category_id) ?? '-';
            $clone->category_id = $p->category_id;
            $clone->pr_unit = $prUnitCol;
            $clone->relasi = $relationsGrouped->get($clone->product_variant_id, collect());
            $clone->stock = $stocksGrouped->get($clone->product_variant_id, collect());

            $s = $clone->stock->firstWhere('unit_id', $clone->unit_id);
            if (! $s) {
                $s = ProductStock::where('product_variant_id', $clone->product_variant_id)
                    ->where('unit_id', $clone->unit_id)
                    ->where('status', '=', 1)
                    ->first();
            }
            $clone->ps_stock = $s ? $s->ps_stock : 0;

            $out->put($clone->product_variant_id, $clone);
        }

        return $out;
    }

    public function insertProductVariant($data)
    {
        if($data["variant_name"]=="standar")$data["variant_name"] = "";
        $t = new self();
        $t->product_id = $data["product_id"];
        $t->product_variant_name = $data["variant_name"];
        $t->product_variant_sku = $data["variant_sku"];
        $t->unit_id = $data["unit_id"];
      //  $t->product_variant_price = $data["variant_price"]!=""? $data["variant_price"] : 0;
        $t->product_variant_barcode = $data["variant_barcode"]!="" ? $data["variant_barcode"] : $t->generateBarcode();
        $t->product_variant_alert = $data["variant_alert"]!="" ? $data["variant_alert"] : 0;
        $t->product_variant_stock = 0;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->product_variant_id;
    }

    public function updateProductVariant($data)
    {
        $t = self::find($data["product_variant_id"]);
        if (!$t) {
            return $this->insertProductVariant([
                "product_id"      => $data["product_id"],
                "variant_name"    => $data["variant_name"],
                "variant_sku"     => $data["variant_sku"],
                "variant_alert"   => $data["variant_alert"],
                "variant_barcode" => $data["variant_barcode"] ?? "",
                "unit_id" => $data["unit_id"] ?? 0,
            ]);
        }
        $t->product_id = $data["product_id"];
        $t->product_variant_name = $data["variant_name"];
        $t->product_variant_sku = $data["variant_sku"];
      //  $t->product_variant_price = $data["variant_price"]!=""? $data["variant_price"] : 0;
        $t->product_variant_barcode =  $data["variant_barcode"]!="" ? $data["variant_barcode"] : $t->generateBarcode();
        $t->product_variant_alert = $data["variant_alert"];
        $t->unit_id = $data["unit_id"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->product_variant_id;
    }

    public function deleteProductVariant($data)
    {
        $t = self::find($data["product_variant_id"]);
        if (!$t) {
            throw new \Exception("Product variant with ID " . $data["product_variant_id"] . " not found.");
        }
        $t->status = 0; // soft delete
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
    }
     function generateBarcode() {
       do {
        // Generate angka acak sebanyak 12 digit
            $barcode = (string) random_int(100000000000, 999999999999);
        } while (self::where('product_variant_barcode', $barcode)->exists());
        return $barcode;
    }
}