<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = "products";
    protected $primaryKey = "product_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProduct($data = [])
    {
        $data = array_merge([
            "product_name" => null,
            "category_id"  => null
        ], $data);

        $result = Product::where("status", "=", 1);

        if ($data["product_name"]) {
            $result->where("product_name", "like", "%".$data["product_name"]."%");
        }

        if ($data["category_id"]) {
            $result->where("category_id", "=", $data["category_id"]);
        }

        $result->orderBy("created_at", "asc");
        $result= $result->get();
        foreach ($result as $key => $value) {
            $value->product_unit = json_decode($value->product_unit);
            $value->pr_unit = Unit::whereIn('unit_id', $value->product_unit)->get();
            $value->pr_variant = ProductVariant::where('product_id','=', $value->product_id)->get();
            $value->product_category = Category::find($value->category_id)->category_name ?? "-";
        }
        return $result;
    }

    function insertProduct($data)
    {
        $t = new Product();
        $t->product_name = $data["product_name"];
        $t->category_id  = $data["category_id"];
        $t->product_unit = $data["product_unit"];
        $t->status       = $data["status"] ?? 1;
        $t->save();

        return $t->product_id;
    }

    function updateProduct($data)
    {
        $t = Product::find($data["product_id"]);
        $t->product_name = $data["product_name"];
        $t->category_id  = $data["category_id"];
        $t->product_unit = $data["product_unit"];
        $t->status       = $data["status"];
        $t->save();

        return $t->product_id;
    }

    function deleteProduct($data)
    {
        $t = Product::find($data["product_id"]);
        $t->status = 0; // soft delete
        $t->save();
    }
   
}
