<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDeliveryDetail extends Model
{
    protected $table = "purchase_delivery_orders_details";
    protected $primaryKey = "pdod_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPoDeliveryDetail($data = [])
    {
        $data = array_merge([
            "pdo_id"   => null,
            "pdod_id"   => null,
        ], $data);

        $result = PurchaseOrderDeliveryDetail::where("status", ">=", 1);

        if ($data["pdo_id"]) $result->where("pdo_id", "=", $data["pdo_id"]);
        if ($data["pdod_id"]) $result->where("pdod_id", "=", $data["pdod_id"]);

        $result->orderBy("created_at", "asc");
        $result = $result->get();

        foreach ($result as $key => $value) {
            dd($value);
            $sv = SuppliesVariant::find($value->supplies_variant_id);
            $s = Supplies::find($sv->supplies_id);
            $value->supplies_name = $s->supplies_name;
            $value->supplies_variant_name = $sv->supplies_variant_name;
        }

        return $result;
    }

    function insertPoDeliveryDetail($data)
    {
        $t = new PurchaseOrderDeliveryDetail();
        $t->pdo_id = $data["pdo_id"];
        $t->supplies_variant_id = $data["supplies_variant_id"];
        $t->pdod_sku = $data["pdod_sku"];
        $t->pdod_qty = $data["pdod_qty"];
        $t->save();

        $s = SuppliesVariant::find($data["supplies_variant_id"]);
        $s->supplies_variant_stock += $data["pdod_qty"];
        $s->save();

        return $t->pdod_id;
    }

    function updatePoDeliveryDetail($data)
    {
        $s = SuppliesVariant::find($data["supplies_variant_id"]);

        $t = PurchaseOrderDeliveryDetail::find($data["pdod_id"]);

        $s->supplies_variant_stock -= $t->pdod_qty;
        $s->save();


        $t->supplies_variant_id = $data["supplies_variant_id"];
        $t->pdod_sku = $data["pdod_sku"];
        $t->pdod_qty = $data["pdod_qty"];
        $t->save();

        //ditambah lagi
        $s->supplies_variant_stock += $data["pdod_qty"];
        $s->save();
        return $t->pdo_id;
    }

    function deletePoDeliveryDetail($data)
    {
        $t = PurchaseOrderDeliveryDetail::find($data["pdod_id"]);
        $t->status = 0; // soft delete
        $t->save();

        $s = SuppliesVariant::find($t->supplies_variant_id);
        $s->supplies_variant_stock -= $t->pdod_qty;
        $s->save();
    }
}
