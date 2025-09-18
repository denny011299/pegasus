<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $table = "districts";
    protected $primaryKey = "id";
    public $timestamps = true;
    public $incrementing = true;

    function getDistrict($data=[]) {
         $data = array_merge(array(
            "id" =>null,
            "city_id" =>null,
        ), $data);
        $pc = District::where('city_id','=',$data["city_id"]);
        if($data["id"]) $pc->where('id', '=', $data["id"]);
        if($data["city_id"]) $pc->where('city_id', '=', $data["city_id"]);
        return [
            "data"=>$pc->get(),
            "count"=>$pc->count()
        ];
    }
}
