<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderDelivery extends Model
{
    protected $table = "sales_delivery_orders";
    protected $primaryKey = "sdo_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSoDelivery($data = [])
    {
        $data = array_merge([
            "sdo_number"   => null,
            "sdo_receiver"   => null,
            "sdo_id"   => null,
            "so_id"   => null,
            "sdo_date" => null,
        ], $data);

        $result = SalesOrderDelivery::where("status", ">=", 0);

        if ($data["sdo_receiver"]) $result->where("sdo_receiver", "like", "%" . $data["sdo_receiver"] . "%");
        if ($data["sdo_id"]) $result->where("sdo_id", "=", $data["sdo_id"]);
        if ($data["so_id"]) $result->where("so_id", "=", $data["so_id"]);

        $result->orderBy("created_at", "asc");
        $result = $result->get();

        foreach ($result as $key => $value) {
            $value->items = (new SalesOrderDeliveryDetail())->getSoDeliveryDetail(["sdo_id" => $value->sdo_id]);
        }

        return $result;
    }

    function insertSoDelivery($data)
    {
        $t = new SalesOrderDelivery();
        $t->sdo_number   = $this->generateSoDeliveryID();
        $t->sdo_receiver = $data["sdo_receiver"];
        // $t->staff_id     = $data["staff_id"];
        $t->so_id = $data["so_id"];
        $t->sdo_date     = $data["sdo_date"];
        $t->sdo_phone    = $data["sdo_phone"];
        $t->sdo_desc     = $data["sdo_desc"];
        $t->save();

        return $t->sdo_id;
    }

    function updateSoDelivery($data)
    {
        $t = SalesOrderDelivery::find($data["sdo_id"]);
        $t->sdo_receiver = $data["sdo_receiver"];
        // $t->staff_id     = $data["staff_id"];
        $t->sdo_date     = $data["sdo_date"];
        $t->sdo_phone    = $data["sdo_phone"];
        $t->sdo_desc     = $data["sdo_desc"];
        $t->save();

        return $t->sdo_id;
    }

    function deleteSoDelivery($data)
    {
        $t = SalesOrderDelivery::find($data["sdo_id"]);
        $t->status = -1; // soft delete
        $t->save();
        if($t->status==2){
            $p = SalesOrderDeliveryDetail::where("sdo_id", "=", $data["sdo_id"])->get();;
            foreach ($p as $key => $value) {
                $s = ProductVariant::find($value->product_variant_id);
                $s->product_variant_stock -= $value->pdod_qty;
                $s->save();
            }
        }
    }

    function statusSoDelivery($data)
    {
        $t = SalesOrderDelivery::find($data["sdo_id"]);
        $t->status = $data["status"]; // soft delete
        $t->save();
    }

    function generateSoDeliveryID()
    {
        $id = self::max('sdo_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "SDO" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
