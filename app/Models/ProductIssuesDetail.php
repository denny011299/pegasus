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
                $value->supplies_id = $svr->supplies_id;
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
            $inv = PurchaseOrderDetailInvoice::find($data['ref_num']);
            $po = PurchaseOrder::find($inv->po_id);
            $pod = PurchaseOrderDetail::where('po_id', $po->po_id)->get();

            // pengurangan qty invoice
            $total = 0;
            foreach ($pod as $key => $value) {
                if ($data['supplies_variant_id'] == $value['supplies_variant_id'] && $data['unit_id'] == $value['unit_id']){
                    $value['pod_qty'] -= $data['pid_qty'];
                    $value['pod_subtotal'] = $value['pod_harga'] * $value['pod_qty'];
                    $value->save();
                }
                $total += $value['pod_subtotal'];
            }

            $inv->poi_total = $total;
            $inv->save();
            
            // pengurangan qty stok
            $stocks = $s->ss_stock ?? 0;
            if ($stocks - $data["pid_qty"] >= 0) {
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
        $t =  self::find($data["pid_id"]);
        $itemId = 0;

        // Return to Supplier
        if ($pi->tipe_return == 1){
            $itemId = $data['supplies_variant_id'];
            $m = SuppliesVariant::find($itemId);
            $s = SuppliesStock::where('supplies_id','=',$m->supplies_id)->where('unit_id','=',$data["unit_id"])->first();
            
            $inv = PurchaseOrderDetailInvoice::find($data['ref_num']);
            $po = PurchaseOrder::find($inv->po_id);
            $pod = PurchaseOrderDetail::where('po_id', $po->po_id)->get();

            // pengurangan qty invoice
            $total = 0;
            foreach ($pod as $key => $value) {
                if ($data['supplies_variant_id'] == $value['supplies_variant_id'] && $data['unit_id'] == $value['unit_id']){
                    if (($value['pod_qty'] - $data['pid_qty']) >= 0) {
                        $value['pod_qty'] -= $data['pid_qty'];
                        $value['pod_subtotal'] = $value['pod_harga'] * $value['pod_qty'];
                        $value->save();
                    }
                    else return -1;
                }
                $total += $value['pod_subtotal'];
            }

            $inv->poi_total = $total;
            $inv->save();
            
            if($m->pid_qty != $data["pid_qty"]){
                if ($s->ss_stock + $t->pid_qty - $data["pid_qty"] >= 0) {
                    $s->ss_stock += $t->pid_qty;
                    $s->ss_stock -= $data["pid_qty"];
                } else {
                    return -1;
                }
            }
            // $s->ss_stock = $stocks;
            $m->save();
            $s->save();
        }

        // Return from customer 
        else{
            $itemId = $data["product_variant_id"];
            $m = ProductVariant::find($itemId);
            $s = ProductStock::where('product_variant_id','=',$m->product_variant_id)->where('unit_id','=',$data["unit_id"])->first();
            
            if($m->pid_qty != $data["pid_qty"]){
                $s->ps_stock -= $t->pid_qty;
                $s->ps_stock += $data["pid_qty"];
            }
            $m->save();
            $s->save();
        }

        $t->pi_id = $data["pi_id"];
        $t->pid_qty = $data["pid_qty"];
        $t->item_id = $itemId;
        $t->unit_id = $data["unit_id"];
        $t->save();

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

            $inv = PurchaseOrderDetailInvoice::find($t['ref_num']);
            $po = PurchaseOrder::find($inv->po_id);
            $pod = PurchaseOrderDetail::where('po_id', $po->po_id)->get();
            // pengurangan qty invoice
            $total = 0;
            foreach ($pod as $key => $value) {
                if ($t['supplies_variant_id'] == $value['supplies_variant_id'] && $t['unit_id'] == $value['unit_id']){
                    $value['pod_qty'] += $t->pid_qty;
                    $value['pod_subtotal'] = $value['pod_harga'] * $value['pod_qty'];
                    $value->save();
                }
                $total += $value['pod_subtotal'];
            }
            $inv->poi_total = $total;
            $inv->save();

            return $m;
        }else if($pi->tipe_return == 2){
            $m = ProductVariant::find($t->product_variant_id);
            $s = ProductStock::where('product_variant_id',$m->product_variant_id)->where('unit_id',$t->unit_id)->first();
            $s->ps_stock -= $t->pid_qty;
            $s->save();
            return $m;
        }
    }

    function stockCheck($data){
        $itemId = 0;

        // Return to Supplier
        if ($data['tipe_return'] == 1){
            $itemId = $data['supplies_variant_id'] ?? $data['item_id'];
            $m = SuppliesVariant::find($itemId);
            $s = SuppliesStock::where('supplies_id','=',$m->supplies_id)->where('unit_id','=',$data["unit_id"])->first();
            
            $stocks = $s->ss_stock ?? 0;
            if ($stocks - $data["pid_qty"] < 0) {
                $ss = SuppliesStock::where('supplies_id','=',$m->supplies_id)->where('status', 1)->orderBy('ss_id')->get();
                if (count($ss) > 1){
                    for ($i=0; $i < count($ss)-1; $i++) { 
                        $ss1 = $ss[$i];
                        $ss2 = $ss[$i + 1];
                        $sr = SuppliesRelation::where('supplies_id','=',$m->supplies_id)->where('su_id_2', $ss2['unit_id'])->first();

                        if ($ss1->ss_stock <=0 || $data['pid_qty'] > ($sr['sr_value_2'] * $ss1->ss_stock)) {
                            if ($i == count($ss)-2) {
                                return -1;
                            }
                            continue;
                        }

                        // Untuk konversi seberapa banyak agar memenuhi permintaan
                        $qtyTarget = (int) ceil($data['pid_qty'] / $sr['sr_value_2']);
                        if ($qtyTarget <= 0) {
                            continue;
                        }

                        $ss1->ss_stock -= $qtyTarget;
                        $ss1->save();
                        // Catat Log
                        (new LogStock())->insertLog([
                            'log_date' => now(),
                            'log_kode'    => "-",
                            'log_type'    => 2,
                            'log_category' => 2,
                            'log_item_id' => $m->supplies_id,
                            'log_notes'  => "Konversi unit dari produk bermasalah",
                            'log_jumlah' => $qtyTarget,
                            'unit_id'    => $ss1['unit_id'],
                        ]);

                        $ss2->ss_stock += $sr['sr_value_2'] * $qtyTarget;
                        $ss2->save();
                        // Catat Log
                        (new LogStock())->insertLog([
                            'log_date' => now(),
                            'log_kode'    => "-",
                            'log_type'    => 2,
                            'log_category' => 1,
                            'log_item_id' => $m->supplies_id,
                            'log_notes'  => "Konversi unit dari produk bermasalah",
                            'log_jumlah' => $sr['sr_value_2'] * $qtyTarget,
                            'unit_id'    => $ss2['unit_id'],
                        ]);
                    }
                    return 1;
                } else {
                    return -1;
                }
            }
        } else if ($data['tipe_return'] == 2) {
            $itemId = $data['product_variant_id'] ?? $data['item_id'];
            $s = ProductStock::where('product_variant_id','=',$itemId)->where('unit_id','=',$data["unit_id"])->first();
            $stocks = $s->ps_stock ?? 0;
            if ($stocks - $data["pid_qty"] < 0) {
                return -1;
            }
        }
        return 1;
    }
}