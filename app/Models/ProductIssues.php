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
            // $pvr = ProductVariant::find($value->product_variant_id);
            // $sup = Product::find($pvr->product_id);
            // $value->product_variant_name = $pvr->product_variant_name;
            // $value->pr_sku = $pvr->product_variant_sku;
            // $u = Unit::find($value->unit_id);
            // $value->unit_name = $u->unit_name;
            if ($value->ref_num){
                $inv = PurchaseOrderDetailInvoice::find($value->ref_num);
                $value->poi_code = $inv->poi_code ?? "";
                $po = PurchaseOrder::find($inv->po_id);
                $sup = Supplier::where('supplier_id', $po->po_supplier)->first();
                $value->supplier_name = $sup->supplier_name ?? "";
            }
            $value->po_number = PurchaseOrder::find($value['po_id'])->po_number;
            $value->items = (new ProductIssuesDetail())->getProductIssuesDetail(["pi_id" => $value->pi_id, "tipe_return" => $value->tipe_return]);
        }
 
        return $result;
    }

    function insertProductIssues($data)
    {   
        // $m = ProductVariant::find($data["product_variant_id"]);
        // $s = ProductStock::where('product_variant_id','=',$m->product_variant_id)->where('unit_id','=',$data["unit_id"])->first();
        // // return $m;  

        // // Return to Supplier
        // $stocks = $s->ps_stock ?? 0;
        // if ($data["tipe_return"] == 1) {
        //     if ($stocks - $data["pi_qty"] > 0) {
        //         $stocks -= $data["pi_qty"];
        //     } else {
        //         return -1;
        //     }
        // }
        
        // // Return from customer
        // elseif ($data["tipe_return"] == 2) {
        //     $stocks += $data["pi_qty"];
        // }

        // $s->ps_stock = $stocks;

        $pi_date = Carbon::createFromFormat('d-m-Y', $data['pi_date'])->format('Y-m-d');   
        $t = new self();
        $t->pi_code   = $this->generateProductIssueID();
        $t->pi_type = $data["pi_type"];
        $t->ref_num = $data["ref_num"] ?? 0;
        $t->po_id = $data["po_id"];
        $t->pi_date = $pi_date;
        $t->pi_notes = $data["pi_notes"];    
        $t->tipe_return = $data["tipe_return"];     
        $t->pi_img = $data["pi_img"] ?? null; 
        // $t->pi_qty = $data["pi_qty"];   
        // $t->product_variant_id = $data["product_variant_id"];
        // $t->unit_id = $data["unit_id"]; 
        $t->save(); 
        // $m->save();
        // $s->save();

        return $t;  
    }

    function updateProductIssues($data)
    {
        $t =  self::find($data["pi_id"]);
        $pi_date = Carbon::createFromFormat('d-m-Y', $data['pi_date'])->format('Y-m-d');
        // $m = ProductVariant::find($data["product_variant_id"]);
        // $s = ProductStock::where('product_variant_id','=',$m->product_variant_id)->where('unit_id','=',$data["unit_id"])->first();
  
        // // return $m;  
        // if($m->pi_qty != $data["pi_qty"]){
        //     // kembalikan stock ke kondisi sebelum update
        //     if($data["tipe_return"]  == 1){
        //         $s->ps_stock += $t->pi_qty;
        //     }elseif($data["tipe_return"] == 2){
                
        //         $s->ps_stock -= $t->pi_qty;
        //     }
        //     $s->save();

        
        //       // Return to Supplier
        //     if ($data["tipe_return"] == 1) {
        //         if ($s->ps_stock - $data["pi_qty"] > 0) {
        //             $s->ps_stock -= $data["pi_qty"];
        //         } else {
        //             return -1;
        //         }
        //     }
        //     // Return from customer
        //     elseif ($data["tipe_return"] == 2) {
        //         $s->ps_stock += $data["pi_qty"];
        //     }

        // } 
        $t->pi_code   = $data['pi_code'];
        $t->pi_type = $data["pi_type"];
        $t->po_id = $data["po_id"];
        $t->ref_num = $data["ref_num"];
        $t->pi_date = $pi_date;
        $t->pi_notes = $data["pi_notes"];
        $t->tipe_return = $data["tipe_return"];
        if (isset($data['pi_img'])) $t->pi_img = $data["pi_img"];
        // $t->pi_qty = $data["pi_qty"];   
        // $t->product_variant_id = $data["product_variant_id"];
        // $t->unit_id = $data["unit_id"];
        $t->save(); 
        // $s->save();
        // $m->save();

        return $t;  
    }

    function deleteProductIssues($data)
    {
        $t = self::find($data["pi_id"]);
        if (isset($t->ref_num) && $t->ref_num != 0){
            $inv = PurchaseOrderDetailInvoice::find($t->ref_num);
            $po = PurchaseOrder::find($inv->po_id);
            if ($po->tt_id != null) {
                return -1;
            }
        }
        $t->status = 0;
        $t->save();

        // $m = ProductVariant::find($t->product_variant_id);
        // $s = ProductStock::where('product_variant_id',$m->product_variant_id)->where('unit_id',$t->unit_id)->first();
        // if($t->tipe_return == 1){
        //     $s->ps_stock += $t->pi_qty;
        // }else if($t->tipe_return == 2){
        //     $s->ps_stock -= $t->pi_qty;
        // }
        // $s->save();
    }

    function generateProductIssueID()
    {
        $id = self::max('pi_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "PI" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}

