<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderDelivery;
use App\Models\SalesOrderDetailInvoice;
use App\Models\Customer;
use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrderDeliveryDetail;
use App\Models\SalesOrderDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class CustomerController extends Controller
{
    public function SalesOrder(){
        return view('Backoffice.Customers.Sales_Order');
    }

    public function SalesOrderDetail($id){
        $param["data"] = (new SalesOrder())->getSalesOrder(["so_id" => $id])[0];
        return view('Backoffice.Customers.Sales_Order_Detail')->with($param);
    }
    
    function getSalesOrder(Request $req){
        $data = (new SalesOrder())->getSalesOrder($req->all());
        return response()->json($data);
    }

    function insertSalesOrder(Request $req){
        $data = $req->all();
        $p = [];
        $valid = 1;
        // 1. AGGREGASI: Gunakan kombinasi Variant ID dan Unit ID agar Liter dan Piece tidak bercampur
        $aggregatedProducts = [];
        foreach (json_decode($data['products'], true) as $value) {
            // Kunci unik: gabungan ID Varian dan ID Satuan
            $uniqueKey = $value["product_variant_id"] . '_' . $value["unit_id"];
            $qtyJual = (int)$value["so_qty"];

            if (!isset($aggregatedProducts[$uniqueKey])) {
                $aggregatedProducts[$uniqueKey] = [
                    'variant_id' => $value["product_variant_id"],
                    'total_butuh' => 0,
                    'unit_id_jual' => $value["unit_id"],
                    'details' => $value
                ];
            }
            $aggregatedProducts[$uniqueKey]['total_butuh'] += $qtyJual;
        }

        // 2. PROCESSING: Penyiapan Stok (Konversi Otomatis)
        foreach ($aggregatedProducts as $key => $req) {
            $variantId = $req['variant_id']; // Ambil ID varian asli
            $butuhTersedia = $req['total_butuh'];
            $unitTarget = $req['unit_id_jual'];
            $val = $req['details'];

            $cekRelasi = ProductRelation::where('product_variant_id', $variantId)
                    ->where('status', 1)
                    ->exists();

            if (!$cekRelasi) {
                return response()->json([
                    "status" => 0,
                    "header" => "Gagal Insert",
                    "message" => "Mohon masukkan relasi produk: " . $val["product_variant_name"]
                ]);
            }

            // Ambil semua level stok produk ini
            $ss = ProductStock::where('product_variant_id', $variantId)
                ->where('status', 1)
                ->orderBy('ps_id', 'desc') 
                ->get();

            if (count($ss) > 0) {
                $virtualStock = [];
                $logSummary = []; 
                $keyTarget = null;

                foreach ($ss as $idx => $stok) {
                    $virtualStock[$stok->ps_id] = [
                        'model' => $stok,
                        'current' => (float)$stok->ps_stock,
                        'unit_id' => $stok->unit_id,
                        'ps_id' => $stok->ps_id
                    ];
                    // Pastikan kita membidik unit_id yang tepat untuk baris ini
                    if ($stok->unit_id == $unitTarget) { $keyTarget = $idx; }
                }

                if ($keyTarget === null) {
                    array_push($p, $val["pr_name"]." ".$val["product_variant_name"]);
                    $valid = -1;
                    continue;
                }

                // FUNGSI REKURSIF (Tetap sama, tidak ada perubahan)
                $siapkanStok = function($targetKey, $units) use (&$virtualStock, &$logSummary, &$siapkanStok, $variantId) {
                    if (!isset($units[$targetKey + 1])) return false; 
                    $stokSekarang = $units[$targetKey];
                    $stokAtas = $units[$targetKey + 1];
                    if ($virtualStock[$stokAtas->ps_id]['current'] <= 0) {
                        if (!$siapkanStok($targetKey + 1, $units)) return false;
                    }
                    $sr = ProductRelation::where('product_variant_id', $variantId)
                        ->where('pr_unit_id_2', $stokSekarang->unit_id)
                        ->where('status', 1)
                        ->first();
                    if ($sr && $virtualStock[$stokAtas->ps_id]['current'] > 0) {
                        $virtualStock[$stokAtas->ps_id]['current'] -= 1;
                        $hasilBongkar = (float)$sr['pr_unit_value_2'];
                        $virtualStock[$stokSekarang->ps_id]['current'] += $hasilBongkar;
                        $baseOrder = $stokAtas->ps_id * 10; 
                        $logSummary[$stokAtas->unit_id . '_cat2'] = [
                            'unit_id' => $stokAtas->unit_id, 'jumlah' => ($logSummary[$stokAtas->unit_id . '_cat2']['jumlah'] ?? 0) + 1,
                            'cat' => 2, 'note' => "Konversi unit dari pengiriman (Bongkar)", 'sort_order' => $baseOrder
                        ];
                        $logSummary[$stokSekarang->unit_id . '_cat1'] = [
                            'unit_id' => $stokSekarang->unit_id, 'jumlah' => ($logSummary[$stokSekarang->unit_id . '_cat1']['jumlah'] ?? 0) + $hasilBongkar,
                            'cat' => 1, 'note' => "Konversi unit dari pengiriman (Hasil)", 'sort_order' => $baseOrder + 1
                        ];
                        return true;
                    }
                    return false;
                };

                $idPalingBawah = $ss[$keyTarget]->ps_id;
                $safety = 0; 
                while ($virtualStock[$idPalingBawah]['current'] < $butuhTersedia) {
                    $safety++;
                    if ($safety > 500) break; 
                    if (!$siapkanStok($keyTarget, $ss)) break; 
                }

                if ($virtualStock[$idPalingBawah]['current'] >= $butuhTersedia) {
                    foreach ($virtualStock as $v) {
                        $v['model']->ps_stock = (int)$v['current'];
                        $v['model']->save();
                    }

                    usort($logSummary, function($a, $b) { return $a['sort_order'] <=> $b['sort_order']; });
                    foreach ($logSummary as $l) {
                        (new LogStock())->insertLog([
                            'log_date' => now(), 'log_kode' => "-", 'log_type' => 1, 'log_category' => $l['cat'], 
                            'log_item_id' => $variantId, 'log_notes' => $l['note'], 'log_jumlah' => $l['jumlah'], 'unit_id' => $l['unit_id'],
                        ]);
                    }

                    $stokFinal = ProductStock::find($idPalingBawah);
                    $stokFinal->ps_stock -= $butuhTersedia;
                    $stokFinal->save();
                } else {
                    array_push($p, $val["pr_name"]." ".$val["product_variant_name"]);
                    $valid = -1;
                }
            }
        }
        if($valid==-1){
            return implode(", ",$p);
        }
        $img = [];
        foreach (json_decode($data["so_img"]) as $key => $value) {
            $image = $value;

            // Hilangkan prefix base64
            $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);

            // Decode
            $imageData = base64_decode($image);

            // Nama file
            $imageName = 'photo_' . uniqid() . '.png';

            // Path tujuan di public/produksi
            $path = public_path('issue/' . $imageName);
            // Simpan file
            file_put_contents($path, $imageData);
            array_push($img, $imageName);
        }
        $data["so_img"] = json_encode($img);
        $so = (new SalesOrder())->insertSalesOrder($data);
        if ($so->so_id == -1){
            return -1;
        }
        foreach (json_decode($data['products'],true) as $key => $value) {
            $value['so_id'] = $so->so_id;
            (new SalesOrderDetail())->insertSalesOrderDetail($value);

            // Catat Log
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $so->so_number,
                'log_type'    => 1,
                'log_category' => 2,
                'log_item_id' => $value['product_variant_id'],
                'log_notes'  => "Pengiriman produk",
                'log_jumlah' => $value["so_qty"],
                'unit_id'    => $value['unit_id'],
            ]);
        }
        return 1;
    }

    function updateSalesOrder(Request $req){
        $data = $req->all();

        $p = [];
        $valid = 1;
        // --- TAHAP 1: REVERT STOK LAMA (PENTING: Agregasi Dulu Agar Log Tidak Double) ---
        $currentDetails = SalesOrderDetail::where('so_id', $data['so_id'])->where('status', '>=', 1)->get();
        $revertAgregat = [];

        foreach ($currentDetails as $oldDetail) {
            $key = $oldDetail->product_variant_id . '_' . $oldDetail->unit_id;
            if (!isset($revertAgregat[$key])) {
                $revertAgregat[$key] = [
                    'pvr_id' => $oldDetail->product_variant_id,
                    'unit_id' => $oldDetail->unit_id,
                    'qty' => 0
                ];
            }
            $revertAgregat[$key]['qty'] += $oldDetail->sod_qty;
        }

        // Eksekusi Revert Real ke DB & Log (Sekali per Satuan)
        foreach ($revertAgregat as $rev) {
            $sOld = ProductStock::where("product_variant_id", $rev['pvr_id'])
                ->where("unit_id", $rev['unit_id'])
                ->where("status", 1)
                ->first();
            
            if($sOld) {
                $sOld->ps_stock += $rev['qty'];
                $sOld->save();

                (new LogStock())->insertLog([
                    'log_date' => now(), 
                    'log_kode' => $data['so_number'], 
                    'log_type' => 1, 
                    'log_category' => 1,
                    'log_item_id' => $rev['pvr_id'], 
                    'log_notes' => "Update Pengiriman",
                    'log_jumlah' => $rev['qty'], 
                    'unit_id' => $rev['unit_id'],
                ]);
            }
        }

        // --- TAHAP 2: AGGREGASI DATA BARU (Sesuai Image 31ede1) ---
        $aggregatedProducts = [];
        $productsData = json_decode($data['products'], true);

        foreach ($productsData as $value) {
            $uniqueKey = $value["product_variant_id"] . '_' . $value["unit_id"];
            if (!isset($aggregatedProducts[$uniqueKey])) {
                $aggregatedProducts[$uniqueKey] = [
                    'variant_id' => $value["product_variant_id"],
                    'total_butuh' => 0,
                    'unit_id_jual' => $value["unit_id"],
                    'details' => $value
                ];
            }
            $aggregatedProducts[$uniqueKey]['total_butuh'] += (int)$value["so_qty"];
        }

        // --- TAHAP 3: VALIDASI & KONVERSI STOK OTOMATIS ---
        $p = []; $valid = 1;
        foreach ($aggregatedProducts as $req) {
            $variantId = $req['variant_id'];
            $butuhTersedia = $req['total_butuh'];
            $unitTarget = $req['unit_id_jual'];

            $ss = ProductStock::where('product_variant_id', $variantId)
                ->where('status', 1)
                ->orderBy('ps_id', 'desc')
                ->get();
            
            if (count($ss) > 0) {
                $virtualStock = []; $logSummary = []; $keyTarget = null;
                foreach ($ss as $idx => $stok) {
                    $virtualStock[$stok->ps_id] = [
                        'model' => $stok, 
                        'current' => (float)$stok->ps_stock, 
                        'unit_id' => $stok->unit_id, 
                        'ps_id' => $stok->ps_id
                    ];
                    if ($stok->unit_id == $unitTarget) { $keyTarget = $idx; }
                }

                if ($keyTarget === null) { $valid = -1; continue; }

                $siapkanStok = function($targetKey, $units) use (&$virtualStock, &$logSummary, &$siapkanStok, $variantId) {
                    if (!isset($units[$targetKey + 1])) return false; 
                    $stokSekarang = $units[$targetKey];
                    $stokAtas = $units[$targetKey + 1];

                    if ($virtualStock[$stokAtas->ps_id]['current'] <= 0) {
                        if (!$siapkanStok($targetKey + 1, $units)) return false;
                    }

                    $sr = ProductRelation::where('product_variant_id', $variantId)
                        ->where('pr_unit_id_2', $stokSekarang->unit_id)
                        ->where('status', 1)->first();

                    if ($sr && $virtualStock[$stokAtas->ps_id]['current'] > 0) {
                        $virtualStock[$stokAtas->ps_id]['current'] -= 1;
                        $hasilBongkar = (float)$sr['pr_unit_value_2'];
                        $virtualStock[$stokSekarang->ps_id]['current'] += $hasilBongkar;
                        
                        $baseOrder = $stokAtas->ps_id * 10; 
                        $logSummary[$stokAtas->unit_id . '_cat2'] = [
                            'unit_id' => $stokAtas->unit_id, 'jumlah' => ($logSummary[$stokAtas->unit_id . '_cat2']['jumlah'] ?? 0) + 1,
                            'cat' => 2, 'note' => "Konversi unit dari pengiriman (Bongkar)", 'sort_order' => $baseOrder
                        ];
                        $logSummary[$stokSekarang->unit_id . '_cat1'] = [
                            'unit_id' => $stokSekarang->unit_id, 'jumlah' => ($logSummary[$stokSekarang->unit_id . '_cat1']['jumlah'] ?? 0) + $hasilBongkar,
                            'cat' => 1, 'note' => "Konversi unit dari pengiriman (Hasil)", 'sort_order' => $baseOrder + 1
                        ];
                        return true;
                    }
                    return false;
                };

                $idPalingBawah = $ss[$keyTarget]->ps_id;
                $safety = 0; 
                while ($virtualStock[$idPalingBawah]['current'] < $butuhTersedia) {
                    $safety++; if ($safety > 500) break; 
                    if (!$siapkanStok($keyTarget, $ss)) break; 
                }

                if ($virtualStock[$idPalingBawah]['current'] >= $butuhTersedia) {
                    // Update Database Stok Hasil Bongkar
                    foreach ($virtualStock as $v) {
                        $v['model']->ps_stock = (int)$v['current'];
                        $v['model']->save();
                    }
                    // Simpan Log Konversi
                    usort($logSummary, function($a, $b) { return $a['sort_order'] <=> $b['sort_order']; });
                    foreach ($logSummary as $l) {
                        (new LogStock())->insertLog([
                            'log_date' => now(), 'log_kode' => "-", 'log_type' => 1, 'log_category' => $l['cat'], 
                            'log_item_id' => $variantId, 'log_notes' => $l['note'], 'log_jumlah' => $l['jumlah'], 'unit_id' => $l['unit_id']
                        ]);
                    }
                } else {
                    $valid = -1;
                    array_push($p, $req['details']['product_name']);
                }
            }
        }

        if($valid == -1) { return implode(", ", $p); }

        // --- TAHAP 4: UPDATE DATA SO & POTONG STOK FINAL ---
        $so = (new SalesOrder())->updateSalesOrder($data);
        $list_id_detail = [];

        foreach ($aggregatedProducts as $final) {
            $variantId = $final['variant_id'];
            $qtyTotal = $final['total_butuh'];
            $unitId = $final['unit_id_jual'];

            // Potong Stok Real
            $stokFinal = ProductStock::where('product_variant_id', $variantId)
                ->where('unit_id', $unitId)->first();
                
            if($stokFinal) {
                $stokFinal->ps_stock -= $qtyTotal;
                $stokFinal->save();

                (new LogStock())->insertLog([
                    'log_date' => now(), 'log_kode' => $data['so_number'], 'log_type' => 1, 'log_category' => 2, 
                    'log_item_id' => $variantId, 'log_notes' => "Update Pengiriman", 
                    'log_jumlah' => $qtyTotal, 'unit_id' => $unitId,
                ]);
            }
        }

        // Simpan detail transaksi ke tabel SalesOrderDetail
        foreach ($productsData as $val) {
            $val['so_id'] = $so->so_id;
            $id = isset($val["sod_id"]) ? (new SalesOrderDetail())->updateSalesOrderDetail($val) : (new SalesOrderDetail())->insertSalesOrderDetail($val);
            array_push($list_id_detail, $id);
        }

        SalesOrderDetail::where('so_id', $so->so_id)->whereNotIn('sod_id', $list_id_detail)->update(['status' => 0]);

        return 1;
    }

    function deleteSalesOrder(Request $req){
        $data = $req->all();
        (new SalesOrder())->deleteSalesOrder($data);
        $so = SalesOrder::find($data['so_id']);
        $v = SalesOrderDetail::where('so_id','=',$data["so_id"])->get();
        foreach ($v as $key => $value) {
            (new SalesOrderDetail())->deleteSalesOrderDetail($value);

            // Catat Log
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $so->so_number,
                'log_type'    => 1,
                'log_category' => 1,
                'log_item_id' => $value['product_variant_id'],
                'log_notes'  => "Pembatalan pengiriman produk",
                'log_jumlah' => $value["sod_qty"],
                'unit_id'    => $value['unit_id'],
            ]);
        }
    }

    function updateSalesOrderDetail(Request $req)
    {
        foreach (json_decode($req->so_detail, true) as $key => $value) {
            (new SalesOrderDetail())->updateSalesOrderDetail($value);
        }
    }

    function searchProduct(Request $req)
    {
        $data = (new ProductVariant())->getProductVariant(["search" => $req->search]);
        if (count($data) > 0) return response()->json($data[0]);
        else return -1;
    }

    function getSoDelivery(Request $req)
    {
        $data = (new SalesOrderDelivery())->getSoDelivery([
            "so_id" => $req->so_id
        ]);
        return response()->json($data);
    }

    function insertSoDelivery(Request $req)
    {
        $data = $req->all();
        $id = (new SalesOrderDelivery())->insertSoDelivery($data);
         foreach (json_decode($data['sdo_detail'], true) as $key => $value) {
            $value['sdo_id'] = $id;
            (new SalesOrderDeliveryDetail())->insertSoDeliveryDetail($value);
        }
    }

    function updateSoDelivery(Request $req)
    {
        $data = $req->all();
        $id = [];
        (new SalesOrderDelivery())->updateSoDelivery($data);
        foreach (json_decode($data['sdo_detail'], true) as $key => $value) {
            $value['sdo_id'] = $data["sdo_id"];
            $value['statusSO'] = $value["status"];
            if (!isset($value["sdod_id"])) $t = (new SalesOrderDeliveryDetail())->insertSoDeliveryDetail($value);
            else {

                $t = (new SalesOrderDeliveryDetail())->updateSoDeliveryDetail($value);
            }
            array_push($id, $t);
        }
        SalesOrderDeliveryDetail::where('sdo_id', '=', $data["sdo_id"])->whereNotIn("sdod_id", $id)->update(["status" => 0]);
    }

    function deleteSoDelivery(Request $req)
    {
        $data = $req->all();
        return (new SalesOrderDelivery())->deleteSoDelivery($data);
    }
    function accSoDelivery(Request $req)
    {
        $data = $req->all();
         $id = [];
        (new SalesOrderDelivery())->updateSoDelivery($data);
        (new SalesOrderDelivery())->statusSoDelivery($data);
        $status = SalesOrderDelivery::find($data["sdo_id"])->status; // approved
        foreach (json_decode($data['sdo_detail'], true) as $key => $value) {
            $value['sdo_id'] = $data["sdo_id"];
            $value['statusSO'] = $status;
            if (!isset($value["sdod_id"])) $t = (new SalesOrderDeliveryDetail())->insertSoDeliveryDetail($value);
            else {
                $t = (new SalesOrderDeliveryDetail())->updateSoDeliveryDetail($value);
            }
            array_push($id, $t);
        }
        SalesOrderDeliveryDetail::where('sdo_id', '=', $data["sdo_id"])->whereNotIn("sdod_id", $id)->update(["status" => 0]);
    }

    function declineSoDelivery(Request $req)
    {
        $data = $req->all();
        return (new SalesOrderDelivery())->statusSoDelivery($data);
    }

    function getSoInvoice(Request $req)
    {
        $data = (new SalesOrderDetailInvoice())->getSoInvoice($req->all());
        return response()->json($data);
    }

    function insertInvoiceSO(Request $req)
    {
        $data = $req->all();
        return (new SalesOrderDetailInvoice())->insertInvoiceSO($data);
    }

    function updateInvoiceSO(Request $req)
    {
        $data = $req->all();
        if (isset($req->image) && $req->image != "undefined") $data["supplier_image"] = (new HelperController)->insertFile($req->image, "supplier");
        return (new SalesOrderDetailInvoice())->updateInvoiceSO($data);
    }

    function deleteInvoiceSO(Request $req)
    {
        $data = $req->all();
        return (new SalesOrderDetailInvoice())->deleteInvoiceSO($data);
    }

    // Customer
    function customer() {
        return view('Backoffice.Customer.customer');
    }
    
    function customerDetail($id) {
       // $param["data"] =(new Customer())->getCustomer(["cus_id"=>$id])[0];
        $param["cus_id"] =$id;
        return view('Backoffice.Customer.CustomerDetails')->with($param);
    }
    function viewInsertCustomer() {
        $param["mode"] =1;
        $param["data"] =[];
        return view('Backoffice.Customer.insertCustomer')->with($param);
    }
    function ViewUpdateCustomer($id) {
        $param["mode"]=2; // 1 = insert, 2 = update
        $param["data"] = (new Customer())->getCustomer(["customer_id"=>$id])[0];
        return view('Backoffice.Customer.insertCustomer')->with($param);
    }

    function getCustomer(Request $req)
    {
        $data =  (new Customer())->getCustomer([
            "cus_name"=>$req->cus_name,
            "city_id"=>$req->city_id
        ]);
        return response()->json($data);
    }

    function insertCustomer(Request $req)
    {
        $data = $req->all();
        return (new Customer())->insertCustomer($data);
    }

    function updateCustomer(Request $req)
    {
        $data = $req->all();
        return (new Customer())->updateCustomer($data);
    }

    function deleteCustomer(Request $req)
    {
        $data = $req->all();
        return (new Customer())->deleteCustomer($data);
    }

    function declineInvoiceSO(Request $req)
    {
        $data = $req->all();
        return (new SalesOrderDetailInvoice())->changeStatusInvoiceSO($data);
    }
    function acceptInvoiceSO(Request $req)
    {
        $data = $req->all();
        return (new SalesOrderDetailInvoice())->changeStatusInvoiceSO($data);
    }
}
