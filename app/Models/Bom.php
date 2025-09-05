<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bom extends Model
{
    protected $table = "boms";
    protected $primaryKey = "bom_id";
    public $timestamps = true;
    public $incrementing = true;

    function getBom($data = [])
    {

        $data = array_merge([
            "bom_id"=>null,
            "product_id"=>null,
        ], $data);

        $result = Bom::where('status', '=', 1);
        if($data["product_id"]) $result->where('product_id','=',$data["product_id"]);
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();

        foreach ($result as $key => $value) {
            $u = Product::find($value->product_id);
            $value->product_name = $u->product_name;

            $v = ProductVariant::where('product_id',"=", $value->product_id)->first();
            $value->product_sku = $v->product_variant_sku;
        }

        return $result;
    }

    function insertBom($data)
    {
        $t = new Bom();
        $t->product_id = $data["product_id"];
        $t->bom_qty = $data["bom_qty"];
        $t->save();
        return $t->bom_id;
    }

    function updateBom($data)
    {
        $t = Bom::find($data["bom_id"]);
        $t->product_id = $data["product_id"];
        $t->bom_qty = $data["bom_qty"];
        $t->save();
        return $t->bom_id;
    }

    function deleteBom($data)
    {
        $t = Bom::find($data["bom_id"]);
        $t->status = 0;
        $t->save();
    }
}
