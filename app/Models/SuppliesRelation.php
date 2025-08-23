<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuppliesRelation extends Model
{
    protected $table = "supplies_relations";
    protected $primaryKey = "sr_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSuppliesRelation($data = []){
        $data = array_merge([
            "supplier_name"=>null,
            "supplier_code"=>null,
            "city_id"=>null,
        ], $data);
    }

    function insertSuppliesRelation($data){
        $t = new self();
        $t->su_id_1 = $data["su_id_1"];
        $t->su_id_2 = $data["su_id_2"];
        $t->sr_value_1 = $data["sr_value_1"];
        $t->sr_value_2 = $data["sr_value_2"];
        $t->save();
        return $t->sr_id;
    }

    function updateSuppliesRelation($data){
        $t = self::find($data["sr_id"]);
        $t->su_id_1 = $data["su_id_1"];
        $t->su_id_2 = $data["su_id_2"];
        $t->sr_value_1 = $data["sr_value_1"];
        $t->sr_value_2 = $data["sr_value_2"];
        $t->save();
        return $t->sr_id;
    }

    function deleteSuppliesRelation($data){
        $t = self::find($data["sr_id"]);
        $t->status = 0;
        $t->save();
    }
}
