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
            
            $sup = Product::find($value->pr_id);
            $pvr = ProductVariants::find($value->pvr_id);
            $value->pr_name = $sup->nama_produk." ".$pvr->nama_varian;
            $value->pr_sku = $pvr->SKU;
            $value->pr_img = $pvr->gambar;

            if($value->warehouse_id){
                $value->outlet = Warehouse::find($value->warehouse_id)->warehouse_name;
            }
            else{
                $value->outlet = Store::find($value->store_id)->store_name;
            }
             $value->sup_in = manageStock::where("pvr_id", $value->pvr_id)
                ->where("ms_type", 1)
            // ->whereBetween('created_at', [$data["ms_start_date"] ?? Carbon::now(), $data["ms_end_date"] ?? Carbon::now()])
                ->sum('ms_stock');
            $value->sup_out = manageStock::where("pvr_id", $value->pvr_id)
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

       
        $p = ProductVariants::where("barcode","=",$data["barcode"])->first();

        if($p){
             $exists = self::where("pvr_id",'=', $p->product_variant_id)
                ->where(function($q) use ($data) {
                    $q->where("store_id",'=', $data["store_id"])
                    ->orWhere("warehouse_id",'=', $data["warehouse_id"]);
                })
                ->where('ms_type','=', $data["ms_type"])
                ->whereDate('created_at','=', Carbon::today());
            $ext  = $exists->count();
            if ($ext>0) {
              
               $s= $exists->first();
               $data["pvr_id"] = $s->pvr_id;
               $data["pr_id"] = $s->pr_id;
               $data["ms_id"] = $s->ms_id;
               $data["product_id"] = $s->product_id;
               return $this->updateManage($data);
            }
            else{
                $data["pr_id"] = $p->product_id;
                $data["pvr_id"] = $p->product_variant_id;
            }
        } else {
            return "Product not found";
        }
        
        $t = new self();    
        $t->ms_type = $data["ms_type"]; 
        $t->pr_id = $data["pr_id"];    
        $t->pvr_id = $data["pvr_id"];    
        if(isset($data["store_id"]))$t->store_id = $data["store_id"];    
        if(isset($data["warehouse_id"]))$t->warehouse_id = $data["warehouse_id"]; 
        $t->ms_stock = $data["ms_stock"];   
        $t->save(); 
        return $t->ms_id;   
    }

    function updateManage($data)
    {
        $t = self::find($data["ms_id"]);    
        $t->ms_type = $data["ms_type"]; 
        if(isset($data["pr_id"]))$t->pr_id = $data["pr_id"];    
        if(isset($data["sup_id"]))$t->sup_id = $data["sup_id"]; 
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
