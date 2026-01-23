<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use function PHPUnit\Framework\isArray;

class PurchaseOrderDetail extends Model
{
    protected $table = "purchase_orders_details";
    protected $primaryKey = "pod_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPurchaseOrderDetail($data = [])
    {
        $data = array_merge([
            "po_id"              => null,
            "supplies_variant_id" => null,
            "suppliesIds" => null,
        ], $data);

        $result = PurchaseOrderDetail::where("status", "=", 1);

        if ($data["po_id"]) {
            $result->where("po_id", "=", $data["po_id"]);
        }

        if ($data["supplies_variant_id"]) {
            $result->where("supplies_variant_id", "=", $data["supplies_variant_id"]);
        }

        if ($data["suppliesIds"] && is_array($data["suppliesIds"])) {
            // Ambil po_id yang valid
            $poIds = PurchaseOrderDetail::where('status', 1)
                ->whereIn('supplies_variant_id', $data['suppliesIds'])
                ->groupBy('po_id')
                ->havingRaw(
                    'COUNT(DISTINCT supplies_variant_id) = ?',
                    [count($data['suppliesIds'])]
                )
                ->pluck('po_id');
            
            // Ambil data
            $result->whereIn('po_id', $poIds);
        }

        $result->orderBy("created_at", "asc");
        $result = $result->get();

        foreach ($result as $key => $value) {
            $value->po_number = PurchaseOrder::find($value->po_id)->first();
            // relasi ke SuppliesVariant
            $value->supplies_variant = SuppliesVariant::find($value->supplies_variant_id);
            $u = Unit::find($value->unit_id);
            $value->unit_name = $u ? $u->unit_short_name : null;
        }

        return $result;
    }

    function insertPurchaseOrderDetail($data)
    {
        $t = new self();
        $t->po_id               = $data["po_id"];
        $t->supplies_variant_id = $data["supplies_variant_id"];
        $t->pod_nama            = $data["pod_nama"];
        $t->pod_variant         = $data["pod_variant"] ?? null;
        $t->pod_sku             = $data["pod_sku"] ?? null;
        $t->pod_harga           = $data["pod_harga"];
        $t->pod_qty             = $data["pod_qty"];
        $t->pod_subtotal        = $data["pod_subtotal"];
        $t->unit_id        = $data["unit_id_select"];
        $t->status              = 1;
        $t->save();

        return $t->po_id;
    }

    function updatePurchaseOrderDetail($data)
    {
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

    function deletePurchaseOrderDetail($data)
    {
        $t = PurchaseOrderDetail::find($data["pod_id"]);
        $t->status = 0; // soft delete
        $t->save();
    }
}
