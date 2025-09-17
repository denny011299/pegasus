<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BomDetail extends Model
{
    protected $table = "bom_details";
    protected $primaryKey = "bom_detail_id";
    public $timestamps = true;
    public $incrementing = true;

    function getBomDetail($data = [])
    {

        $data = array_merge([
            "bom_detail_id"=>null,
            "bom_id"=>null,
            "supplies_id"=>null,
            "unit_id"=>null,
        ], $data);

        $result = BomDetail::where('status', '=', 1);
        if($data["bom_detail_id"]) $result->where('bom_detail_id','=',$data["bom_detail_id"]);
        if($data["bom_id"]) $result->where('bom_id','=',$data["bom_id"]);
        if($data["supplies_id"]) $result->where('supplies_id','=',$data["supplies_id"]);
        if($data["unit_id"]) $result->where('unit_id','=',$data["unit_id"]);
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();

        foreach ($result as $key => $value) {
            $u = SuppliesVariant::find($value->supplies_id);
            $s = Supplies::find($u->supplies_id);
            $value->supplies_name = $s->supplies_name." ".$u->supplies_variant_name;

            $v = Unit::find($value->unit_id);
            $value->unit_name = $v->unit_name;
        }

        return $result;
    }

    function insertBomDetail($data)
    {
        $t = new BomDetail();
        $t->bom_id = $data["bom_id"];
        $t->supplies_id = $data["supplies_id"];
        $t->bom_detail_qty = $data["bom_detail_qty"];
        $t->unit_id = $data["unit_id"];
        $t->save();
        return $t->bom_detail_id;
    }

    function updateBomDetail($data)
    {
        $t = BomDetail::find($data["bom_detail_id"]);
        $t->bom_id = $data["bom_id"];
        $t->supplies_id = $data["supplies_id"];
        $t->bom_detail_qty = $data["bom_detail_qty"];
        $t->unit_id = $data["unit_id"];
        $t->save();
        return $t->bom_detail_id;
    }
}
