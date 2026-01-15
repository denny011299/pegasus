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
        $result= $result->get();
        foreach ($result as $key => $value) {
            $pv = ProductVariant::find($value->product_variant_id);
            $p = Product::find($pv->product_id);
            $u = Unit::find($p->unit_id);
            $value->unit_id = $u->unit_id;
            $value->unit_name = $u->unit_name;

            $value->units = json_decode($p->product_unit);
            $value->pr_unit = Unit::whereIn('unit_id', $value->units)->get();
        }
        return $result;
    }

    function insertSalesOrderDetail($data){
        $t = new SalesOrderDetail();
        $t->so_id = $data["so_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->sod_nama = $data["pr_name"];
        $t->sod_variant = $data["product_variant_name"];
        $t->sod_sku = $data["product_variant_sku"];
        $t->unit_id = $data["unit_id"];
        $t->sod_harga = $data["product_variant_price"];
        $t->sod_qty = $data["so_qty"];
        $t->sod_subtotal = $data["so_subtotal"];
        $t->save();

        $s = ProductVariant::find($data["product_variant_id"]);
         $s = ProductStock::where("product_variant_id", "=", $s->product_variant_id)
            ->where("unit_id", "=", $data["unit_id"])
            ->where("status", "=", 1)
            ->first();
        $s->ps_stock -= $data["so_qty"];
        $s->save();

        return $t->sod_id;
    }

    function updateSalesOrderDetail($data){

        $t = SalesOrderDetail::find($data["sod_id"]);
        $s = ProductVariant::find($data["product_variant_id"]);
         $s = ProductStock::where("product_variant_id", "=", $s->product_variant_id)
            ->where("unit_id", "=", $data["unit_id"])
            ->where("status", "=", 1)
            ->first();
        $s->ps_stock += $t["sod_qty"];
        $s->ps_stock -= $data["so_qty"];
        $s->save();


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
        $t->status = 0; // soft delete
        $t->save();
        
        $m = ProductVariant::find($t->product_variant_id);
        $s = ProductStock::where('product_variant_id',$m->product_variant_id)->where('unit_id',$t->unit_id)->first();

        $s->ps_stock += $t->sod_qty;
        $s->save();
        return $m;
    }
}
