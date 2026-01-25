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
        foreach (json_decode($data['products'],true) as $key => $value) {
            $ps = ProductVariant::find($value["product_variant_id"]);
            $s = ProductStock::where("product_variant_id", "=", $ps->product_variant_id)
                ->where("unit_id", "=", $value["unit_id"])
                ->where("status", "=", 1)
                ->first();
            if($s->ps_stock < $value["so_qty"]){
                $butuhTersedia = $value["so_qty"]; // Jumlah yang dibutuhkan untuk penjualan

                // Ambil stok produk, urutkan DESC (Unit terkecil ke terbesar)
                $ss = ProductStock::where('product_variant_id', $value['product_variant_id'])
                    ->where('status', 1)
                    ->orderBy('ps_id', 'desc') 
                    ->get();

                if (count($ss) <= 0) {
                    array_push($p, $value["pr_name"]." ".$value["product_variant_name"]);
                    $valid = -1;
                } else {
                    $virtualStock = [];
                    $logSummary = []; 

                    foreach ($ss as $stok) {
                        $virtualStock[$stok->ps_id] = [
                            'model' => $stok,
                            'current' => (float)$stok->ps_stock,
                            'unit_id' => $stok->unit_id,
                            'ps_id' => $stok->ps_id
                        ];
                    }

                    // FUNGSI REKURSIF: Menyiapkan stok produk dengan membongkar satuan di atasnya
                    $siapkanStok = function($targetKey, $units) use (&$virtualStock, &$logSummary, &$siapkanStok, $value) {
                        if (!isset($units[$targetKey + 1])) return false; 

                        $stokSekarang = $units[$targetKey];
                        $stokAtas = $units[$targetKey + 1];

                        // Jika level atas kosong, bongkar level yang lebih atas lagi
                        if ($virtualStock[$stokAtas->ps_id]['current'] <= 0) {
                            $bisaBongkarAtas = $siapkanStok($targetKey + 1, $units);
                            if (!$bisaBongkarAtas) return false;
                        }

                        // Cek relasi antar unit produk
                        $sr = ProductRelation::where('product_variant_id', $value['product_variant_id'])
                            ->where('pr_unit_id_2', $stokSekarang->unit_id)
                            ->where('status', 1)
                            ->first();

                        if ($sr && $virtualStock[$stokAtas->ps_id]['current'] > 0) {
                            // Eksekusi bongkar virtual per 1 unit
                            $virtualStock[$stokAtas->ps_id]['current'] -= 1;
                            $hasilBongkar = (float)$sr['pr_unit_value_2'];
                            $virtualStock[$stokSekarang->ps_id]['current'] += $hasilBongkar;

                            // Grouping Log: Unit besar (ps_id kecil) diletakkan di atas
                            $baseOrder = $stokAtas->ps_id * 10; 

                            $keyBongkar = $stokAtas->unit_id . '_cat2';
                            $logSummary[$keyBongkar] = [
                                'unit_id' => $stokAtas->unit_id,
                                'jumlah' => ($logSummary[$keyBongkar]['jumlah'] ?? 0) + 1,
                                'cat' => 2,
                                'note' => "Konversi unit dari penjualan (Bongkar)",
                                'sort_order' => $baseOrder
                            ];

                            $keyHasil = $stokSekarang->unit_id . '_cat1';
                            $logSummary[$keyHasil] = [
                                'unit_id' => $stokSekarang->unit_id,
                                'jumlah' => ($logSummary[$keyHasil]['jumlah'] ?? 0) + $hasilBongkar,
                                'cat' => 1,
                                'note' => "Konversi unit dari penjualan (Hasil)",
                                'sort_order' => $baseOrder + 1
                            ];
                            
                            return true;
                        }
                        return false;
                    };

                    // Proses Penyiapan Stok Penjualan
                    $keyPalingBawah = 0;
                    $idPalingBawah = $ss[$keyPalingBawah]->ps_id;
                    $safety = 0; 

                    while ($virtualStock[$idPalingBawah]['current'] < $butuhTersedia) {
                        $safety++;
                        if ($safety > 500) break; 
                        
                        $berhasil = $siapkanStok($keyPalingBawah, $ss);
                        if (!$berhasil) break; 
                    }

                    // --- FINAL EKSEKUSI ---
                    if ($virtualStock[$idPalingBawah]['current'] >= $butuhTersedia) {
                        // 1. Update stok fisik produk ke Database
                        foreach ($virtualStock as $v) {
                            $v['model']->ps_stock = $v['current'];
                            $v['model']->save();
                        }
                        
                        // 2. Urutkan Log (Unit Besar -> Unit Kecil)
                        usort($logSummary, function($a, $b) {
                            return $a['sort_order'] <=> $b['sort_order'];
                        });

                        foreach ($logSummary as $l) {
                            (new LogStock())->insertLog([
                                'log_date' => now(), 
                                'log_kode' => "-", 
                                'log_type' => 1, // log_type penjualan
                                'log_category' => $l['cat'], 
                                'log_item_id' => $value['product_variant_id'],
                                'log_notes' => $l['note'], 
                                'log_jumlah' => $l['jumlah'], 
                                'unit_id' => $l['unit_id'],
                            ]);
                        }
                        
                        // Stok sudah tersedia di level unit penjualan. 
                        // Silakan panggil fungsi pengurangan stok penjualan milikmu di sini.

                    } else {
                        // Jika stok total tetap tidak cukup
                        array_push($p, $value["pr_name"]." ".$value["product_variant_name"]);
                        $valid = -1;
                    }
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
                'log_notes'  => "Penjualan produk",
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
        foreach (json_decode($data['products'],true) as $key => $value) {
            if(isset($value["sod_id"])) {
                $sod = SalesOrderDetail::find($value["sod_id"]);
                $ps = ProductVariant::find($value["product_variant_id"]);
                $s = ProductStock::where("product_variant_id", "=", $ps->product_variant_id)
                    ->where("unit_id", "=", $value["unit_id"])
                    ->where("status", "=", 1)
                    ->first();
                if(($s->ps_stock+$sod->sod_qty) < $value["so_qty"]){
                    array_push($p, $value["pr_name"]." ".$value["product_variant_name"]);
                    $valid=-1;
                }
            }
        }
        
        if($valid==-1){
            return implode(", ",$p);
        }

        
        $list_id_detail = [];
        $so = (new SalesOrder())->updateSalesOrder($data);
        if ($so->so_id == -1){
            return -1;
        }
        foreach (json_decode($req->products,true) as $key => $value) {
            $value['so_id'] = $so->so_id;

            if(!isset($value["sod_id"])) {
                $getSo = SalesOrderDetail::where('so_id', $so->so_id)->where('status', '>=', 1)->get();

                if (count($getSo)){
                    foreach ($getSo as $key => $val) {
                        $pvr = ProductVariant::find($value["product_variant_id"]);
                        $s = ProductStock::where("product_variant_id", "=", $pvr->product_variant_id)
                            ->where("unit_id", "=", $value["unit_id"])
                            ->where("status", "=", 1)
                            ->first();
                        $s->ps_stock += $val["sod_qty"];
                        $s->save();

                        // Catat Log
                        (new LogStock())->insertLog([
                            'log_date' => now(),
                            'log_kode'    => $so->so_number,
                            'log_type'    => 1,
                            'log_category' => 1,
                            'log_item_id' => $val['product_variant_id'],
                            'log_notes'  => "Perubahan penjualan produk",
                            'log_jumlah' => $val["sod_qty"],
                            'unit_id'    => $val['unit_id'],
                        ]);
                    }
                }

                $id = (new SalesOrderDetail())->insertSalesOrderDetail($value);

                // Catat Log
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $so->so_number,
                    'log_type'    => 1,
                    'log_category' => 2,
                    'log_item_id' => $value['product_variant_id'],
                    'log_notes'  => "Perubahan penjualan produk",
                    'log_jumlah' => $value["so_qty"],
                    'unit_id'    => $value['unit_id'],
                ]);
            }
            else {
                $sod = SalesOrderDetail::find($value['sod_id']);
                // Catat Log
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $so->so_number,
                    'log_type'    => 1,
                    'log_category' => 1,
                    'log_item_id' => $sod['product_variant_id'],
                    'log_notes'  => "Perubahan penjualan produk",
                    'log_jumlah' => $sod["sod_qty"],
                    'unit_id'    => $sod['unit_id'],
                ]);
                $id = (new SalesOrderDetail())->updateSalesOrderDetail($value);

                // Catat Log
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $so->so_number,
                    'log_type'    => 1,
                    'log_category' => 2,
                    'log_item_id' => $value['product_variant_id'],
                    'log_notes'  => "Perubahan penjualan produk",
                    'log_jumlah' => $value["so_qty"],
                    'unit_id'    => $value['unit_id'],
                ]);
            }
            array_push($list_id_detail, $id);
        }
        SalesOrderDetail::where('so_id','=',$so->so_id)->whereNotIn('sod_id', $list_id_detail)->update(['status' => 0]);
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
                'log_notes'  => "Pembatalan penjualan produk",
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
