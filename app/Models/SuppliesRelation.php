<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuppliesRelation extends Model
{
    protected $table = "supplies_relations";
    protected $primaryKey = "sr_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSuppliesRelation($data = [])
    {
        $data = array_merge([
            "supplies_id" => null
        ], $data);

        $result = self::where('status', '=', 1);
        if ($data["supplies_id"]) $result->where('supplies_id', '=', $data["supplies_id"]);
        $result->orderBy('created_at', 'asc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            $u1 = Unit::find($value->su_id_1);
            $u2 = Unit::find($value->su_id_2);
            $value->pr_unit_id_1 = $u1->unit_id;
            $value->pr_unit_id_2 = $u2->unit_id;
            $value->pr_unit_name_1 = $u1->unit_short_name;
            $value->pr_unit_name_2 = $u2->unit_short_name;
        }
        return $result;
    }

    function insertSuppliesRelation($data)
    {
        $t = new self();
        $t->supplies_id = $data["supplies_id"];
        $t->su_id_1 = $data["sr_unit_id_1"];
        $t->su_id_2 = $data["sr_unit_id_2"];
        $t->sr_value_1 = 1;
        $t->sr_value_2 = $data["unit_value_2"];
        $t->save();
        return $t->sr_id;
    }

    function updateSuppliesRelation($data)
    {
       
        $t = self::find($data["sr_id"]);
        $t->su_id_1 = $data["sr_unit_id_1"];
        $t->su_id_2 = $data["sr_unit_id_2"];
        $t->sr_value_1 = 1;
        $t->sr_value_2 = $data["unit_value_2"];
        $t->save();
        return $t->sr_id;
    }

    function deleteSuppliesRelation($data)
    {
        $t = self::find($data["sr_id"]);
        $t->status = 0;
        $t->save();
    }
}
