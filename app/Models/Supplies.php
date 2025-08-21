<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplies extends Model
{
    protected $table = "supplies";
    protected $primaryKey = "supplies_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSupplies($data = []){
        $data = array_merge([
            "supplies_name"=>null,
            "supplies_desc"=>null,
            "supplies_unit"=>null,
            "supplies_stock"=>null,
        ], $data);

        $result = Supplies::where('status', '=', 1);
        if($data["supplies_name"]) $result->where('supplies_name','like','%'.$data["supplies_name"].'%');
        if($data["supplies_desc"]) $result->where('supplies_desc','like','%'.$data["supplies_desc"].'%');
        if($data["supplies_unit"]) $result->where('supplies_unit','like','%'.$data["supplies_unit"].'%');
        if($data["supplies_stock"]) $result->where('supplies_stock', '=', $data["supplies_stock"]);
        $result->orderBy('created_at', 'asc');
        
        $result = $result->get();
        return $result;
    }

    function insertSupplies($data)
    {
        $t = new Supplies();
        $t->supplies_name = $data["supplies_name"];
        $t->supplies_desc = $data["supplies_desc"];
        $t->supplies_unit = $data["supplies_unit"];
        $t->supplies_stock = $data["supplies_stock"];
        $t->save();
        return $t->supplies_id;
    }

    function updateSupplies($data)
    {
        $t = Supplies::find($data["supplies_id"]);
        $t->supplies_name = $data["supplies_name"];
        $t->supplies_desc = $data["supplies_desc"];
        $t->supplies_unit = $data["supplies_unit"];
        $t->supplies_stock = $data["supplies_stock"];
        $t->save();
        return $t->supplies_id;
    }

    function deleteSupplies($data)
    {
        $t = Supplies::find($data["supplies_id"]);
        $t->status = 0;
        $t->save();
    }
}
