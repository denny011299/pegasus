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
            "date"=>null,
            "all"=>null,
            "tipe_return"=>null,//default = product
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["pi_type"])$result->where('pi_type','=',$data["pi_type"]);
        if($data["tipe_return"])$result->where('tipe_return','=',$data["tipe_return"]);
        
        if($data["date"]) {
            if (is_array($data["date"]) && count($data["date"]) === 2) {
                // Jika date adalah array [start_date, end_date]]
                $startDate = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][0])->format('Y-m-d');
                $endDate   = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][1])->format('Y-m-d');
                $result->whereBetween('created_at', [$startDate, $endDate]);
            } else {
                // Jika date hanya satu nilai
                $date = $data["date"];
                if (!\Carbon\Carbon::hasFormat($data["date"], 'Y-m-d'))$date = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"])->format('Y-m-d');
                
                $result->where('created_at', '=', $date);
            }
        }

        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            $pvr = ProductVariant::find($value->product_variant_id);
            $sup = Product::find($pvr->product_id);
            $value->pr_name = $sup->product_name." ".$pvr->product_variant_name;
            $value->pr_sku = $pvr->product_variant_sku;
            $u = Unit::find($value->unit_id);
            $value->unit_name = $u->unit_name;
        }
 
        return $result;
    }

    function insertProductIssues($data)
    {   
        $m = ProductVariant::find($data["product_variant_id"]);
        $s = ProductStock::where('product_variant_id','=',$m->product_variant_id)->where('unit_id','=',$data["unit_id"])->first();
        // return $m;  

        // Return to Supplier
        $stocks = $s->ps_stock ?? 0;
        if ($data["tipe_return"] == 1) {
            if ($stocks - $data["pi_qty"] > 0) {
                $stocks -= $data["pi_qty"];
            } else {
                return -1;
            }
        }
        
        // Return from customer
        elseif ($data["tipe_return"] == 2) {
            $stocks += $data["pi_qty"];
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
        $t->unit_id = $data["unit_id"];
        $t->save(); 
        $m->save();
        $s->save();

        return $m;  
    } 

    function updateProductIssues($data)
    {
        $m = ProductVariant::find($data["product_variant_id"]);
        $s = ProductStock::where('product_variant_id','=',$m->product_variant_id)->where('unit_id','=',$data["unit_id"])->first();
  
        $pi_date = Carbon::createFromFormat('d-m-Y', $data['pi_date'])->format('Y-m-d');   
        $t =  self::find($data["pi_id"]);    
        // return $m;  
        if($m->pi_qty != $data["pi_qty"]){
            // kembalikan stock ke kondisi sebelum update
            if($data["tipe_return"]  == 1){
                $s->ps_stock += $t->pi_qty;
            }elseif($data["tipe_return"] == 2){
                
                $s->ps_stock -= $t->pi_qty;
            }
            $s->save();

        
              // Return to Supplier
            if ($data["tipe_return"] == 1) {
                if ($s->ps_stock - $data["pi_qty"] > 0) {
                    $s->ps_stock -= $data["pi_qty"];
                } else {
                    return -1;
                }
            }
            // Return from customer
            elseif ($data["tipe_return"] == 2) {
                $s->ps_stock += $data["pi_qty"];
            }

        }
      

        $t->pi_type = $data["pi_type"];     
        $t->pi_qty = $data["pi_qty"];   
        $t->pi_date = $pi_date;    
        $t->product_variant_id = $data["product_variant_id"];    
        $t->pi_notes = $data["pi_notes"];    
        $t->tipe_return = $data["tipe_return"];      
        $t->product_variant_id = $data["product_variant_id"];
        $t->unit_id = $data["unit_id"];
        $t->save(); 
        $s->save();
        $m->save();

        return $m;  
    }

    function deleteProductIssues($data)
    {
        $t = self::find($data["pi_id"]);
        $t->status = 0;
        $t->save();

        $m = ProductVariant::find($t->product_variant_id);
        $s = ProductStock::where('product_variant_id',$m->product_variant_id)->where('unit_id',$t->unit_id)->first();
        if($t->tipe_return == 1){
            $s->ps_stock += $t->pi_qty;
        }else if($t->tipe_return == 2){
            $s->ps_stock -= $t->pi_qty;
        }
        $s->save();
        return $m;
    }
}

