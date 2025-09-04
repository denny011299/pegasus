<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuppliesUnit extends Model
{
    protected $table = "supplies_units";
    protected $primaryKey = "su_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSuppliesUnit($data=[]) {
         $data = array_merge([
            "supplies_id"=>null,
        ], $data);

        $result = SuppliesUnit::where('status', '=', 1);
        if($data["supplies_id"]) $result->where('supplies_id', '=', $data["supplies_id"]);
        $result->orderBy('created_at', 'asc');
        $result = $result->get();
        foreach ($result as $key => $value) {
            $u = Unit::find($value->unit_id);
            $value->unit_name = $u->unit_name;
        }
        return $result;
    }

    function insertSuppliesUnit($data){
        $t = new self();
        $t->unit_id = $data["unit_id"];
        $t->supplies_id = $data["supplies_id"];
        $t->save();
        return $t->su_id;
    }

    function updateSuppliesUnit($data){
        $t = self::find($data["su_id"]);
        $t->unit_id = $data["unit_id"];
        $t->supplies_id = $data["supplies_id"];
        $t->save();
        return $t->su_id;
    }

    function deleteSuppliesUnit($data){
        $t = self::find($data["su_id"]);
        $t->status = 0;
        $t->save();
    }
}
