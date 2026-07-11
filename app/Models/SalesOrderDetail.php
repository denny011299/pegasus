<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderDetail extends Model
{
    protected $table = "sales_order_details";
    protected $primaryKey = "sod_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSalesOrderDetail($data = []){
        $data = array_merge([
            "so_id" => null,
            "product_variant_id" => null,
        ], $data);

        $result = SalesOrderDetail::where("status", "=", 1);
        if ($data["so_id"]) {
            $result->where("so_id", "=" ,$data["so_id"]);
        }
            
        if ($data["product_variant_id"]) {
            $result->where("product_variant_id", "=" ,$data["product_variant_id"]);
        }
        
        $result->orderBy("created_at", "asc");
        $result = $result->get();

        return $this->enrichDetailsCollection($result);
    }

    public function enrichDetailsCollection($details)
    {
        $details = collect($details);
        if ($details->isEmpty()) {
            return $details;
        }

        $variants = ProductVariant::whereIn(
            'product_variant_id',
            $details->pluck('product_variant_id')->filter()->unique()->values()->all()
        )->get()->keyBy('product_variant_id');

        $products = Product::whereIn(
            'product_id',
            $variants->pluck('product_id')->filter()->unique()->values()->all()
        )->get()->keyBy('product_id');

        $unitIdSet = [];
        foreach ($details as $detail) {
            if ($detail->unit_id) {
                $unitIdSet[(int) $detail->unit_id] = true;
            }
        }
        foreach ($products as $product) {
            foreach ((array) (json_decode($product->product_unit, true) ?: []) as $unitId) {
                $unitIdSet[(int) $unitId] = true;
            }
        }

        $unitsMap = $unitIdSet !== []
            ? Unit::whereIn('unit_id', array_keys($unitIdSet))->get()->keyBy('unit_id')
            : collect();

        foreach ($details as $value) {
            $variant = $variants->get($value->product_variant_id);
            $product = $variant ? $products->get($variant->product_id) : null;
            $unit = $unitsMap->get($value->unit_id);
            $value->unit_name = $unit ? $unit->unit_name : null;

            $unitIds = $product ? (json_decode($product->product_unit, true) ?: []) : [];
            $value->units = $unitIds;
            $value->pr_unit = collect($unitIds)
                ->map(fn ($id) => $unitsMap->get((int) $id))
                ->filter()
                ->values();
        }

        return $details;
    }

    function insertSalesOrderDetail($data){
        $t = new SalesOrderDetail();
        $t->so_id = $data["so_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->sod_nama = $data["pr_name"] ?? $data["product_name"];
        $t->sod_variant = $data["product_variant_name"];
        $t->sod_sku = $data["product_variant_sku"];
        $t->unit_id = $data["unit_id"];
        $t->sod_harga = $data["product_variant_price"];
        $t->sod_qty = $data["so_qty"];
        $t->sod_subtotal = $data["so_subtotal"];
        $t->save();

        return $t->sod_id;
    }

    function updateSalesOrderDetail($data){

        $t = SalesOrderDetail::find($data["sod_id"]);
        $t->so_id = $data["so_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->sod_nama = $data["product_name"];
        $t->sod_variant = $data["product_variant_name"];
        $t->sod_sku = $data["product_variant_sku"];
        $t->unit_id = $data["unit_id"];
        $t->sod_harga = $data["product_variant_price"];
        $t->sod_qty = $data["so_qty"];
        $t->sod_subtotal = $data["so_subtotal"];
        $t->save();

        return $t->sod_id;
    }

    function deleteSalesOrderDetail($data){
        $t = SalesOrderDetail::find($data["sod_id"]);
        $t->status = 1; // soft delete
        $t->save();
        
        // $m = ProductVariant::find($t->product_variant_id);
        // $s = ProductStock::where('product_variant_id',$m->product_variant_id)->where('unit_id',$t->unit_id)->first();

        // $s->ps_stock += $t->sod_qty;
        // $s->save();
        return 1;
    }
}
