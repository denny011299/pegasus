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
            $value->product_variant = ProductVariant::where('product_variant_id','=', $value->product_variant_id)->get();
        }
        return $result;
    }

    function insertSalesOrderDetail($data){
        $t = new SalesOrderDetail();
        $t->so_id = $data["so_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->sod_nama = $data["product_name"];
        $t->sod_variant = $data["product_variant_name"];
        $t->sod_sku = $data["product_variant_sku"];
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
        $t->sod_nama = $data["sod_nama"];
        $t->sod_variant = $data["sod_variant"];
        $t->sod_sku = $data["sod_sku"];
        $t->sod_harga = $data["sod_harga"];
        $t->sod_qty = $data["sod_qty"];
        $t->sod_subtotal = $data["sod_subtotal"];
        $t->save();

        return $t->sod_id;
    }

    function deleteSalesOrderDetail($data){
        $t = SalesOrderDetail::find($data["sod_id"]);
        $t->status = 0; // soft delete
        $t->save();
    }
}
