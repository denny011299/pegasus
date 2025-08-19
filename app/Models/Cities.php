<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cities extends Model
{
    protected $table = "cities";
    protected $primaryKey = "city_id";
    public $timestamps = true;
    public $incrementing = true;

    function get_data_simple_city($data=[]) {
         $data = array_merge(array(
            "prov_id" =>null,
            "city_name" =>null,
        ), $data);
        
        $pc = Cities::where('prov_id','=',$data["prov_id"]);
        if($data["city_name"]!=null) $pc->where("city_name","like","%".$data["city_name"]."%");
        return [
            "data"=>$pc->get(),
            "count"=>$pc->count()
        ];
    }
}
