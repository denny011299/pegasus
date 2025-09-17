<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetail extends Model
{
    protected $table = "purchase_orders_details";
    protected $primaryKey = "pod_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPurchaseOrderDetail($data = []){
        $data = array_merge([
            "po_id"              => null,
            "supplies_variant_id"=> null,
        ], $data);

        $result = PurchaseOrderDetail::where("status", "=", 1);

        if ($data["po_id"]) {
            $result->where("po_id", "=" ,$data["po_id"]);
        }

        if ($data["supplies_variant_id"]) {
            $result->where("supplies_variant_id", "=" ,$data["supplies_variant_id"]);
        }

        $result->orderBy("created_at", "asc");
        $result = $result->get();

        foreach ($result as $key => $value) {
            // relasi ke SuppliesVariant
            $value->supplies_variant = SuppliesVariant::find($value->supplies_variant_id);
        }

        return $result;
    }

    function insertPurchaseOrderDetail($data){
        $t = new PurchaseOrderDetail();
        $t->po_id               = $data["po_id"];
        $t->supplies_variant_id = $data["supplies_variant_id"];
        $t->pod_nama            = $data["pod_nama"];
        $t->pod_variant         = $data["pod_variant"] ?? null;
        $t->pod_sku             = $data["pod_sku"] ?? null;
        $t->pod_harga           = $data["pod_harga"];
        $t->pod_qty             = $data["pod_qty"];
        $t->pod_subtotal        = $data["pod_subtotal"];
        $t->status              = 1;
        $t->save();

        return $t->po_id;
    }

    function updatePurchaseOrderDetail($data){
        $t = PurchaseOrderDetail::find($data["pod_id"]);
        $t->po_id               = $data["po_id"];
        $t->supplies_variant_id = $data["supplies_variant_id"];
        $t->pod_nama            = $data["pod_nama"];
        $t->pod_variant         = $data["pod_variant"] ?? null;
        $t->pod_sku             = $data["pod_sku"] ?? null;
        $t->pod_harga           = $data["pod_harga"];
        $t->pod_qty             = $data["pod_qty"];
        $t->pod_subtotal        = $data["pod_subtotal"];
        $t->save();

        return $t->po_id;
    }

    function deletePurchaseOrderDetail($data){
        $t = PurchaseOrderDetail::find($data["pod_id"]);
        $t->status = 0; // soft delete
        $t->save();
    }
}
