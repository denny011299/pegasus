<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAlertSupplies extends Model
{
    use HasFactory;
    protected $table = "stock_alerts";
    protected $primaryKey = "stal_id";
    public $timestamps = true;
    public $incrementing = true;

    function getStockAlertSupplies($data = []){
        $data = array_merge([
            "mode"=>1,//1=low stock, 2= out of stock
        ], $data);

        $result = Supplies::where('supplies.status', '=', 1);
        $result->where('supplies.status', '=', 1);
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
            $value->supplies_unit = json_decode($value->supplies_unit);
            $value->units = Unit::whereIn('unit_id', $value->supplies_unit)->get();
            $value->stock = (new SuppliesStock())->getProductStock([
                "supplies_id" => $value->supplies_id
            ]);
            $u = Unit::find($value->supplies_default_unit);
            $value->default_unit = $u->unit_name;
            $value->relation = (new SuppliesRelation())->getSuppliesRelation(["supplies_id"=>$value->supplies_id]);
        }
        
        return $result;
    }
}
