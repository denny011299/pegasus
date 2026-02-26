<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnSupplies extends Model
{
    protected $table = "return_supplies";
    protected $primaryKey = "rs_id";
    public $timestamps = true;
    public $incrementing = true;

    function getReturnSupplies($data = [])
    {
        $data = array_merge([
            "supplier_id"=>null
        ], $data);

        $result = ReturnSupplies::where('status', '=', 1);
        if($data["supplier_id"]) $result->where('supplier_id', '=', $data["supplier_id"]);
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            $value->detail = (new ReturnSuppliesDetail())->getReturnSuppliesDetail(['rs_id' => $value->rs_id]);
        }
        return $result;
    }

    function insertReturnSupplies($data)
    {
        $t = new ReturnSupplies();
        $t->poi_id = $data["poi_id"];
        $t->pi_id = $data["pi_id"];
        $t->rs_date = $data["rs_date"];
        $t->rs_notes = $data["rs_notes"];
        $t->rs_total = $data["rs_total"];
        $t->save();
        return $t->rs_id;
    }

    function updateReturnSupplies($data)
    {
        $t = ReturnSupplies::find($data["rs_id"]);
        $t->poi_id = $data["poi_id"];
        $t->pi_id = $data["pi_id"];
        $t->rs_date = $data["rs_date"];
        $t->rs_notes = $data["rs_notes"];
        $t->rs_total = $data["rs_total"];
        $t->save();
        return $t->rs_id;
    }

    function deleteReturnSupplies($data)
    {
        $t = ReturnSupplies::find($data["rs_id"]);
        $t->status = 0;
        $t->save();
    }
}
