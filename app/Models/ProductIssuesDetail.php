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
            $po->po_total = $total;
            $po->save();
            
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
            $po->po_total = $total;
            $inv->save();
            $po->save();
            
            if($m->pid_qty != $data["pid_qty"]){
                if ($s->ss_stock - $data["pid_qty"] >= 0) {
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
            $m = SuppliesVariant::find($t->item_id);
            $s = SuppliesStock::where('supplies_id',$m->supplies_id)->where('unit_id',$t->unit_id)->first();
            $s->ss_stock += $t->pid_qty;
            $s->save();

            $inv = PurchaseOrderDetailInvoice::find($pi['ref_num']);
            $po = PurchaseOrder::find($inv->po_id);
            $pod = PurchaseOrderDetail::where('po_id', $po->po_id)->get();
            // pengurangan qty invoice
            $total = 0;
            foreach ($pod as $key => $value) {
                if ($t['item_id'] == $value['supplies_variant_id'] && $t['unit_id'] == $value['unit_id']){
                    $value['pod_qty'] += $t->pid_qty;
                    $value['pod_subtotal'] = $value['pod_harga'] * $value['pod_qty'];
                    $value->save();
                }
                $total += $value['pod_subtotal'];
            }
            $inv->poi_total = $total;
            $inv->save();
            $po->po_total = $total;
            $po->save();

            return $m;
        }else if($pi->tipe_return == 2){
            $m = ProductVariant::find($t->item_id);
            $s = ProductStock::where('product_variant_id',$m->product_variant_id)->where('unit_id',$t->unit_id)->first();
            $s->ps_stock -= $t->pid_qty;
            $s->save();
            return $m;
        }
    }

    function stockCheck($data){
        $itemId = 0;
        // Return to Supplier (Tipe 1)
        if ($data['tipe_return'] == 1) {
            $itemId = $data['supplies_variant_id'] ?? $data['item_id'];
            $m = SuppliesVariant::find($itemId);
            $s = SuppliesStock::where('supplies_id', '=', $m->supplies_id)->where('unit_id', '=', $data["unit_id"])->first();

            $pid = isset($data['pid_id']) ? ProductIssuesDetail::find($data['pid_id']) : null;
            $qtyLama = $pid ? (int)$pid->pid_qty : 0;
            $qtyBaru = (int)$data['pid_qty'];
            $stokFisikDB = (int)($s->ss_stock ?? 0);

            // KUNCI: Hitung balance akhir seandainya transaksi ini selesai
            // Stok Sekarang + Stok Balik - Stok Keluar Baru
            $balanceAkhir = $stokFisikDB + $qtyLama - $qtyBaru;
            
            // Jika balance akhir < 0, artinya wadah unit ini HARUS ditambah lewat konversi
            if ($balanceAkhir < 0) {
                
                // Berapa Liter sih yang harus kita tambahkan ke DB agar balance tidak minus?
                // Kita paksa target fisik di DB menjadi selisih bersihnya + 1 (cadangan aman)
                $targetFisikMinimal = ($qtyBaru - $qtyLama) + 1;

                $ss = SuppliesStock::where('supplies_id', $m->supplies_id)->where('status', 1)->orderBy('ss_id', 'desc')->get();
                if (count($ss) <= 0) return -1;

                $virtualStock = []; $logSummary = []; $keyTarget = null;
                foreach ($ss as $idx => $stok) {
                    $virtualStock[$stok->ss_id] = [
                        'model' => $stok, 
                        'current' => (float)$stok->ss_stock, 
                        'unit_id' => $stok->unit_id, 
                        'ss_id' => $stok->ss_id
                    ];
                    if ($stok->unit_id == $data["unit_id"]) { $keyTarget = $idx; }
                }

                $siapkanStok = function ($targetKey, $units) use (&$virtualStock, &$logSummary, &$siapkanStok, $m) {
                    if (!isset($units[$targetKey + 1])) return false;
                    $stokSekarang = $units[$targetKey]; 
                    $stokAtas = $units[$targetKey + 1];
                    
                    if ($virtualStock[$stokAtas->ss_id]['current'] <= 0) { 
                        if (!$siapkanStok($targetKey + 1, $units)) return false; 
                    }
                    
                    $sr = SuppliesRelation::where('supplies_id', $m->supplies_id)->where('su_id_2', $stokSekarang->unit_id)->where('status', 1)->first();
                    
                    if ($sr && $virtualStock[$stokAtas->ss_id]['current'] > 0) {
                        $virtualStock[$stokAtas->ss_id]['current'] -= 1;
                        $hasilBongkar = (float)$sr['sr_value_2'];
                        $virtualStock[$stokSekarang->ss_id]['current'] += $hasilBongkar;
                        
                        $kb = $stokAtas->unit_id . '_cat2';
                        $logSummary[$kb] = ['unit_id' => $stokAtas->unit_id, 'jumlah' => ($logSummary[$kb]['jumlah'] ?? 0) + 1, 'cat' => 2, 'note' => "Konversi unit (Bongkar)", 'sort' => $stokAtas->ss_id * 10];
                        $kh = $stokSekarang->unit_id . '_cat1';
                        $logSummary[$kh] = ['unit_id' => $stokSekarang->unit_id, 'jumlah' => ($logSummary[$kh]['jumlah'] ?? 0) + $hasilBongkar, 'cat' => 1, 'note' => "Konversi unit (Hasil)", 'sort' => ($stokAtas->ss_id * 10) + 1];
                        return true;
                    }
                    return false;
                };

                $idTarget = $ss[$keyTarget]->ss_id;
                $safety = 0;
                
                // Loop ini akan membongkar Pcs menjadi Liter sampai modal fisik kamu di DB mencukupi
                while ($virtualStock[$idTarget]['current'] < $targetFisikMinimal) {
                    $safety++; if ($safety > 500) break;
                    if (!$siapkanStok($keyTarget, $ss)) break;
                }

                if ($virtualStock[$idTarget]['current'] >= $targetFisikMinimal) {
                    foreach ($virtualStock as $v) { 
                        $v['model']->ss_stock = $v['current']; 
                        $v['model']->save(); 
                    }
                    usort($logSummary, function ($a, $b) { return $a['sort'] <=> $b['sort']; });
                    foreach ($logSummary as $l) { 
                        (new LogStock())->insertLog([
                            'log_date' => now(), 'log_kode' => "-", 'log_type' => 2, 'log_category' => $l['cat'], 
                            'log_item_id' => $m->supplies_id, 'log_notes' => $l['note'], 
                            'log_jumlah' => $l['jumlah'], 'unit_id' => $l['unit_id']
                        ]); 
                    }
                } else {
                    return -1; // Stok total (termasuk satuan besar) benar-benar habis
                }
            }
            return 1;
        }

        // Retur pelanggan
        else if ($data['tipe_return'] == 2) {
            $itemId = $data['product_variant_id'] ?? $data['item_id'];
            $s = ProductStock::where('product_variant_id', '=', $itemId)->where('unit_id', '=', $data["unit_id"])->first();
            
            $pid = isset($data['pid_id']) ? ProductIssuesDetail::find($data['pid_id']) : null;
            $qtyLama = $pid ? (float)$pid->pid_qty : 0;
            $qtyBaru = (float)$data['pid_qty'];
            $stokFisikDB = (float)($s->ps_stock ?? 0);

            // Jika balance akhir kamu diprediksi 0 atau minus, siapkan stok fisik di DB +1 unit!
            $targetFisikMinimal = ($qtyBaru - $qtyLama) + 1;

            if ($stokFisikDB < $targetFisikMinimal) {
                $ss = ProductStock::where('product_variant_id', $itemId)->where('status', 1)->orderBy('ps_id', 'desc')->get();
                if (count($ss) <= 0) return -1;

                $virtualStock = []; $logSummary = []; $keyTarget = null;
                foreach ($ss as $idx => $stok) {
                    $virtualStock[$stok->ps_id] = ['model' => $stok, 'current' => (float)$stok->ps_stock, 'unit_id' => $stok->unit_id, 'ps_id' => $stok->ps_id];
                    if ($stok->unit_id == $data["unit_id"]) { $keyTarget = $idx; }
                }

                $siapkanStokProd = function ($targetKey, $units) use (&$virtualStock, &$logSummary, &$siapkanStokProd, $itemId) {
                    if (!isset($units[$targetKey + 1])) return false;
                    $stokSekarang = $units[$targetKey]; $stokAtas = $units[$targetKey + 1];
                    if ($virtualStock[$stokAtas->ps_id]['current'] <= 0) { if (!$siapkanStokProd($targetKey + 1, $units)) return false; }
                    $sr = ProductRelation::where('product_variant_id', $itemId)->where('pr_unit_id_2', $stokSekarang->unit_id)->where('status', 1)->first();
                    if ($sr && $virtualStock[$stokAtas->ps_id]['current'] > 0) {
                        $virtualStock[$stokAtas->ps_id]['current'] -= 1;
                        $hasilBongkar = (float)$sr['pr_unit_value_2'];
                        $virtualStock[$stokSekarang->ps_id]['current'] += $hasilBongkar;
                        
                        $kb = $stokAtas->unit_id . '_cat2';
                        $logSummary[$kb] = ['unit_id' => $stokAtas->unit_id, 'jumlah' => ($logSummary[$kb]['jumlah'] ?? 0) + 1, 'cat' => 2, 'note' => "Konversi unit (Bongkar)", 'sort' => $stokAtas->ps_id * 10];
                        $kh = $stokSekarang->unit_id . '_cat1';
                        $logSummary[$kh] = ['unit_id' => $stokSekarang->unit_id, 'jumlah' => ($logSummary[$kh]['jumlah'] ?? 0) + $hasilBongkar, 'cat' => 1, 'note' => "Konversi unit (Hasil)", 'sort' => ($stokAtas->ps_id * 10) + 1];
                        return true;
                    }
                    return false;
                };

                $idTarget = $ss[$keyTarget]->ps_id;
                $safety = 0;
                while ($virtualStock[$idTarget]['current'] < $targetFisikMinimal) {
                    $safety++; if ($safety > 500) break;
                    if (!$siapkanStokProd($keyTarget, $ss)) break;
                }

                if ($virtualStock[$idTarget]['current'] >= $targetFisikMinimal) {
                    foreach ($virtualStock as $v) { $v['model']->ps_stock = $v['current']; $v['model']->save(); }
                    usort($logSummary, function ($a, $b) { return $a['sort'] <=> $b['sort']; });
                    foreach ($logSummary as $l) { (new LogStock())->insertLog(['log_date' => now(), 'log_kode' => "-", 'log_type' => 1, 'log_category' => $l['cat'], 'log_item_id' => $itemId, 'log_notes' => $l['note'], 'log_jumlah' => $l['jumlah'], 'unit_id' => $l['unit_id']]); }
                }
            }
            return 1;
        }
    }
}