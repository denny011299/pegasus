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
            "bom_id" => null,
            "product_id" => null,
        ], $data);

        $result = Bom::where('status', '=', 1);
        if ($data["product_id"]) $result->where('product_id', '=', $data["product_id"]);
        if ($data["bom_id"]) $result->where('bom_id', '=', $data["bom_id"]);
        $result->orderBy('created_at', 'asc');

        $result = $result->get();

        foreach ($result as $key => $value) {
            $v = ProductVariant::find($value->product_id);
            $value->product_sku = $v->product_variant_sku;
            $u = Product::find($v->product_id);
            $value->product_name = $u->product_name;
            $value->unit_name = Unit::find($value->unit_id)->unit_short_name;
            $value->pr_unit = Unit::whereIn('unit_id', json_decode($u->product_unit, true))->get();
        }

        return $result;
    }

    function insertBom($data)
    {
        $t = new Bom();
        $t->product_id = $data["product_id"];
        $t->bom_qty = $data["bom_qty"];
        $t->unit_id = $data["unit_id"];
        $t->save();
        return $t->bom_id;
    }

    function updateBom($data)
    {
        $t = Bom::find($data["bom_id"]);
        $t->product_id = $data["product_id"];
        $t->bom_qty = $data["bom_qty"];
        $t->unit_id = $data["unit_id"];
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
