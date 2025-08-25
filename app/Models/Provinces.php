<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provinces extends Model
{
    protected $table = "provinces";
    protected $primaryKey = "prov_id";
    public $timestamps = true;
    public $incrementing = true;

    function get_data($data=[]) {
         $data = array_merge(array(
            "prov_name" =>null,
            "city_name" =>null,
            "prov_id" =>null
        ), $data);
        
        $pc = Provinces::query();
        if($data["prov_name"]!=null) $pc->where("prov_name","like","%".$data["prov_name"]."%");
        if($data["prov_id"]) $pc->where('prov_id', '=', $data["prov_id"]);
        return [
            "data"=>$pc->get(),
            "count"=>$pc->count()
        ];
    }

}
