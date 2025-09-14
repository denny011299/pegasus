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
            "supplies_id"=>null,
            "supplies_name"=>null,
            "supplies_desc"=>null,
        ], $data);
        $result = Supplies::where('status', '=', 1);
        if($data["supplies_id"]) $result->where('supplies_id','=', $data["supplies_id"]);
        if($data["supplies_name"]) $result->where('supplies_name','like','%'.$data["supplies_name"].'%');
        if($data["supplies_desc"]) $result->where('supplies_desc','like','%'.$data["supplies_desc"].'%');
        $result->orderBy('created_at', 'asc');
        
        $result = $result->get();
        foreach ($result as $key => $value) {
            $value->sup_variant = (new SuppliesVariant())->getSuppliesVariant([
                "supplies_id" => $value->supplies_id
            ]);
        }
        return $result;
    }

    function insertSupplies($data)
    {
        $t = new Supplies();
        $t->supplies_name = $data["supplies_name"];
        $t->supplies_desc = $data["supplies_desc"];
        $t->save();
        return $t->supplies_id;
    }

    function updateSupplies($data)
    {
        $t = Supplies::find($data["supplies_id"]);
        $t->supplies_name = $data["supplies_name"];
        $t->supplies_desc = $data["supplies_desc"];
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
