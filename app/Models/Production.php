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
            $u = ProductVariant::find($value->production_product_id);
            $value->product_sku = $u->product_variant_sku;
            $v = Product::find($u->product_id);
            $value->product_name = $v->product_name;
        }
        return $result;
    }

    function insertProduction($data)
    {
        $t = new Production();
        $t->production_date = $data["production_date"];
        $t->production_bom_id = $data["production_bom_id"];
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
        $t->production_bom_id = $data["production_bom_id"];
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

        $b = (new BomDetail())->getBomDetail([
            "bom_id" => $t->production_bom_id
        ]);

        foreach ($b as $key => $value) {
            $s = Supplies::find($value->supplies_id);
            $s->supplies_stock +=  ($value->bom_detail_qty * $t->production_qty);
            $s->save();
        }
    }
}
