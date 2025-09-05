<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $table = "productions";
    protected $primaryKey = "production_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProduction($data = [])
    {
        $data = array_merge([
            "production_product_id"=>null
        ], $data);

        $result = Production::where('status', '=', 1);
        if($data["production_product_id"]) $result->where('production_product_id','=',$data["production_product_id"]);
        $result->orderBy('created_at', 'asc');
        
        $result = $result->get();
        foreach ($result as $key => $value) {
            $u = Product::find($value->production_product_id);
            $value->product_name = $u->product_name;
            $v = ProductVariant::where('product_id',"=", $value->production_product_id)->first();
            $value->product_sku = $v->product_variant_sku;
        }
        return $result;
    }

    function insertProduction($data)
    {
        $t = new Production();
        $t->production_date = $data["production_date"];
        $t->production_product_id = $data["production_product_id"];
        $t->production_qty = $data["production_qty"];
        $t->production_created_by = 0;
        $t->save();
        return $t->production_id;
    }

    function updateProduction($data)
    {
        $t = Production::find($data["production_id"]);
        $t->production_date = $data["production_date"];
        $t->production_product_id = $data["production_product_id"];
        $t->production_qty = $data["production_qty"];
        $t->production_created_by = 0;
        $t->save();
        return $t->production_id;
    }

    function deleteProduction($data)
    {
        $t = Production::find($data["production_id"]);
        $t->status = 0;
        $t->save();
    }
}
