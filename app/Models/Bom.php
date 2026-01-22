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
            "search" => null,
            "product_id" => null,
        ], $data);

        $result = Bom::where('boms.status', '=', 1)
          ->join('product_variants', 'product_variants.product_variant_id', '=', 'boms.product_id')
            ->join('products', 'products.product_id', '=', 'product_variants.product_id')
            ->select('boms.*');

        if ($data["product_id"]) $result->where('boms.product_id', '=', $data["product_id"]);
        if ($data["bom_id"]) $result->where('boms.bom_id', '=', $data["bom_id"]);

        if ($data['search']) {
            $s = $data['search'];
             $result->where(function ($q) use ($s) {
                $q->whereRaw("CONCAT(products.product_name, ' ', product_variants.product_variant_name) LIKE ?", ["%{$s}%"])
                ->orWhere("product_variants.product_variant_sku", "LIKE", "%{$s}%");
            });
        }

        $result->orderBy('created_at', 'asc');

        $result = $result->get();

        foreach ($result as $key => $value) {
            $v = ProductVariant::find($value->product_id);
            $value->product_sku = $v->product_variant_sku;
            $value->product_variant_id = $v->product_variant_id;
            $u = Product::find($v->product_id);
            $value->product_name =  $u->product_name . " " . $v->product_variant_name;
            $value->product_variant_sku = $v->product_variant_sku;
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
