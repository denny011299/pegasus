<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductIssuesDetail extends Model
{
    protected $table = "product_issues_details";
    protected $primaryKey = "pid_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProductIssuesDetail($data = [])
    {
        $data = array_merge([
            "pi_id"=>null,
            "tipe_return"=>null
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["pi_id"])$result->where('pi_id','=',$data["pi_id"]);

        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            if ($data["tipe_return"] == 1){
                $svr = SuppliesVariant::find($value->item_id);
                $sup = Supplies::find($svr->supplies_id);
                $value->sup_name = $sup->supplies_name." ".$svr->supplies_variant_name;
                $value->sup_sku = $svr->supplies_variant_sku;
            }
            else if ($data["tipe_return"] == 2){
                $pvr = ProductVariant::find($value->item_id);
                $sup = Product::find($pvr->product_id);
                $value->pr_name = $sup->product_name." ".$pvr->product_variant_name;
                $value->pr_sku = $pvr->product_variant_sku;
            }
            $u = Unit::find($value->unit_id);
            $value->unit_name = $u->unit_name;
        }
 
        return $result;
    }

    function insertProductIssuesDetail($data)
    {
        $pi = ProductIssues::find($data['pi_id']);
        $itemId = 0;

        // Return to Supplier
        if ($pi->tipe_return == 1){
            $itemId = $data['supplies_variant_id'];
            $m = SuppliesVariant::find($itemId);
            $s = SuppliesStock::where('supplies_id','=',$m->supplies_id)->where('unit_id','=',$data["unit_id"])->first();
            
            $stocks = $s->ss_stock ?? 0;
            if ($stocks - $data["pid_qty"] > 0) {
                $stocks -= $data["pid_qty"];
            } else {
                return -1;
            }

            $s->ss_stock = $stocks;
            $m->save();
            $s->save();
        }

        // Return from customer 
        else{
            $itemId = $data["product_variant_id"];
            $m = ProductVariant::find($itemId);
            $s = ProductStock::where('product_variant_id','=',$m->product_variant_id)->where('unit_id','=',$data["unit_id"])->first();
            
            $stocks = $s->ps_stock ?? 0;
            $stocks += $data["pid_qty"];

            $s->ps_stock = $stocks;
            $m->save();
            $s->save();
        }
        // return $m;
 
        $t = new self();      
        $t->pi_id = $data["pi_id"];
        $t->pid_qty = $data["pid_qty"];
        $t->item_id = $itemId;
        $t->unit_id = $data["unit_id"];
        $t->save();

        return $t->pid_id;  
    } 

    function updateProductIssuesDetail($data)
    {
        $pi = ProductIssues::find($data['pi_id']);
        $m = ProductVariant::find($data["product_variant_id"]);
        $s = ProductStock::where('product_variant_id','=',$m->product_variant_id)->where('unit_id','=',$data["unit_id"])->first();
  
        $t =  self::find($data["pid_id"]);    
        // return $m;
        if($m->pid_qty != $data["pid_qty"]){
            // kembalikan stock ke kondisi sebelum update
            if($pi->tipe_return  == 1){
                $s->ps_stock += $t->pid_qty;
            }elseif($pi->tipe_return == 2){
                
                $s->ps_stock -= $t->pid_qty;
            }
            $s->save();

            // Return to Supplier
            if ($pi->tipe_return == 1) {
                if ($s->ps_stock - $data["pid_qty"] > 0) {
                    $s->ps_stock -= $data["pid_qty"];
                } else {
                    return -1;
                }
            }
            // Return from customer
            elseif ($pi->tipe_return == 2) {
                $s->ps_stock += $data["pid_qty"];
            }

        }
      
        $t->pid_qty = $data["pid_qty"];
        $t->item_id = $data["product_variant_id"];
        $t->unit_id = $data["unit_id"];
        $t->save();
        $s->save();
        $m->save();

        return $t->pid_id;  
    }

    function deleteProductIssuesDetail($data)
    {
        $t = self::find($data["pid_id"]);
        $t->status = 0;
        $t->save();

        $pi = ProductIssues::find($t->pi_id);
        
        if($pi->tipe_return == 1){
            $m = SuppliesVariant::find($t->supplies_variant_id);
            $s = SuppliesStock::where('supplies_id',$m->supplies_id)->where('unit_id',$t->unit_id)->first();
            $s->ss_stock += $t->pid_qty;
            $s->save();
            return $m;
        }else if($pi->tipe_return == 2){
            $m = ProductVariant::find($t->product_variant_id);
            $s = ProductStock::where('product_variant_id',$m->product_variant_id)->where('unit_id',$t->unit_id)->first();
            $s->ps_stock -= $t->pid_qty;
            $s->save();
            return $m;
        }
    }
}