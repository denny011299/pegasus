<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = "disticts";
    protected $primaryKey = "id";
    public $timestamps = true;
    public $incrementing = true;

    function getDistrict($data=[]) {
         $data = array_merge(array(
            "id" =>null,
            "city_id" =>null,
        ), $data);
        
        $pc = District::where('id','=',$data["prov_id"]);
        if($data["prov_id"]) $pc->where('prov_id', '=', $data["prov_id"]);
        if($data["prov_id"]) $pc->where('prov_id', '=', $data["prov_id"]);
        return [
            "data"=>$pc->get(),
            "count"=>$pc->count()
        ];
    }
}
