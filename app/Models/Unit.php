<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $table = "units";
    protected $primaryKey = "unit_id";
    public $timestamps = true;
    public $incrementing = true;

    function getUnit($data = [])
    {
        $data = array_merge([
            "unit_name"=>null,
            "unit_short_name"=>null
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["unit_short_name"]) $result->where('unit_short_name','like','%'.$data["unit_short_name"].'%');
        if($data["unit_name"]) $result->where('unit_name','like','%'.$data["unit_name"].'%');
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            //$value->product_count = Product::where('unit_id', $value->unit_id)->where('status','=',1)->count();
            $value->product_count = 0;
        }
        return $result;
    }

    function insertUnit($data)
    {
        $t = new self();
        $t->unit_short_name = $data["unit_short_name"];
        $t->unit_name = $data["unit_name"];
        $t->save();
        return $t->unit_id;
    }

    function updateUnit($data)
    {
        $t = self::find($data["unit_id"]);
        $t->unit_short_name = $data["unit_short_name"];
        $t->unit_name = $data["unit_name"];
        $t->save();
        return $t->unit_id;
    }

    function deleteUnit($data)
    {
        $t = self::find($data["unit_id"]);
        $t->status = 0;
        $t->save();
    }
}
