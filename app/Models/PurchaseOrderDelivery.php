<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDelivery extends Model
{
    protected $table = "purchase_delivery_orders";
    protected $primaryKey = "pdo_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPoDelivery($data = [])
    {
        $data = array_merge([
            "pdo_number"   => null,
            "pdo_receiver"   => null,
            "pdo_id"   => null,
            "pdo_date" => null,
        ], $data);

        $result = PurchaseOrderDelivery::where("status", ">=", 1);

        if ($data["pdo_receiver"]) $result->where("pdo_receiver", "like", "%" . $data["pdo_receiver"] . "%");
        if ($data["pdo_id"]) $result->where("pdo_id", "=", $data["pdo_id"]);

        $result->orderBy("created_at", "asc");
        $result = $result->get();

        foreach ($result as $key => $value) {
            $value->items = (new PurchaseOrderDeliveryDetail())->getPoDeliveryDetail(["pdo_id" => $value->pdo_id]);
        }

        return $result;
    }

    function insertPoDelivery($data)
    {
        $t = new PurchaseOrderDelivery();
        $t->pdo_number   = $this->generatePoDeliveryID();
        $t->pdo_receiver = $data["pdo_receiver"];
        $t->pdo_date     = $data["pdo_date"];
        $t->pdo_phone    = $data["pdo_phone"];
        $t->pdo_address  = $data["pdo_address"];
        $t->pdo_desc     = $data["pdo_desc"];
        $t->save();

        return $t->pdo_id;
    }

    function updatePoDelivery($data)
    {
        $t = PurchaseOrderDelivery::find($data["pdo_id"]);
        $t->pdo_receiver = $data["pdo_receiver"];
        $t->pdo_date     = $data["pdo_date"];
        $t->pdo_phone    = $data["pdo_phone"];
        $t->pdo_address  = $data["pdo_address"];
        $t->pdo_desc     = $data["pdo_desc"];
        $t->save();

        return $t->pdo_id;
    }

    function deletePoDelivery($data)
    {
        $t = PurchaseOrderDelivery::find($data["pdo_id"]);
        $t->status = 0; // soft delete
        $t->save();

        $p = PurchaseOrderDeliveryDetail::where("pdo_id", "=", $data["pdo_id"])->get();;
        foreach ($p as $key => $value) {
            $s = SuppliesVariant::find($value->supplies_variant_id);
            $s->supplies_variant_stock -= $value->pdod_qty;
            $s->save();
        }
    }

    function generatePoDeliveryID()
    {
        $id = self::max('pdo_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "PDO" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
