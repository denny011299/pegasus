<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ManageStock extends Model
{
    protected $table = "manage_stocks";
    protected $primaryKey = "ms_id";
    public $timestamps = true;
    public $incrementing = true;

    function getManageStocks($data = [])
    {
        $data = array_merge([
            "ms_type"=>null,
            "ms_start_date"=>null,
            "ms_end_date"=>null,
            "all"=>null,
            "type"=>2,//default = product
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["ms_type"])$result->where('ms_type','=',$data["ms_type"]);
        

        if($data["ms_start_date"] && $data["ms_end_date"]) {
            $endDate = Carbon::parse($data["ms_end_date"])->addDay();
            $result->whereBetween('created_at', [$data["ms_start_date"], $endDate]);
        } elseif($data["ms_start_date"]) {
      
            $result->where('created_at', '>=', $data["ms_start_date"]);
        } elseif($data["ms_end_date"]) {
            $result->where('created_at', '<=', $data["ms_end_date"]);
        }
        
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();

        foreach ($result as $key => $value) {
            $pvr = ProductVariant::find($value->product_variant_id);
            $sup = Product::find($pvr->product_id);
            $value->pr_name = $sup->product_name." ".$pvr->product_variant_name;
            $value->pr_sku = $pvr->product_variant_sku;
             $value->sup_in = manageStock::where("product_variant_id", $value->product_variant_id)
                ->where("ms_type", 1)
            // ->whereBetween('created_at', [$data["ms_start_date"] ?? Carbon::now(), $data["ms_end_date"] ?? Carbon::now()])
                ->sum('ms_stock');
            $value->sup_out = manageStock::where("product_variant_id", $value->product_variant_id)
                ->where("ms_type", 2)
                //->whereBetween('created_at', [$data["ms_start_date"] ?? Carbon::now(), $data["ms_end_date"] ?? Carbon::now()])
                ->sum('ms_stock');
        }
 
        return $result;
    }

    function insertManageStock($data)
    {   
         $data = array_merge([
            "store_id"=>null,
            "warehouse_id"=>null,
        ], $data);

       
        $p = ProductVariant::where("product_variant_barcode","=",$data["barcode"])->first();

        if($p){
             $exists = self::where("product_variant_id",'=', $p->product_variant_id)
                ->where('ms_type','=', $data["ms_type"])
                ->whereDate('created_at','=', Carbon::today());
            $ext  = $exists->count();
            if ($ext>0) {
              
               $s= $exists->first();
               $data["product_variant_id"] = $s->product_variant_id;
               $data["ms_id"] = $s->ms_id;
                $p->product_variant_stock += $data["ms_stock"];
                $p->save();
               return $this->updateManage($data);
            }
            else{
                $data["product_variant_id"] = $p->product_variant_id;
                $p->product_variant_stock += $data["ms_stock"];
                $p->save();
            }
        } else {
            return "Product not found";
        }
        
        $t = new self();    
        $t->ms_type = $data["ms_type"]; 
        $t->product_variant_id = $data["product_variant_id"];    
        $t->ms_stock = $data["ms_stock"];   
        $t->save(); 
        return $t->ms_id;   
    }

    function updateManage($data)
    {
        $t = self::find($data["ms_id"]);    
        $t->ms_stock += $data["ms_stock"];   
        $t->save(); 
        return $t->ms_id;   
    }

    function deleteManage($data)
    {
        $t = self::find($data["ms_id"]);    
        $t->status = 0;
        $t->save();
    }
}
