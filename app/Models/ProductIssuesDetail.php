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
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["pi_id"])$result->where('pi_id','=',$data["pi_id"]);

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

    function insertProductIssuesDetail($data)
    {
        $pi = ProductIssues::find($data['pi_id']);
        $m = ProductVariant::find($data["product_variant_id"]);
        $s = ProductStock::where('product_variant_id','=',$m->product_variant_id)->where('unit_id','=',$data["unit_id"])->first();
        // return $m;  

        // Return to Supplier
        $stocks = $s->ps_stock ?? 0;
        if ($pi->tipe_return == 1) {
            if ($stocks - $data["pid_qty"] > 0) {
                $stocks -= $data["pid_qty"];
            } else {
                return -1;
            }
        }
        
        // Return from customer
        elseif ($pi->tipe_return == 2) {
            $stocks += $data["pid_qty"];
        }

        $s->ps_stock = $stocks;
 
        $t = new self();      
        $t->pi_id = $data["pi_id"];
        $t->pid_qty = $data["pid_qty"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->unit_id = $data["unit_id"];
        $t->save();
        $m->save();
        $s->save();

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
        $t->product_variant_id = $data["product_variant_id"];
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
        $m = ProductVariant::find($t->product_variant_id);
        $s = ProductStock::where('product_variant_id',$m->product_variant_id)->where('unit_id',$t->unit_id)->first();
        if($pi->tipe_return == 1){
            $s->ps_stock += $t->pid_qty;
        }else if($pi->tipe_return == 2){
            $s->ps_stock -= $t->pid_qty;
        }
        $s->save();
        return $m;
    }
}