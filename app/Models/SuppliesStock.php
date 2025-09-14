<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuppliesStock extends Model
{
    protected $table = "supplies_stocks";
    protected $primaryKey = "ss_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSuppliesStock($data = []){
        $data = array_merge([
            "supplies_id"=>null,
            "supplies_variant_id"=>null
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["supplies_id"]) $result->where('supplies_id','=',$data["supplies_id"]);
        if($data["supplies_variant_id"]) $result->where('supplies_variant_id','=',$data["supplies_variant_id"]);
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {

        }
        return $result;
    }

    function insertSuppliesStock($data)
    {
        $t = new self();
        $t->supplies_id = $data["supplies_id"];
        $t->supplies_variant_id = $data["supplies_variant_id"];
        $t->ss_stock = $data["ss_stock"] ?? 0;
        $t->save();

        return $t->role_id;
    }

    function updateSuppliesStock($data)
    {
        $t = self::find($data["ss_id"]);
        $t->supplies_id = $data["supplies_id"];
        $t->supplies_variant_id = $data["supplies_variant_id"];
        $t->ss_stock = $data["ss_stock"];
        $t->save();
        return $t->role_id;
    }

    function deleteSuppliesStock($data)
    {
        $t = self::find($data["ss_id"]);
        $t->status = 0;
        $t->save();
    }
}
