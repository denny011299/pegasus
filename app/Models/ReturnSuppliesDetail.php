<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnSuppliesDetail extends Model
{
    protected $table = "return_supplies_detail";
    protected $primaryKey = "rsd_id";
    public $timestamps = true;
    public $incrementing = true;

    function getReturnSuppliesDetail($data = [])
    {

        $data = array_merge([
            "rs_id"=>null
        ], $data);

        $result = ReturnSuppliesDetail::where('status', '=', 1);
        if($data["rs_id"]) $result->where('rs_id', '=', $data["rs_id"]);
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            $svr = SuppliesVariant::find($value->supplies_variant_id);
            $value->supplies_variant_name = $svr->supplies_variant_name;
            $u = Unit::find($value->unit_id);
            $value->unit_name = $u->unit_name;
        }
        return $result;
    }

    function insertReturnSuppliesDetail($data)
    {
        $t = new ReturnSuppliesDetail();
        $t->rs_id = $data["rs_id"];
        $t->supplies_variant_id = $data["supplies_variant_id"];
        $t->rsd_qty = $data["rsd_qty"];
        $t->rsd_price = $data["rsd_price"];
        $t->unit_id = $data["unit_id"];
        $t->save();
        return $t->rsd_id;
    }

    function updateReturnSuppliesDetail($data)
    {
        $t = ReturnSuppliesDetail::find($data["rsd_id"]);
        $t->rs_id = $data["rs_id"];
        $t->supplies_variant_id = $data["supplies_variant_id"];
        $t->rsd_qty = $data["rsd_qty"];
        $t->rsd_price = $data["rsd_price"];
        $t->unit_id = $data["unit_id"];
        $t->save();
        return $t->rsd_id;
    }

    function deleteReturnSuppliesDetail($data)
    {
        $t = ReturnSuppliesDetail::find($data["rsd_id"]);
        $t->status = 0;
        $t->save();
    }
}
