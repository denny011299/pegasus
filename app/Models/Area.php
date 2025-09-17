<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = "areas";
    protected $primaryKey = "area_id";
    public $timestamps = true;
    public $incrementing = true;

    function getArea($data = [])
    {

        $data = array_merge([
            "area_name"=>null,
            "area_code"=>null
        ], $data);

        $result = Area::where('status', '=', 1);
        if($data["area_code"]) $result->where('area_code','=',$data["area_code"]);
        if($data["area_name"]) $result->where('area_name','like','%'.$data["area_name"].'%');
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            
        }
        return $result;
    }

    function insertArea($data)
    {
        $t = new Area();
        $t->area_code = $data["area_code"];
        $t->area_name = $data["area_name"];
        $t->save();
        return $t->area_id;
    }

    function updateArea($data)
    {
        $t = Area::find($data["area_id"]);
        $t->area_code = $data["area_code"];
        $t->area_name = $data["area_name"];
        $t->save();
        return $t->area_id;
    }

    function deleteArea($data)
    {
        $t = Area::find($data["area_id"]);
        $t->status = 0;
        $t->save();
    }
}
