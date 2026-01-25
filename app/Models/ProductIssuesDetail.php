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
            $m = ProductVariant::find($t->item_id);
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
                $butuhTersedia = $data['pid_qty']; // Jumlah yang dibutuhkan untuk diproses

                $ss = SuppliesStock::where('supplies_id', $m->supplies_id)
                    ->where('status', 1)
                    ->orderBy('ss_id', 'desc') 
                    ->get();

                if (count($ss) <= 0) {
                    return -1;
                }

                $virtualStock = [];
                $logSummary = []; 

                foreach ($ss as $stok) {
                    $virtualStock[$stok->ss_id] = [
                        'model' => $stok,
                        'current' => (float)$stok->ss_stock,
                        'unit_id' => $stok->unit_id,
                        'ss_id' => $stok->ss_id
                    ];
                }

                // FUNGSI REKURSIF: Menyiapkan stok (Bongkar Berantai)
                $siapkanStok = function($targetKey, $units) use (&$virtualStock, &$logSummary, &$siapkanStok, $m) {
                    if (!isset($units[$targetKey + 1])) return false; 

                    $stokSekarang = $units[$targetKey];
                    $stokAtas = $units[$targetKey + 1];

                    if ($virtualStock[$stokAtas->ss_id]['current'] <= 0) {
                        $bisaBongkarAtas = $siapkanStok($targetKey + 1, $units);
                        if (!$bisaBongkarAtas) return false;
                    }

                    $sr = SuppliesRelation::where('supplies_id', $m->supplies_id)
                        ->where('su_id_2', $stokSekarang->unit_id)
                        ->where('status', 1)
                        ->first();

                    if ($sr && $virtualStock[$stokAtas->ss_id]['current'] > 0) {
                        $virtualStock[$stokAtas->ss_id]['current'] -= 1;
                        $hasilBongkar = (float)$sr['sr_value_2'];
                        $virtualStock[$stokSekarang->ss_id]['current'] += $hasilBongkar;

                        // Grouping Log: ss_id kecil (unit besar) diletakkan di atas
                        $baseOrder = $stokAtas->ss_id * 10; 

                        $keyBongkar = $stokAtas->unit_id . '_cat2';
                        $logSummary[$keyBongkar] = [
                            'unit_id' => $stokAtas->unit_id,
                            'jumlah' => ($logSummary[$keyBongkar]['jumlah'] ?? 0) + 1,
                            'cat' => 2,
                            'note' => "Konversi unit dari produk bermasalah (Bongkar)",
                            'sort_order' => $baseOrder
                        ];

                        $keyHasil = $stokSekarang->unit_id . '_cat1';
                        $logSummary[$keyHasil] = [
                            'unit_id' => $stokSekarang->unit_id,
                            'jumlah' => ($logSummary[$keyHasil]['jumlah'] ?? 0) + $hasilBongkar,
                            'cat' => 1,
                            'note' => "Konversi unit dari produk bermasalah (Hasil)",
                            'sort_order' => $baseOrder + 1
                        ];
                        
                        return true;
                    }
                    return false;
                };

                // Proses Penyiapan Stok
                $keyPalingBawah = 0;
                $idPalingBawah = $ss[$keyPalingBawah]->ss_id;
                $safety = 0; 

                while ($virtualStock[$idPalingBawah]['current'] < $butuhTersedia) {
                    $safety++;
                    if ($safety > 500) break; 
                    $berhasil = $siapkanStok($keyPalingBawah, $ss);
                    if (!$berhasil) break; 
                }

                // --- EKSEKUSI FINAL ---
                if ($virtualStock[$idPalingBawah]['current'] >= $butuhTersedia) {
                    foreach ($virtualStock as $v) {
                        $v['model']->ss_stock = $v['current'];
                        $v['model']->save();
                    }
                    
                    // Urutkan Log (Unit Besar -> Unit Kecil)
                    usort($logSummary, function($a, $b) {
                        return $a['sort_order'] <=> $b['sort_order'];
                    });

                    foreach ($logSummary as $l) {
                        (new LogStock())->insertLog([
                            'log_date' => now(), 
                            'log_kode' => "-", 
                            'log_type' => 2,
                            'log_category' => $l['cat'], 
                            'log_item_id' => $m->supplies_id,
                            'log_notes' => $l['note'], 
                            'log_jumlah' => $l['jumlah'], 
                            'unit_id' => $l['unit_id'],
                        ]);
                    }

                    return 1; // Berhasil menyiapkan stok
                } else {
                    return -1; // Stok total tidak mencukupi bahkan setelah konversi
                }
            }
        } else if ($data['tipe_return'] == 2) {
            $itemId = $data['product_variant_id'] ?? $data['item_id'];
            $s = ProductStock::where('product_variant_id','=',$itemId)->where('unit_id','=',$data["unit_id"])->first();
            $stocks = $s->ps_stock ?? 0;
            if ($stocks - $data["pid_qty"] < 0) {
                $butuhTersedia = $data["pid_qty"];

                // 1. Ambil semua stok untuk produk ini, urutkan dari unit terkecil ke terbesar
                $ss = ProductStock::where('product_variant_id', $itemId)
                    ->where('status', 1)
                    ->orderBy('ps_id', 'desc') 
                    ->get();

                if (count($ss) <= 0) {
                    return -1;
                }

                // 2. Buat Virtual Sandbox agar tidak langsung memotong DB jika akhirnya tidak cukup
                $virtualStock = [];
                $logSummary = []; 
                $keyTarget = null;

                foreach ($ss as $key => $stok) {
                    $virtualStock[$stok->ps_id] = [
                        'model' => $stok,
                        'current' => (float)$stok->ps_stock,
                        'unit_id' => $stok->unit_id,
                        'ps_id' => $stok->ps_id
                    ];
                    
                    // Tandai index mana yang merupakan unit_id yang diminta user
                    if ($stok->unit_id == $data["unit_id"]) {
                        $keyTarget = $key;
                    }
                }

                // Jika unit_id yang diminta user tidak terdaftar di stok produk ini
                if ($keyTarget === null) {
                    return -1;
                }

                // 3. FUNGSI REKURSIF: Bongkar satuan besar ke bawahnya
                $siapkanStok = function($targetKey, $units) use (&$virtualStock, &$logSummary, &$siapkanStok, $itemId) {
                    if (!isset($units[$targetKey + 1])) return false; 

                    $stokSekarang = $units[$targetKey];
                    $stokAtas = $units[$targetKey + 1];

                    if ($virtualStock[$stokAtas->ps_id]['current'] <= 0) {
                        $bisaBongkarAtas = $siapkanStok($targetKey + 1, $units);
                        if (!$bisaBongkarAtas) return false;
                    }

                    $sr = ProductRelation::where('product_variant_id', $itemId)
                        ->where('pr_unit_id_2', $stokSekarang->unit_id)
                        ->where('status', 1)
                        ->first();

                    if ($sr && $virtualStock[$stokAtas->ps_id]['current'] > 0) {
                        $virtualStock[$stokAtas->ps_id]['current'] -= 1;
                        $hasilBongkar = (float)$sr['pr_unit_value_2'];
                        $virtualStock[$stokSekarang->ps_id]['current'] += $hasilBongkar;

                        $baseOrder = $stokAtas->ps_id * 10; 

                        $logSummary[$stokAtas->unit_id . '_cat2'] = [
                            'unit_id' => $stokAtas->unit_id,
                            'jumlah' => ($logSummary[$stokAtas->unit_id . '_cat2']['jumlah'] ?? 0) + 1,
                            'cat' => 2,
                            'note' => "Konversi unit dari produk bermasalah (Bongkar)",
                            'sort_order' => $baseOrder
                        ];

                        $logSummary[$stokSekarang->unit_id . '_cat1'] = [
                            'unit_id' => $stokSekarang->unit_id,
                            'jumlah' => ($logSummary[$stokSekarang->unit_id . '_cat1']['jumlah'] ?? 0) + $hasilBongkar,
                            'cat' => 1,
                            'note' => "Konversi unit dari produk bermasalah (Hasil)",
                            'sort_order' => $baseOrder + 1
                        ];
                        
                        return true;
                    }
                    return false;
                };

                // 4. Eksekusi Penyiapan Stok
                $idTargetStok = $ss[$keyTarget]->ps_id;
                $safety = 0; 
                while ($virtualStock[$idTargetStok]['current'] < $butuhTersedia) {
                    $safety++;
                    if ($safety > 500) break; 
                    
                    $berhasil = $siapkanStok($keyTarget, $ss);
                    if (!$berhasil) break; 
                }

                // 5. FINAL CHECK & SAVE
                if ($virtualStock[$idTargetStok]['current'] >= $butuhTersedia) {
                    // Update DB
                    foreach ($virtualStock as $v) {
                        $v['model']->ps_stock = $v['current'];
                        $v['model']->save();
                    }
                    
                    // Insert Log Konversi
                    usort($logSummary, function($a, $b) {
                        return $a['sort_order'] <=> $b['sort_order'];
                    });

                    foreach ($logSummary as $l) {
                        (new LogStock())->insertLog([
                            'log_date' => now(), 
                            'log_kode' => "-", 
                            'log_type' => 1, // Type 2 biasanya untuk penyesuaian/produk bermasalah
                            'log_category' => $l['cat'], 
                            'log_item_id' => $itemId,
                            'log_notes' => $l['note'], 
                            'log_jumlah' => $l['jumlah'], 
                            'unit_id' => $l['unit_id'],
                        ]);
                    }

                    // Sekarang stok sudah siap di unit yang diminta. 
                    // Kamu tinggal panggil fungsi pengurangan stok utama kamu di sini.
                    return 1; 

                } else {
                    return -1; // Stok tetap tidak cukup meskipun sudah coba konversi
                }
            }
        }
        return 1;
    }
}