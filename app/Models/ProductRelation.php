<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductRelation extends Model
{
    protected $table = "product_relations";
    protected $primaryKey = "pr_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProductRelation($data = []){
        $data = array_merge([
            "product_id"=>null
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["product_id"]) $result->where('product_id','=',$data["product_id"]);
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            $u1 = Unit::find($value->pr_unit_id_1);
            $u2 = Unit::find($value->pr_unit_id_2);
            $value->pr_unit_name_1 = $u1->unit_short_name;
            $value->pr_unit_name_2 = $u2->unit_short_name;
        }
        return $result;
    }

    function insertProductRelation($data)
    {
        $t = new self();
        $t->product_id = $data["product_id"];
        $t->pr_unit_id_1 = $data["unit_id_1"];
        $t->pr_unit_value_1 = $data["unit_value_1"];
        $t->pr_unit_id_2 = $data["unit_id_2"];
        $t->pr_unit_value_2 = $data["unit_value_2"];
        $t->save();
        return $t->pr_id;
    }

    function updateProductRelation($data)
    {
        $t = self::find($data["pr_id"]);
        $t->product_id = $data["product_id"];
        $t->pr_unit_id_1 = $data["unit_id_1"];
        $t->pr_unit_value_1 = $data["unit_value_1"];
        $t->pr_unit_id_2 = $data["unit_id_2"];
        $t->pr_unit_value_2 = $data["unit_value_2"];
        $t->save();
        return $t->pr_id;
    }

    function deleteProductRelation($data)
    {
        $t = self::find($data["pr_id"]);
        $t->status = 0;
        $t->save();
    }
}
