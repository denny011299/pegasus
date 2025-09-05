<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ProductIssues extends Model
{
    protected $table = "product_issues";
    protected $primaryKey = "pi_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProductIssues($data = [])
    {
        $data = array_merge([
            "pi_type"=>null,
            "ms_start_date"=>null,
            "ms_end_date"=>null,
            "all"=>null,
            "type"=>2,//default = product
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["pi_type"])$result->where('pi_type','=',$data["pi_type"]);
        

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

        }
 
        return $result;
    }

    function insertProductIssues($data)
    {   
        $m = ProductVariant::find($data["product_variant_id"]);
        // return $m;  

        // Return to Supplier
        if ($data["tipe_return"] == 1) {
            if ($m->product_variant_stock - $data["pi_qty"] > 0) {
                $m->product_variant_stock -= $data["pi_qty"];
            } else {
                return -1;
            }
        }
        // Return from customer
        elseif ($data["tipe_return"] == 2) {
            $m->ms_stock += $data["pi_qty"];
        }


        $pi_date = Carbon::createFromFormat('d-m-Y', $data['pi_date'])->format('Y-m-d');   
        $t = new self();    
        $t->pi_type = $data["pi_type"];     
        $t->pi_qty = $data["pi_qty"];    
        $t->pi_date = $pi_date;    
        $t->product_variant_id = $data["product_variant_id"];    
        $t->pi_notes = $data["pi_notes"];    
        $t->tipe_return = $data["tipe_return"];      
        $t->product_variant_id = $data["product_variant_id"];      
        $t->save(); 
        $m->save();

        return $m;  
    } 

    function updateProductIssues($data)
    {
        $m = ProductVariant::find($data["product_variant_id"]);
        $pi_date = Carbon::createFromFormat('d-m-Y', $data['pi_date'])->format('Y-m-d');   
        $t =  self::find($data["pi_id"]);    
        // return $m;  
        if($m->pi_qty != $data["pi_qty"]){
            // kembalikan stock ke kondisi sebelum update
            if($m->tipe_return == 1){
                $m->product_variant_stock += $m->pi_qty;
            }elseif($m->tipe_return == 2){
                $m->ms_stock -= $m->pi_qty;
            }

              // Return to Supplier
            if ($data["tipe_return"] == 1) {
                if ($m->product_variant_stock - $data["pi_qty"] > 0) {
                    $m->product_variant_stock -= $data["pi_qty"];
                } else {
                    return -1;
                }
            }
            // Return from customer
            elseif ($data["tipe_return"] == 2) {
                $m->ms_stock += $data["pi_qty"];
            }

        }
      

        $t->pi_type = $data["pi_type"];     
        $t->pi_qty = $data["pi_qty"];    
        $t->pi_date = $pi_date;    
        $t->product_variant_id = $data["product_variant_id"];    
        $t->pi_notes = $data["pi_notes"];    
        $t->tipe_return = $data["tipe_return"];      
        $t->product_variant_id = $data["product_variant_id"];      
        $t->save(); 
        $m->save();

        return $m;  
    }

    function deleteProductIssues($data)
    {
        $t = self::find($data["pi_id"]);    
        $t->status = 0;
        $t->save();
        $m = ProductVariant::find($t->product_variant_id);
        if($m->tipe_return == 1){
            $m->product_variant_stock += $m->pi_qty;
        }elseif($m->tipe_return == 2){
            $m->ms_stock -= $m->pi_qty;
        }
        $m->save();
        return $m;
    }
}

