<?php

namespace App\Http\Controllers;

use App\Models\LogStock;
use App\Models\ManageStock;
use App\Models\Product;
use App\Models\ProductIssues;
use App\Models\ProductIssuesDetail;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\ReturnSupplies;
use App\Models\ReturnSuppliesDetail;
use App\Models\Staff;
use App\Models\Stock;
use App\Models\StockAlert;
use App\Models\StockAlertSupplies;
use App\Models\StockOpname;
use App\Models\StockOpnameBahan;
use App\Models\StockOpnameDetail;
use App\Models\StockOpnameDetailBahan;
use App\Models\Supplier;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

use function Symfony\Component\Clock\now;

class StockController extends Controller
{
    // Stock Opname
    public function StockOpname()
    {
        return view('Backoffice.Inventory.Stock_Opname');
    }

    function getStockOpname(Request $req)
    {
        $data = (new StockOpname())->getStockOpname();
        return response()->json($data);
    }

    function insertStockOpname(Request $req)
    {
        $data = $req->all();
        $id =  (new StockOpname())->insertStockOpname($data);
        foreach (json_decode($req->item, true) as $key => $value) {
            $value["sto_id"] = $id;
            (new StockOpnameDetail())->insertDetail($value);
        }
    }

    // function updateStockOpname(Request $req)
    // {
    //     $data = $req->all();
    //     $id = (new StockOpname())->updateStockOpname($data);
    //     foreach (json_decode($req->item, true) as $key => $value) {
    //         $value["sto_id"] = $id;
    //         if (isset($value["stod_id"])) (new StockOpnameDetail())->updateDetail($value);
    //         else (new StockOpnameDetail())->insertDetail($value);
    //     }
    // }

    function deleteStockOpname(Request $req)
    {
        $data = $req->all();
        return (new StockOpname())->deleteStockOpname($data);
    }

    // Stock Opname Detail
    public function DetailStockOpname($id)
    {
        if ($id == -1) {
            return view('Backoffice.Inventory.CreateStockOpname', [
                'data' => [],
                'mode' => 1
            ]);
        }

        $sto = (new StockOpname())->getStockOpname(['sto_id' => $id])->first();
        $items = [];
        foreach ($sto->item as $detail) {
            $units = [];

            foreach ($detail->stock as $s) {
                $units[] = [
                    'unit_id'          => $s->unit_id,
                    'unit_short_name'  => $s->unit_short_name,
                    'system_qty'       => $this->getQty($detail->stod_system, $s->unit_short_name),
                    'real_qty'         => $this->getQty($detail->stod_real, $s->unit_short_name),
                    'selisih_qty'      => $this->getQty($detail->stod_selisih, $s->unit_short_name),
                ];
            }

            $items[] = [
                'product_id'           => $detail->product_id,
                'product_variant_id'   => $detail->product_variant_id,
                'product_variant_sku'  => $detail->product_variant_sku,
                'pr_name'              => $detail->pr_name,
                'product_variant_name' => $detail->product_variant_name,
                'stod_notes'           => $detail->stod_notes,
                'units'                => $units,
                'stod_system'          => $detail->stod_system,
                'stod_real'            => $detail->stod_real,
                'stod_selisih'         => $detail->stod_selisih,
            ];
        }

        $data = [
            'sto_id'      => $sto->sto_id,
            'sto_date'    => $sto->sto_date,
            'staff_id'    => $sto->staff_id,
            'staff_name'  => $sto->staff_name,
            'category_id' => $sto->category_id,
            'sto_notes'   => $sto->sto_notes,
            'status'      => $sto->status,
            'item'        => $items
        ];

        return view('Backoffice.Inventory.CreateStockOpname', [
            'data' => $data,
            'mode' => 2
        ]);
    }

    private function getQty($string, $unit)
    {
        // contoh: "12 jerigen, 0 DOS, 0 pcs"
        foreach (explode(',', $string) as $part) {
            [$qty, $u] = explode(' ', trim($part));
            if ($u === $unit) {
                return (int) $qty;
            }
        }
        return 0;
    }

    function getDetailStockOpname(Request $req)
    {
        $data = (new StockOpnameDetail())->getDetailStockOpname();
        return response()->json($data);
    }

    function insertDetailStockOpname(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameDetail())->insertDetailStockOpname($data);
    }

    function updateDetailStockOpname(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameDetail())->updateDetailStockOpname($data);
    }

    function deleteDetailStockOpname(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameDetail())->deleteDetailStockOpname($data);
    }

    function accStockOpname(Request $req) {
        $data = $req->all();
        $stod = json_decode($data['item'], true);
        $sto = StockOpname::find($data['sto_id']);
        foreach ($stod as $key => $value) {
            foreach ($value['units'] as $u) {
                $s = ProductStock::where('product_variant_id', $value['product_variant_id'])
                    ->where('unit_id', $u['unit_id'])
                    ->first();
                
                // Catat log
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $sto->sto_code,
                    'log_type'    => 1,
                    'log_category' => 2,
                    'log_item_id' => $value['product_variant_id'],
                    'log_notes'  => "Stock Opname Produk",
                    'log_jumlah' => $s->ps_stock,
                    'unit_id'    => $u['unit_id'],
                ]);

                $s->ps_stock = $u['real_qty'];
                $s->save();

                (new LogStock())->insertLog([
                    // Catat log
                    'log_date' => now(),
                    'log_kode'    => $sto->sto_code,
                    'log_type'    => 1,
                    'log_category' => 1,
                    'log_item_id' => $value['product_variant_id'],
                    'log_notes'  => "Stock Opname Produk",
                    'log_jumlah' => $s->ps_stock,
                    'unit_id'    => $u['unit_id'],
                ]);
            }
        }
        $sto->status = 2;
        $sto->save();
    }

    function tolakStockOpname(Request $req) {
        $data = $req->all();
        $sto = StockOpname::find($data["sto_id"]);

        $sto->status = 3; // Tolak
        $sto->save();
    }

    function generateStockOpname($id) {
        $param['stockOpname'] = StockOpname::find($id);
        $param['staff_name'] = Staff::find($param['stockOpname']['staff_id'])->first();
        $param["detail"] = (new StockOpnameDetail())->getDetail(['sto_id' => $id]);

        if ($param['stockOpname']['status'] == 1) $param['status'] = "Menunggu";
        else if ($param['stockOpname']['status'] == 2) $param['status'] = "Disetujui";
        else if ($param['stockOpname']['status'] == 3) $param['status'] = "Ditolak";

        if(count($param["detail"])<=0){
            return -1;
        }

        $pdf = Pdf::loadView('Backoffice.PDF.Opname', $param);
        return $pdf->download('Stock Opname_'.$param["stockOpname"]["sto_code"].'.pdf');
    }

    // Stock Opname
    public function StockOpnameBahan()
    {
        return view('Backoffice.Inventory.Stock_Opname_Bahan');
    }

    function getStockOpnameBahan(Request $req)
    {
        $data = (new StockOpnameBahan())->getStockOpnameBahan();
        return response()->json($data);
    }

    function insertStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        $id =  (new StockOpnameBahan())->insertStockOpnameBahan($data);
        foreach (json_decode($req->item, true) as $key => $value) {
            $value["stob_id"] = $id;
            (new StockOpnameDetailBahan())->insertDetail($value);
        }
    }

    function updateStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        $id = (new StockOpnameBahan())->updateStockOpnameBahan($data);
        foreach (json_decode($req->item, true) as $key => $value) {
            $value["stob_id"] = $id;
            if (isset($value["stod_id"])) (new StockOpnameDetailBahan())->updateDetail($value);
            else (new StockOpnameDetailBahan())->insertDetail($value);
        }
    }

    function deleteStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameBahan())->deleteStockOpnameBahan($data);
    }

    // Stock Opname Detail
    public function DetailStockOpnameBahan($id)
    {
        if ($id != -1) {
            $param["data"] = (new StockOpnameBahan())->getStockOpnameBahan(["stob_id" => $id])[0];
            $param["mode"] = 2;
        } else {
            $param["data"] = [];
            $param["mode"] = 1;
        }
        return view('Backoffice.Inventory.CreateStockOpnameSupplies')->with($param);
    }

    function getDetailStockOpnameBahan(Request $req)
    {
        $data = (new StockOpnameDetailBahan())->getDetailStockOpname();
        return response()->json($data);
    }

    function insertDetailStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameDetailBahan())->insertDetailStockOpname($data);
    }

    function updateDetailStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameDetailBahan())->updateDetailStockOpname($data);
    }

    function deleteDetailStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameDetailBahan())->deleteDetailStockOpname($data);
    }

    function accStockOpnameBahan(Request $req) {
        $data = $req->all();
        $stod = json_decode($data['item'], true);
        $stob = StockOpnameBahan::find($data['stob_id']);
        foreach ($stod as $key => $value) {
            foreach ($value['sp_units'] as $u) {
                $s = SuppliesStock::where('supplies_id', $value['supplies_id'])
                    ->where('unit_id', $u['unit_id'])
                    ->first();
                
                // Catat log
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $stob->stob_code,
                    'log_type'    => 2,
                    'log_category' => 2,
                    'log_item_id' => $value['supplies_id'],
                    'log_notes'  => "Stock Opname Bahan Mentah",
                    'log_jumlah' => $s->ss_stock,
                    'unit_id'    => $u['unit_id'],
                ]);

                $s->ss_stock = $u['real_qty'];
                $s->save();

                // Catat log
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $stob->stob_code,
                    'log_type'    => 2,
                    'log_category' => 1,
                    'log_item_id' => $value['supplies_id'],
                    'log_notes'  => "Stock Opname Bahan Mentah",
                    'log_jumlah' => $s->ss_stock,
                    'unit_id'    => $u['unit_id'],
                ]);
            }
        }
        $stob->status = 2;
        $stob->save();
    }

    function tolakStockOpnameBahan(Request $req) {
        $data = $req->all();
        $stob = StockOpnameBahan::find($data["stob_id"]);

        $stob->status = 3; // Tolak
        $stob->save();
    }

    function generateStockOpnameBahan($id) {
        $param['stockOpname'] = StockOpnameBahan::find($id);
        $param['staff_name'] = Staff::find($param['stockOpname']['staff_id'])->first();
        $param["detail"] = (new StockOpnameDetailBahan())->getDetail(['stob_id' => $id]);

        if ($param['stockOpname']['status'] == 1) $param['status'] = "Menunggu";
        else if ($param['stockOpname']['status'] == 2) $param['status'] = "Disetujui";
        else if ($param['stockOpname']['status'] == 3) $param['status'] = "Ditolak";

        if(count($param["detail"])<=0){
            return -1;
        }

        $pdf = Pdf::loadView('Backoffice.PDF.OpnameBahan', $param);
        return $pdf->download('Stock Opname_'.$param["stockOpname"]["stob_code"].'.pdf');
    }

    // Stock Alert
    public function StockAlert()
    {
        return view('Backoffice.Inventory.Stock_Alert');
    }

    function getStockAlert(Request $req)
    {
        $data = (new StockAlert())->getStockAlert(["mode" => $req->mode]);
        return response()->json($data);
    }

    function insertStockAlert(Request $req)
    {
        $data = $req->all();
        return (new StockAlert())->insertStockAlert($data);
    }

    function updateStockAlert(Request $req)
    {
        $data = $req->all();
        return (new StockAlert())->updateStockAlert($data);
    }

    function deleteStockAlert(Request $req)
    {
        $data = $req->all();
        return (new StockAlert())->deleteStockAlert($data);
    }

    // Stock Alert Supplies
    public function StockAlertSupplies()
    {
        return view('Backoffice.Inventory.Stock_Alert_Supplies');
    }

    function getStockAlertSupplies(Request $req)
    {
        $data = (new StockAlertSupplies())->getStockAlertSupplies(["mode" => $req->mode]);
        return response()->json($data);
    }

    function insertStockAlertSupplies(Request $req)
    {
        $data = $req->all();
        return (new StockAlertSupplies())->insertStockAlertSupplies($data);
    }

    function updateStockAlertSupplies(Request $req)
    {
        $data = $req->all();
        return (new StockAlertSupplies())->updateStockAlertSupplies($data);
    }

    function deleteStockAlertSupplies(Request $req)
    {
        $data = $req->all();
        return (new StockAlertSupplies())->deleteStockAlertSupplies($data);
    }


    // Product Issues
    public function ProductIssue()
    {
        return view('Backoffice.Inventory.Product_Issues');
    }

    function getProductIssue(Request $req)
    {
        $data = (new ProductIssues())->getProductIssues([
            "pi_type" => $req->pi_type,
            "tipe_return" => $req->tipe_return,
            "date" => $req->date,
        ]);
        return response()->json($data);
    }

    function insertProductIssue(Request $req)
    {
        $data = $req->all();
        // Ambil base64
        if (isset($req->photo)){
            $image = $req->photo;
    
            // Hilangkan prefix base64
            $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
    
            // Decode
            $imageData = base64_decode($image);
    
            // Nama file
            $imageName = 'photo_' . time() . '.png';
    
            // Path tujuan di public/produksi
            $path = public_path('issue/' . $imageName);
            // Simpan file
            file_put_contents($path, $imageData);
            $data["pi_img"] = $imageName;
        }

        if ($data['tipe_return'] == 1){
            $bermasalah = [];
            foreach (json_decode($data['items'], true) as $key => $value) { 
                // Pengecekan invoice
                if (isset($data['ref_num'])){
                    $inv = PurchaseOrderDetailInvoice::find($data['ref_num']);
                    $po = PurchaseOrder::find($inv->po_id);
                    $pod = PurchaseOrderDetail::where('po_id', $po->po_id)->get();
                    
                    $ada = -1;
                    foreach ($pod as $key => $detail) {
                        if ($detail['supplies_variant_id'] == $value['supplies_variant_id'] && $detail['unit_id'] == $value['unit_id']) {
                            $ada = 1;
                        }
                    }
                    if ($ada == -1) {
                        array_push($bermasalah, $value['supplies_name']);
                    }
                }
            }
            if (count($bermasalah) > 0) {
                return [
                    "status"=>-1,
                    "message"=>"Bahan tidak ditemukan dalam invoice : ".implode(", ",$bermasalah)
                ];
            }
    
            foreach (json_decode($data['items'], true) as $key => $value) {
                $value['tipe_return'] = $data['tipe_return'];
                // Pengecekan stock
                $c = (new ProductIssuesDetail())->stockCheck($value);
                if ($c == -1) return -1;
            }
        }

        // insert
        $t = (new ProductIssues())->insertProductIssues($data);
        foreach (json_decode($data['items'], true) as $key => $value) {
            $value['pi_id'] = $t->pi_id;
            if (isset($t->ref_num)) $value['ref_num'] = $t->ref_num;
            (new ProductIssuesDetail())->insertProductIssuesDetail($value);

            // Catat Log
            $logNotes = "";
            $logCategory = 0;
            $logType = 0;
            $itemId = 0;
            if ($t->tipe_return == 1){
                $sup = SuppliesVariant::find($value['supplies_variant_id']);
                $spr = Supplier::find($sup->supplier_id);
                $logNotes = 'Produk bermasalah retur supplier ' . $spr->supplier_name;
                $logCategory = 2;
                $logType = 2;

                $itemId = $sup->supplies_id;
            } elseif ($t->tipe_return == 2){
                $logNotes = 'Produk bermasalah retur Armada';
                $logCategory = 1;
                $logType = 1;
                $itemId = $value['product_variant_id'];
            }
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $t->pi_code,
                'log_type'    => $logType,
                'log_category' => $logCategory,
                'log_item_id' => $itemId,
                'log_notes'  => $logNotes,
                'log_jumlah' => $value['pid_qty'],
                'unit_id'    => $value['unit_id'],
            ]);
        }
    }

    function updateProductIssue(Request $req)
    {
        $data = $req->all();
        $image = $req->photo;

        // Hilangkan prefix base64
        $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);

        // Decode
        $imageData = base64_decode($image);

        // Nama file
        $imageName = 'photo_' . time() . '.png';

        // Path tujuan di public/produksi
        $path = public_path('issue/' . $imageName);
        // Simpan file
        file_put_contents($path, $imageData);
        $data["pi_img"] = $imageName;
        
        $id = [];
        $pi = (new ProductIssues())->updateProductIssues($data);

        // Cek stock
        $getPi = ProductIssuesDetail::where('pi_id', $pi->pi_id)->where('status', '>=', 1)->get();
        if (count($getPi) > 0) {
            foreach ($getPi as $key => $val) {
                foreach (json_decode($data['items'], true) as $key => $value) {
                    if ($data['tipe_return'] == 1) {
                        if ($value['supplies_variant_id'] == $val['item_id'] && $value['unit_id'] == $val['unit_id']){
                            $val['tipe_return'] = $data['tipe_return'];
                            $val['pid_qty'] = $value['pid_qty'];
                            $c = (new ProductIssuesDetail())->stockCheck($val);
                            if ($c == -1) return -1;
                        }
                    }
                    if ($data['tipe_return'] == 2) {
                        if ($value['product_variant_id'] == $val['item_id'] && $value['unit_id'] == $val['unit_id']){
                            $val['tipe_return'] = $data['tipe_return'];
                            $val['pid_qty'] = $value['pid_qty'];
                            $c = (new ProductIssuesDetail())->stockCheck($val);
                            if ($c == -1) return -1;
                        }
                    }
                }
            }
        }
        // Pengecekan invoice
        if (isset($pi->ref_num) && $pi->ref_num > 0){
            $bermasalah = [];
            foreach (json_decode($data['items'], true) as $key => $value) {
                $inv = PurchaseOrderDetailInvoice::find($pi->ref_num);
                $po = PurchaseOrder::find($inv->po_id);
                $pod = PurchaseOrderDetail::where('po_id', $po->po_id)->get();
                
                $ada = -1;
                foreach ($pod as $key => $detail) {
                    if ($detail['supplies_variant_id'] == $value['supplies_variant_id'] && $detail['unit_id'] == $value['unit_id']) {
                        $ada = 1;
                        break;
                    }
                }
                if ($ada == -1) {
                    array_push($bermasalah, $value['supplies_name']);
                }
            }
            if (count($bermasalah) > 0) {
                return [
                    "status"=>-1,
                    "message"=>"Bahan tidak ditemukan dalam invoice : ".implode(", ",$bermasalah)
                ];
            }

            // Kembalikan stock semua
            $getPi = ProductIssuesDetail::where('pi_id', $pi->pi_id)->where('status', '>=', 1)->get();
            if (count($getPi) > 0) {
                foreach ($getPi as $key => $val) {
                    // Kembalikan stock invoice
                    $total = 0;
                    foreach ($pod as $key => $detail) {
                        if ($val->item_id == $detail['supplies_variant_id'] && $val->unit_id == $detail['unit_id']){
                            $detail['pod_qty'] += $val['pid_qty'];
                            $detail['pod_subtotal'] = $detail['pod_harga'] * $detail['pod_qty'];
                            $detail->save();
                        }
                        $total += $detail['pod_subtotal'];
                    }
                    $total -= $total * $po->po_discount/100;
                    $total += $total * $po->po_ppn/100;
                    $total += $po->po_cost;
                    
                    $inv->poi_total = $total;
                    $inv->save();
                    $po->po_total = $total;
                    $po->save();

                    // Kembalikan stock
                    $svr = SuppliesVariant::find($val->item_id);
                    $ss = SuppliesStock::where('supplies_id', $svr->supplies_id)->where('unit_id', $val->unit_id)->first();
                    $ss->ss_stock += $val['pid_qty'];
                    $ss->save();
                    
                    // Catat Log
                    $logNotes = "";
                    $spr = Supplier::find($svr->supplier_id);
                    $logNotes = 'Perubahan data produk bermasalah retur supplier ' . $spr->supplier_name;
                    (new LogStock())->insertLog([
                        'log_date' => now(),
                        'log_kode'    => $pi->pi_code,
                        'log_type'    => 2,
                        'log_category' => 1,
                        'log_item_id' => $svr->supplies_id,
                        'log_notes'  => $logNotes,
                        'log_jumlah' => $val['pid_qty'],
                        'unit_id'    => $val['unit_id'],
                    ]);
                }
            }
        }
        
        foreach (json_decode($data['items'], true) as $key => $value) {
            $value['pi_id'] = $data["pi_id"];
            if (isset($pi->ref_num)) $value['ref_num'] = $pi->ref_num;

            if (!isset($value["pid_id"])) {
                if ($pi->tipe_return == 2){
                    $getPi = ProductIssuesDetail::where('pi_id', $pi->pi_id)->where('status', '>=', 1)->get();
                    if (count($getPi) > 0) {
                        foreach ($getPi as $key => $val) {
                            $ps = ProductStock::where('product_variant_id', $value['product_variant_id'])->where('unit_id', $val->unit_id)->first();

                            $ps->ps_stock -= $val->pid_qty;
                            $ps->save();

                            // Catat Log
                            $logNotes = "";
                            $logNotes = 'Perubahan data produk bermasalah retur Armada';
                            (new LogStock())->insertLog([
                                'log_date' => now(),
                                'log_kode'    => $pi->pi_code,
                                'log_type'    => 1,
                                'log_category' => 2,
                                'log_item_id' => $value['product_variant_id'],
                                'log_notes'  => $logNotes,
                                'log_jumlah' => $val['pid_qty'],
                                'unit_id'    => $val['unit_id'],
                            ]);
                        }
                    }
                }
                
                // Pengurangan stock
                $t = (new ProductIssuesDetail())->insertProductIssuesDetail($value);
                
                // Catat Log
                $logNotes = "";
                $logCategory = 0;
                $logType = 0;
                $itemId = 0;
                if ($pi->tipe_return == 1){
                    $sup = SuppliesVariant::find($value['supplies_variant_id']);
                    $spr = Supplier::find($sup->supplier_id);
                    
                    $logNotes = 'Perubahan data produk bermasalah retur supplier ' . $spr->supplier_name;
                    $logCategory = 2;
                    $logType = 2;

                    $itemId = $sup->supplies_id;
                } elseif ($pi->tipe_return == 2){
                    $logNotes = 'Perubahan data produk bermasalah retur Armada';
                    $logCategory = 1;
                    $logType = 1;
                    $itemId = $value['product_variant_id'];
                }
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $pi->pi_code,
                    'log_type'    => $logType,
                    'log_category' => $logCategory,
                    'log_item_id' => $itemId,
                    'log_notes'  => $logNotes,
                    'log_jumlah' => $value['pid_qty'],
                    'unit_id'    => $value['unit_id'],
                ]);
            }
            else {
                
                $t = (new ProductIssuesDetail())->updateProductIssuesDetail($value);

                // Catat Log
                $logNotes = "";
                $logCategory = 0;
                $logType = 0;
                $itemId = 0;
                if ($pi->tipe_return == 1){
                    $sup = SuppliesVariant::find($value['supplies_variant_id']);
                    $spr = Supplier::find($sup->supplier_id);
                    
                    $logNotes = 'Perubahan data produk bermasalah retur supplier ' . $spr->supplier_name;
                    $logCategory = 2;
                    $logType = 2;
                    $itemId = $sup->supplies_id;

                } elseif ($pi->tipe_return == 2){
                    $logNotes = 'Perubahan data produk bermasalah retur Armada';
                    $logCategory = 1;
                    $logType = 1;
                    $itemId = $value['product_variant_id'];
                }

                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $pi->pi_code,
                    'log_type'    => $logType,
                    'log_category' => $logCategory,
                    'log_item_id' => $itemId,
                    'log_notes'  => $logNotes,
                    'log_jumlah' => $value['pid_qty'],
                    'unit_id'    => $value['unit_id'],
                ]);
            }
            array_push($id, $t);
            
        }
        ProductIssuesDetail::where('pi_id', '=', $data["pi_id"])->whereNotIn("pid_id", $id)->update(["status" => 0]);
    }

    function deleteProductIssue(Request $req)
    {
        $data = $req->all();

        $pi = ProductIssues::find($data['pi_id']);
        $v = ProductIssuesDetail::where('pi_id','=',$data["pi_id"])->where('status', '>=', 1)->get();
        if ($pi->tipe_return == 2){
            foreach ($v as $key => $value) {
                $value['tipe_return'] = $pi['tipe_return'];
                $c = (new ProductIssuesDetail())->stockCheck($value);
                if ($c == -1) return -1;
            }
        }
        $del = (new ProductIssues())->deleteProductIssues($data);
        if ($del == -1){
            return response()->json([
                "status" => 0,
                "header" => "Gagal Delete",
                "message" => "Invoice tersebut sudah terbayar"
            ]);
        }

        // Delete retur (header)
        $rs = ReturnSupplies::where('pi_id', $data['pi_id'])->where('status', 1)->first();
        if ($rs) (new ReturnSupplies())->deleteReturnSupplies($rs);

        foreach ($v as $key => $value) {
            $value['tipe_return'] = $pi->tipe_return;
            if (isset($pi->ref_num) && $pi->ref_num != 0) $value['ref_num'] = $pi->ref_num;
            
            // Hapus retur kalau ada
            if ($rs){
                $rsd = ReturnSuppliesDetail::where('pid_id', $value['pid_id'])
                                        ->where('supplies_variant_id', $value['item_id'])
                                        ->where('unit_id', $value['unit_id'])
                                        ->where('status', 1)->first();
                (new ReturnSuppliesDetail())->deleteReturnSuppliesDetail($rsd);
            }

            (new ProductIssuesDetail())->deleteProductIssuesDetail($value);

            // Catat Log
            $logNotes = "";
            $logCategory = 0;
            $logType = 0;
            $itemId = 0;
            if ($pi->tipe_return == 1){
                $sup = SuppliesVariant::find($value['item_id']);
                $spr = Supplier::find($sup->supplier_id);
                
                $logNotes = 'Penghapusan data produk bermasalah retur supplier ' . $spr->supplier_name;
                $logCategory = 1;
                $logType = 2;

                $itemId = $sup->supplies_id;
            } elseif ($pi->tipe_return == 2){
                $logNotes = 'Penghapusan data produk bermasalah retur Armada';
                $logCategory = 2;
                $logType = 1;
                $itemId = $value['item_id'];
            }
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $pi->pi_code,
                'log_type'    => $logType,
                'log_category' => $logCategory,
                'log_item_id' => $itemId,
                'log_notes'  => $logNotes,
                'log_jumlah' => $value['pid_qty'],
                'unit_id'    => $value['unit_id'],
            ]);
        }
    }

    // Manage Stock
    public function ManageStock()
    {
        return view('Backoffice.Inventory.Manage_Stock');
    }

    function getManageStock(Request $req)
    {
        $data = (new ManageStock())->getManageStocks();
        return response()->json($data);
    }
    function insertManageStocks(Request $req)
    {
        $data = $req->all();
        return (new ManageStock())->insertManageStock($data);
    }
    // Stock
    public function Stock()
    {
        return view('Backoffice.Inventory.Stock_Product');
    }

    function getStock(Request $req)
    {
        $data = (new ProductVariant())->getProductVariant();
        foreach ($data as $key => $value) {
            $value->stock = (new ProductStock())->getProductStock(["product_variant_id" => $value->product_variant_id]);
        }
        return response()->json($data);
    }

    // Stock supplies
    public function StockSupplies()
    {
        return view('Backoffice.Inventory.Stock_Supplies');
    }

    function getStockSupplies(Request $req)
    {
        // $data = (new SuppliesVariant())->getSuppliesVariant();
        //$data = (new SuppliesStock())->getProductStock();
        $data = (new Supplies())->getSupplies();
        return response()->json($data);
    }
}
