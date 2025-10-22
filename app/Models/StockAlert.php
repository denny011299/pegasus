<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAlert extends Model
{
    use HasFactory;
    protected $table = "stock_alerts";
    protected $primaryKey = "stal_id";
    public $timestamps = true;
    public $incrementing = true;

    function getStockAlert($data = []){
        $data = array_merge([
            "mode"=>1,//1=low stock, 2= out of stock
        ], $data);

        $result = ProductVariant::where('product_variants.status', '=', 1);
        $result->join('products as p', 'p.product_id', '=', 'product_variants.product_id');
        $result->where('p.status', '=', 1);
        /*
        if($data["mode"]==1){
            $result->whereColumn('product_variants.product_variant_stock','<=','p.product_min_stock');
            $result->whereColumn('product_variants.product_variant_stock','>',0);
        }
        else if($data["mode"]==2){
            $result->whereColumn('product_variants.product_variant_stock','=',0);
        }*/
        $result = $result->get();
        
        foreach ($result as $key => $value) {
            $u = Unit::where('unit_id', $value->unit_id)->first();
            $value->product_unit = $u ? $u->unit_name : "-";
            $value->product_category = Category::find($value->category_id)->category_name ?? "-";
            $value->stock = (new ProductStock())->getProductStock(["product_variant_id"=>$value->product_variant_id]);
            $value->relation = (new ProductRelation())->getProductRelation(["product_variant_id"=>$value->product_variant_id]);
        }
        
        return $result;
    }
}
