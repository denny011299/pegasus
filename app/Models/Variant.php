<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Variant extends Model
{
    protected $table = "variants";
    protected $primaryKey = "variant_id";
    public $timestamps = true;
    public $incrementing = true;

    function getVariant($data = [])
    {
        $data = array_merge([
            "variant_name"=>null,
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["variant_name"]) $result->where('variant_name','like','%'.$data["variant_name"].'%');
        $result->orderBy('created_at', 'asc');
       
        return $result->get();
    }

    function insertVariant($data)
    {
        $t = new self();
        $t->variant_name = $data["variant_name"];
        $t->variant_attribute = $data["variant_attribute"];
        $t->save();
        return $t->variant_id;
    }

    function updateVariant($data)
    {
        $t = self::find($data["variant_id"]);
        $t->variant_name = $data["variant_name"];
        $t->variant_attribute = $data["variant_attribute"];
        $t->save();
        return $t->pv_id;
    }

    function deleteVariant($data)
    {
        $t = self::find($data["variant_id"]);
        $t->status = 0;
        $t->save();
    }
}
