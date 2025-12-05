<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderDeliveryDetail extends Model
{
    protected $table = "sales_delivery_orders_details";
    protected $primaryKey = "sdod_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSoDeliveryDetail($data = [])
    {
        $data = array_merge([
            "sdo_id"   => null,
            "sdod_id"   => null,
        ], $data);

        $result = SalesOrderDeliveryDetail::where("status", ">=", 1);

        if ($data["sdo_id"]) $result->where("sdo_id", "=", $data["sdo_id"]);
        if ($data["sdod_id"]) $result->where("sdod_id", "=", $data["sdod_id"]);

        $result->orderBy("created_at", "asc");
        $result = $result->get();

        foreach ($result as $key => $value) {

            $sv = ProductVariant::find($value->product_variant_id);
            $s = Product::find($sv->product_id);
            $value->product_name = $s->product_name;
            $value->product_variant_name = $sv->product_variant_name;
        }

        return $result;
    }

    function insertSoDeliveryDetail($data)
    {
        $t = new SalesOrderDeliveryDetail();
        $t->sdo_id = $data["sdo_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->sdod_sku = $data["sdod_sku"];
        $t->sdod_qty = $data["sdod_qty"];
        $t->save();
        if(isset($data["statusSO"])&&$data["statusSO"]==2){
            $s = ProductVariant::find($data["product_variant_id"]);
            $s = ProductStock::where("product_id", "=", $s->product_id)
                ->where("unit_id", "=", $data["unit_id"])
                ->where("status", "=", 1)
                ->first();
            $s->ps_stock += $data["sdod_qty"];
            $s->save();

            
        }
        return $t->sdod_id;
    }

    function updateSoDeliveryDetail($data)
    {
        $s = ProductVariant::find($data["product_variant_id"]);
        $st = ProductStock::where("product_id", "=", $s->product_id)
            ->where("unit_id", "=", $data["unit_id"])
            ->where("status", "=", 1)
            ->first();

        $t = SalesOrderDeliveryDetail::find($data["sdod_id"]);
        if($data["statusSO"]==2){
            $st->ps_stock -= $t->sdod_qty;
            $st->save();

            $t->product_variant_id = $data["product_variant_id"];
            $t->sdod_sku = $data["sku"];
            $t->sdod_qty = $data["sdod_qty"];
            $t->save();

            //ditambah lagi
            $st->ps_stock += $data["sdod_qty"];
            $st->save();
        }
        return $t->sdod_id;
    }

    function deleteSoDeliveryDetail($data)
    {
        $t = SalesOrderDeliveryDetail::find($data["sdod_id"]);
        $t->status = 0; // soft delete
        $t->save();

        $s = ProductVariant::find($t->product_variant_id);
        $s->product_variant_stock -= $t->sdod_qty;
        $s->save();
    }
}
