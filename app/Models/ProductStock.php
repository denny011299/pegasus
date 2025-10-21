<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStock extends Model
{
    protected $table = "product_stocks";
    protected $primaryKey = "ps_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProductStock($data = []){
        $data = array_merge([
            "product_id"=>null,
            "product_variant_id"=>null
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["product_id"]) $result->where('product_id','=',$data["product_id"]);
        if($data["product_variant_id"]) $result->where('product_variant_id','=',$data["product_variant_id"]);
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            $u = Unit::find($value->unit_id);
            $value->unit_name = $u->unit_name;
            $value->unit_short_name = $u->unit_short_name;
        }
        return $result;
    }

    function insertProductStock($data)
    {
        $t = new self();
        $t->product_id = $data["product_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->unit_id = $data["unit_id"];
        $t->ps_stock = $data["ps_stock"] ?? 0;
        $t->save();

        return $t->role_id;
    }

    function updateProductStock($data)
    {
        $t = self::find($data["ps_id"]);
        $t->product_id = $data["product_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->unit_id = $data["unit_id"];
        $t->ps_stock = $data["ps_stock"];
        $t->save();
        return $t->role_id;
    }

    function deleteProductStock($data)
    {
        $t = self::find($data["ps_id"]);
        $t->status = 0;
        $t->save();
    }

    function syncStock($product_id) {
        $variant = ProductVariant::where("product_id","=",$product_id)->get();
        $product = Product::find($product_id);
        foreach ($variant as $key => $value) {
            self::where('product_variant_id','=',$value["product_variant_id"])->whereNotIn('unit_id',json_decode($product->product_unit,true))->update(["status"=>0]);
            foreach (json_decode($product->product_unit,true) as $key => $unit) {
                $dt = self::where('product_variant_id','=',$value["product_variant_id"])->where('unit_id','=',$unit)->count();
                if($dt==0){
                    self::insertProductStock([
                        "product_id"=>$product_id,
                        "product_variant_id"=>$value->product_variant_id,
                        "unit_id"=>$unit,
                        "ps_stock"=>0,
                    ]);
                }
            }
        }
    }

    function cekStockBerlebih($data) {
        $t = self::find($data["ps_id"]);
        $p = Product::find($data["product_id"]);
        if($p->unit_id != $data["unit_id"]){
            $ada = 1;
            while ($ada==1) {
                $r = ProductRelation::where('pr_unit_id_2','=',$data["unit_id"])
                ->where('product_id','=',$data["product_id"])->first();
                if($t->ps_stock>=$r->pr_unit_value_2){
                  
                    $tambah = floor($t->ps_stock /$r->pr_unit_value_2);
                    $t->ps_stock %= $r->pr_unit_value_2;
                    
                    $t->save();
                    $stBaru = self::where('product_variant_id','=',$data["product_variant_id"])
                    ->where("unit_id",'=',$r->pr_unit_id_1)->first();
                    $stBaru->ps_stock += $tambah;
                    $stBaru->save();
    
                    $cek = $r = ProductRelation::where('pr_unit_id_2','=',$r->pr_unit_id_1)
                    ->where('product_id','=',$data["product_id"]);
                    if($cek->count()<=0){
                        $ada=-1;
                    }
                }
                else  $ada=-1;
            }
        }

    }
}
