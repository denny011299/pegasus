<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\LogStock;
use App\Models\ProductIssues;
use App\Models\ProductIssuesDetail;
use App\Models\purchase_order_tt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderDeliveryDetail;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\PurchaseOrderReceipt;
use App\Models\ReturnSupplies;
use App\Models\ReturnSuppliesDetail;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Support\UnitRollUp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SupplierController extends Controller
{
    private function mergePdfPrintMeta(array $param): array
    {
        $u = session()->get('user') ?? Session::get('user');
        $param['printed_by'] = $u ? ($u->staff_name ?? '-') : '-';
        $param['printed_at'] = now()->format('d/m/Y H:i');
        return $param;
    }

    function PurchaseOrder()
    {
        return view('Backoffice.Suppliers.Purchase_Order');
    }

    function PurchaseOrderDetail($id)
    {
        $param["data"] = (new PurchaseOrder())->getPurchaseOrder(["po_id" => $id, "with_items" => true])[0];
        return view('Backoffice.Suppliers.Purchase_Order_Detail')->with($param);
    }
    function PurchaseOrderDetailHutang($id)
    {
        $param["data"] = (new PurchaseOrder())->getPurchaseOrder(["po_id" => $id, "hutang" => 1, "with_items" => true])[0];
        return view('Backoffice.Suppliers.Purchase_Order_Detail')->with($param);
    }

    function getPurchaseOrder(Request $req)
    {
        $data = (new PurchaseOrder())->getPurchaseOrder($req->all());
        return response()->json($data);
    }

    function InsertPurchaseOrder(Request $req)
    {
        $data = $req->all();
        $img = [];

        foreach (json_decode($data["po_img"]) as $key => $value) {
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

        $data["po_img"] = json_encode($img);
        $data = (new PurchaseOrder())->InsertPurchaseOrder($data);
        foreach (json_decode($req->po_detail, true) as $key => $value) {
            $value["po_id"] = $data;
            $value["pod_nama"] = $value["supplies_name"];
            $value["pod_variant"] = $value["supplies_variant_name"];
            $value["pod_sku"] = $value["supplies_variant_sku"];
            $value["pod_qty"] = $value["qty"];
            $value["pod_harga"] = $value["supplies_variant_price"];
            $value["pod_subtotal"] = intval($value["pod_qty"]) * intval($value["pod_harga"]);


            (new PurchaseOrderDetail())->insertPurchaseOrderDetail($value);
        }
        return response()->json($data);
    }

    function deletePurchaseOrder(Request $req)
    {
        $data = (new PurchaseOrder())->deletePurchaseOrder($req->all());
        return response()->json($data);
    }

    function getPurchaseOrderDetail(Request $req)
    {
        $data = (new PurchaseOrderDetail())->getPurchaseOrderDetail($req->all());
        return response()->json($data);
    }

    function updatePurchaseOrderDetail(Request $req)
    {
        $total = 0;
        foreach (json_decode($req->po_detail, true) as $key => $value) {
            $total += $value["pod_subtotal"];
            (new PurchaseOrderDetail())->updatePurchaseOrderDetail($value);
        }
        $retur = ReturnSupplies::where('po_id', '=', $req->po_id)->where('status', 1)->get();
        if ($retur->count() > 0){
            foreach ($retur as $key => $val) {
                $total -= $val->rs_total;
            }
        }
        $p = PurchaseOrder::find($req->po_id);
        $p->po_total = $total;
        $p->save();

    }

    function searchSupplies(Request $req)
    {
        $data = (new SuppliesVariant())->getSuppliesVariant(["search" => $req->search]);
        if (count($data) > 0) return response()->json($data[0]);
        else return -1;
    }

    // DEPRECATED (2026-08-04): the manual/partial Purchase Order Delivery workflow (getPoDelivery
    // through declinePoDelivery below) is no longer used — confirmed by product owner. It let a
    // delivery batch flip purchase_orders.status to Approved with no stock check and no invoice
    // creation (accPO is the only place that happens), permanently locking the PO out of accPO
    // afterward. Not fixed, not tested. See KNOWN_ISSUES.md.
    //
    // NOT deprecated: accPO() (below) still calls PurchaseOrderDelivery::insertPoDelivery() and
    // PurchaseOrderDeliveryDetail::insertPoDeliveryDetail() directly to record its own
    // auto-generated, already-approved delivery — those two model methods stay fully active. Only
    // the independent manual create/edit/approve/decline actions below are deprecated.
    function getPoDelivery(Request $req)
    {
        $data = (new PurchaseOrderDelivery())->getPoDelivery([
            "po_id" => $req->po_id
        ]);
        return response()->json($data);
    }

    function insertPoDelivery(Request $req)
    {
        $data = $req->all();
 
        $id = (new PurchaseOrderDelivery())->insertPoDelivery($data);
         foreach (json_decode($data['pdo_detail'], true) as $key => $value) {
            $value['pdo_id'] = $id;
            (new PurchaseOrderDeliveryDetail())->insertPoDeliveryDetail($value);
        }
        return PurchaseOrder::find($data["po_id"])->status;
    }

    function updatePoDelivery(Request $req)
    {
        $data = $req->all();
        $id = [];
        (new PurchaseOrderDelivery())->updatePoDelivery($data);
        foreach (json_decode($data['pdo_detail'], true) as $key => $value) {
            $value['pdo_id'] = $data["pdo_id"];
            $value['statusPO'] = $value["status"];
            if (!isset($value["pdod_id"])) $t = (new PurchaseOrderDeliveryDetail())->insertPoDeliveryDetail($value);
            else {

                $t = (new PurchaseOrderDeliveryDetail())->updatePoDeliveryDetail($value);
            }
            array_push($id, $t);
        }
        PurchaseOrderDeliveryDetail::where('pdo_id', '=', $data["pdo_id"])->whereNotIn("pdod_id", $id)->update(["status" => 0]);
    }

    function deletePoDelivery(Request $req)
    {
        $data = $req->all();
        return (new PurchaseOrderDelivery())->deletePoDelivery($data);
    }
    function accPoDelivery(Request $req)
    {
        $data = $req->all();
         $id = [];
         $bermasalah = [];
        foreach (json_decode($data['pdo_detail'], true) as $key => $value) {
            $p = PurchaseOrderDelivery::where('po_id','=',$data["po_id"])->where('status','=',2)->get();
            $total =  PurchaseOrderDeliveryDetail::whereIn('pdo_id', $p->pluck('pdo_id'))->where('supplies_variant_id','=',$value['supplies_variant_id'])->sum('pdod_qty');
            // Dulu dibandingkan ke $value["pdod_qty"] sendiri (selalu true begitu $total>0) —
            // sekarang dibandingkan ke jumlah pesanan asli di purchase_orders_details.pod_qty.
            $ordered = PurchaseOrderDetail::where('po_id', '=', $data['po_id'])
                ->where('supplies_variant_id', '=', $value['supplies_variant_id'])
                ->where('status', '=', 1)
                ->value('pod_qty') ?? 0;
            if($total+$value['pdod_qty']>$ordered ){
                array_push($bermasalah, $value['name']);
            }
        }
        if(count($bermasalah)>0){
            return [
                "status"=>-1,
                "message"=>"Jumlah penerimaan melebihi jumlah pemesanan untuk barang : ".implode(", ",$bermasalah)
            ];
        }

        (new PurchaseOrderDelivery())->updatePoDelivery($data);
        (new PurchaseOrderDelivery())->statusPoDelivery($data);
        $status = PurchaseOrderDelivery::find($data["pdo_id"])->status; // approved
        foreach (json_decode($data['pdo_detail'], true) as $key => $value) {
            $value['pdo_id'] = $data["pdo_id"];
            $value['statusPO'] = $status;
            if (!isset($value["pdod_id"])) $t = (new PurchaseOrderDeliveryDetail())->insertPoDeliveryDetail($value);
            else {
                $t = (new PurchaseOrderDeliveryDetail())->updatePoDeliveryDetail($value);
            }
            array_push($id, $t);
        }
        PurchaseOrderDeliveryDetail::where('pdo_id', '=', $data["pdo_id"])->whereNotIn("pdod_id", $id)->update(["status" => 0]);
        return 1;
    }

    function declinePoDelivery(Request $req)
    {
        $data = $req->all();
        $id = [];
        (new PurchaseOrderDelivery())->statusPoDelivery($data);
         foreach (json_decode($data['pdo_detail'], true) as $key => $value) {
            $value['pdo_id'] = $data["pdo_id"];
            if (!isset($value["pdod_id"])) $t = (new PurchaseOrderDeliveryDetail())->insertPoDeliveryDetail($value);
            else {
                $t = (new PurchaseOrderDeliveryDetail())->updatePoDeliveryDetail($value);
            }
            array_push($id, $t);
        }
        PurchaseOrderDeliveryDetail::where('pdo_id', '=', $data["pdo_id"])->whereNotIn("pdod_id", $id)->update(["status" => 0]);
    }

    function getPoInvoice(Request $req)
    {
        $data = (new PurchaseOrderDetailInvoice())->getPoInvoice($req->all());
        return response()->json($data);
    }

    function insertInvoicePO(Request $req)
    {
        $data = $req->all();
        (new PurchaseOrderDetailInvoice())->insertInvoicePO($data);
        return PurchaseOrder::find($data["po_id"])->status;
    }

    function updateInvoicePO(Request $req)
    {
        $data = $req->all();
        if (isset($req->image) && $req->image != "undefined") $data["supplier_image"] = (new HelperController)->insertFile($req->image, "supplier");
        return (new PurchaseOrderDetailInvoice())->updateInvoicePO($data);
    }

    function deleteInvoicePO(Request $req)
    {
        $data = $req->all();
        return (new PurchaseOrderDetailInvoice())->deleteInvoicePO($data);
    }


    function getPoReceipt(Request $req)
    {
        $data = (new PurchaseOrderReceipt())->getPoReceipt();
        return response()->json($data);
    }

    // Supplier
    public function Supplier()
    {
        return view('Backoffice.Suppliers.Supplier');
    }

    public function SupplierDetail($id)
    {
        // $param["data"] =(new Supplier())->getSupplier(["supplier_id"=>$id])[0];
        $param["supplier_id"] = $id;
        return view('Backoffice.Suppliers.Supplier_Detail')->with($param);
    }

    function viewInsertSupplier()
    {
        $param["mode"] = 1; // 1 = insert, 2 = update
        $param["data"] = [];
        return view('Backoffice.Suppliers.insertSupplier')->with($param);
    }

    function ViewUpdateSupplier($id)
    {
        $param["mode"] = 2; // 1 = insert, 2 = update
        $param["data"] = (new Supplier())->getSupplier(["supplier_id" => $id])[0];
        return view('Backoffice.Suppliers.insertSupplier')->with($param);
    }

    function getSupplier(Request $req)
    {
        $data = (new Supplier())->getSupplier([
            "supplier_id" => $req->supplier_id
        ]);
        return response()->json($data);
    }

    function insertSupplier(Request $req)
    {
        $data = $req->all();
        if (isset($req->image) && $req->image != "undefined") $data["supplier_image"] = (new HelperController)->insertFile($req->image, "supplier");
        return (new Supplier())->insertSupplier($data);
    }

    function updateSupplier(Request $req)
    {
        $data = $req->all();
        if (isset($req->image) && $req->image != "undefined") $data["supplier_image"] = (new HelperController)->insertFile($req->image, "supplier");
        return (new Supplier())->updateSupplier($data);
    }

    function deleteSupplier(Request $req)
    {
        $data = $req->all();
        return (new Supplier())->deleteSupplier($data);
    }

    function declineInvoicePO(Request $req)
    {
        $data = $req->all();
        return (new PurchaseOrderDetailInvoice())->changeStatusInvoicePO($data);
    }
    function pelunasanPurchaseOrder(Request $req)
    {
        $data = $req->all();
        return (new PurchaseOrder())->pelunasanPurchaseOrder($data);
    }
    function acceptInvoicePO(Request $req)
    {
        $data = $req->all();
        $po = PurchaseOrderDetailInvoice::find($data["poi_id"]);
        $total = PurchaseOrderDetailInvoice::where("po_id","=",$po->po_id)->where("status","=",2)->sum("poi_total");
        $p = PurchaseOrder::find($po->po_id);
        if($total+$po->poi_total<=$p->po_total){
            return (new PurchaseOrderDetailInvoice())->changeStatusInvoicePO($data);
        }
        else{
            return -1;
        }
    }


    function generateTandaTerimaInvoice(Request $req) {
        $notValid = [];
        $notValidBank = [];
        $valid = [];
        // Ditambahkan (2026-08-06, GitHub issue #18): dulu grouping hanya memvalidasi bank_id yang
        // sama, tidak pernah supplier_id — dua supplier berbeda yang kebetulan pakai bank yang sama
        // bisa lolos digabung ke satu batch Tanda Terima yang sama, dan supplier_id yang tersimpan
        // di batch itu hanya mengikuti supplier dari invoice TERAKHIR di loop (bukan representasi
        // yang benar untuk seluruh batch). PM mengonfirmasi (2026-08-06): grouping harus berdasarkan
        // KEDUANYA, bank_id DAN supplier_id.
        $notValidSupplier = [];
        $bank_id = 0;
        $supplier_id = 0;
        $param["supplier"] ="";
        foreach ($req->poi_id as $key => $value) {
            $p = PurchaseOrderDetailInvoice::find($value);
            $po = PurchaseOrder::find($p->po_id);
            $s = Supplier::find($po->po_supplier);
            $param["supplier"] = $s;
            if ($key == 0) {
                $bank_id = $s->bank_id;
                $supplier_id = $s->supplier_id;
            } else {
                if ($bank_id != $s->bank_id){
                    array_push($notValidBank, $p->poi_code);
                }
                if ($supplier_id != $s->supplier_id){
                    array_push($notValidSupplier, $p->poi_code);
                }
            }
            if($po->pembayaran!=1||$po->tt_id !=null){
                array_push($notValid, $p->poi_code);
            }
            else{
                array_push($valid, $p);
            }
        }
        if(count($notValid)>0){
            return [
                "status"=>-1,
                "message"=>"Data berikut sudah terdaftar atau tanda terima belum diterima : ".implode(", ",$notValid)
            ];
        }
        if(count($notValidBank)>0){
            return [
                "status"=>-1,
                "message"=>"Data berikut memiliki bank yang berbeda : ".implode(", ",$notValidBank)
            ];
        }
        if(count($notValidSupplier)>0){
            return [
                "status"=>-1,
                "message"=>"Data berikut memiliki supplier yang berbeda : ".implode(", ",$notValidSupplier)
            ];
        }

        $param["data"] = $valid;
        if(count($param["data"])<=0){
            return -1;
        }

        $b = Bank::find($param["supplier"]->bank_id);
        $ttid = (new PurchaseOrder())->generateTandaTerimaID($b->bank_id);

        date_default_timezone_set('Asia/Jakarta');
        $tt = (new purchase_order_tt())->insertTt([
            "tt_date"=> date('Y-m-d'),
            "staff_name"=> Session::get('user')->staff_name,
            "tt_kode"=> $ttid,
            "supplier_id"=> $param["supplier"]->supplier_id,
            "tt_total"=> 0,
        ]);

        $total = 0;
        $dueDates = [];

        foreach ($param["data"] as $value) {
            $pi = PurchaseOrderDetailInvoice::find($value->poi_id);

            if ($pi && $pi->poi_due) {
                // pakai tanggal saja
                $dueDates[] = strtotime(date('Y-m-d', strtotime($pi->poi_due)));
            }

            $p = PurchaseOrder::find($pi->po_id);
            $p->tt_id = $tt;
            $p->pembayaran = 3;
            $p->save();

            $total += $p->po_total;
        }

        // rata-rata due date (tanggal saja)
        if ($dueDates) {
            $avg = floor(array_sum($dueDates) / count($dueDates));
            $param["due_date"] = date('Y-m-d', $avg);
        } else {
            $param["due_date"] = null;
        }
        $tt = purchase_order_tt::find($tt);
        $tt->tt_total = $total;
        $tt->tt_due = $param["due_date"];
        $tt->save();
        $param["tt"] = $tt;

        $param = $this->mergePdfPrintMeta($param);
        $pdf = Pdf::loadView('Backoffice.PDF.TandaTerima', $param);
        //return $pdf->download('Tanda Terima'.$param["supplier"]["supplier_name"].'.pdf');
        return [
            "status"=>1,
            "tt_id"=>$tt->tt_id
        ];
    }

    function viewTandaTerima($id) {
        $param["tt"] = (new purchase_order_tt())->getTt(["tt_id"=>$id])[0]??null;
        $param["data"] = PurchaseOrder::where('tt_id','=',$id)->get();
        $param["supplier"] = Supplier::find($param["tt"]["supplier_id"]); 

        foreach ($param['data'] as $key => $value) {
            $value->poi_code = PurchaseOrderDetailInvoice::where('po_id', $value->po_id)->first()->poi_code;
        }
        $param = $this->mergePdfPrintMeta($param);
        $pdf = Pdf::loadView('Backoffice.PDF.TandaTerima', $param);
        //$pdf->stream();
        $supplierName = preg_replace('/[\/\\\\]/', '', $param["supplier"]->supplier_name);
        $supplierName = str_replace(' ', '-', $supplierName);

        return $pdf->download('Tanda-Terima-' . $supplierName . '.pdf');
        // return $pdf->download(
        //     'Tanda-Terima-' . str_replace(' ', '-', $param["supplier"]["supplier_name"]) . '.pdf'
        // );
    }

    public function tt()
    {
        return view('Backoffice.Suppliers.Tt');
    }

    function getTt(Request $req){
        $data = (new purchase_order_tt())->getTt($req->all());
        return response()->json($data);
    }

    function insertTt(Request $req){
        $data = $req->all();
        return (new purchase_order_tt())->insertTt($data);
    }

    function updateTt(Request $req){
        $data = $req->all();
        return (new purchase_order_tt())->updateTt($data);
    }

    function accTt(Request $req){
        $data = $req->all();
        $p = purchase_order_tt::find($req->tt_id);
        if ($p->status != 1) {
            $staff = Staff::find($p->acc_by)->staff_name;
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }
        if (isset($req->image) && $req->image != "undefined") $data["tt_image"] = (new HelperController)->insertFile($req->image, "supplier");
        $p->status=2;
        $p->tt_image = $data["tt_image"];
        $p->tt_desc = $data['tt_desc'];
        $p->staffFinance_name = Session::get('user')->staff_name;
        $p->acc_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $p->save();
        PurchaseOrder::where('tt_id','=',$req->tt_id)->update(["pembayaran"=>2]);
    }

    function declineTt(Request $req){
        $p = purchase_order_tt::find($req->tt_id);
        if ($p->status != 1) {
            $staff = Staff::find($p->acc_by)->staff_name;
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }
        $p->staffFinance_name = Session::get('user')->staff_name;
        $p->acc_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $p->status=0;
        $p->save();

        PurchaseOrder::where('tt_id','=',$req->tt_id)->update(["tt_id"=>null,"pembayaran"=>1]);

    }

    function accPO(Request $req) {
        $data = $req->data;
        $po = PurchaseOrder::find($data['po_id']);
        if ($po->status != 1) {
            $staff = Staff::find($po->acc_by)->staff_name;
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }
        // Ditambahkan (2026-08-24): dulu penerimaan barang ini TIDAK transaksional sama sekali,
        // padahal insertPoDeliveryDetail() di dalam loop menambah ss_stock per item. Gagal di tengah
        // (item ke-3 dari 5 error) meninggalkan stok bahan bertambah sebagian, PurchaseOrderDelivery
        // header sudah terlanjur dibuat, invoice belum, dan po->status masih 1 -- user meng-ACC ulang
        // lalu item yang tadi sudah masuk ditambahkan LAGI. Guard status di atas sengaja tetap di
        // luar transaksi (murni baca, sama seperti accProduction()/accStockOpname()).
        DB::beginTransaction();
        try {
        $pod_id = (new PurchaseOrderDelivery())->insertPoDelivery(["po_id"=>$data["po_id"],"pdo_receiver"=>"Auto Generated","status"=>2]);

        foreach ($data['items'] as $key => $value) {
            $value['pdo_id'] = $pod_id;
            $value["pdod_sku"] = $value["pod_sku"];
            $value["pdod_qty"] = $value["pod_qty"];
            $value["statusPO"] = 2;
            $value["status"] = 2;
            (new PurchaseOrderDeliveryDetail())->insertPoDeliveryDetail($value);

            // Catat Log
            $sv = SuppliesVariant::find($value['supplies_variant_id']);
            $sup = Supplier::find($sv->supplier_id);
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $po->po_number,
                'log_type'    => 2,
                'log_category' => 1,
                'log_item_id' => $sv->supplies_id,
                'log_notes'  => "Pembelian bahan mentah " . $sup->supplier_name . " " . LogStock::actorSuffix(),
                'log_jumlah' => $value["pdod_qty"],
                'unit_id'    => $value['unit_id'],
            ]);
        }
        $s = Supplier::find($data["po_supplier"]);
        $due  = date('Y-m-d', strtotime('+'.$s->supplier_top.' days'));
        (new PurchaseOrderDetailInvoice())->insertInvoicePO(["po_id"=>$data["po_id"],"poi_total"=>$po->po_total,"status"=>1,"poi_due"=>$due,"bank_id"=>$s->bank_id]);
        $po->status = 2; // Lunas
        $po->acc_by = session()->get('user') ? session()->get('user')->staff_id : null;
        $po->save();
        DB::commit();
        return $due;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Apakah TOTAL stok satu bahan (dijumlahkan lintas seluruh ladder satuan, dikonversi ke
     * satuan-terkecil-setara) cukup untuk memenuhi $qty pada $unitId?
     *
     * Dipakai tolakPO() sebagai pengganti "cek stok di satuan yang dipesan saja" — sejak
     * penerimaan PO menaikkan satuan (UnitRollUp), stok bisa berada di satuan yang lebih besar.
     */
    private function suppliesLadderHasEnough(int $suppliesId, int $unitId, int $qty): bool
    {
        $chain = UnitRollUp::suppliesChain($suppliesId);

        // Faktor konversi tiap satuan ke satuan target ($unitId). Satuan target = 1.
        $faktor = [$unitId => 1];
        $current = $unitId;
        $hops = 0;
        while ($hops < 20) {
            $hops++;
            $rel = null;
            foreach ($chain as $link) {
                if ($link['small'] === $current) { $rel = $link; break; }
            }
            if ($rel === null || $rel['ratio'] <= 0) break;
            $faktor[$rel['big']] = $faktor[$current] * $rel['ratio'];
            $current = $rel['big'];
        }

        $total = 0;
        $rows = SuppliesStock::where('supplies_id', $suppliesId)->where('status', 1)->get();
        foreach ($rows as $row) {
            $u = (int) $row->unit_id;
            if (isset($faktor[$u])) {
                $total += ((int) $row->ss_stock) * $faktor[$u];
            }
        }

        return $total >= $qty;
    }

    /**
     * Pecah satuan yang lebih besar turun ke $unitId sampai baris stok $unitId cukup untuk $qty.
     * Menyimpan langsung ke DB dan mencatat log konversinya (pola sama dengan bongkar di
     * ProductIssuesDetail::stockCheck(): satuan atas keluar/cat 2, satuan bawah masuk/cat 1).
     *
     * Unit atas dicari lewat RELASI (su_id_1), bukan posisi array — pelajaran dari
     * ReturnSuppliesBongkarFailsOnStockRowInsertionOrderTest.
     */
    private function bongkarSuppliesUntilEnough(int $suppliesId, int $unitId, int $qty, $sv, &$p): void
    {
        $chain = UnitRollUp::suppliesChain($suppliesId);
        $safety = 0;

        while ($safety < 500) {
            $safety++;

            $target = SuppliesStock::where('supplies_id', $suppliesId)
                ->where('unit_id', $unitId)->where('status', 1)->first();
            if (! $target || $target->ss_stock >= $qty) {
                return; // sudah cukup (atau tidak ada baris sama sekali — biarkan guard di atas yang bicara)
            }

            // Cari satuan tepat di atas $unitId lewat relasi.
            $rel = null;
            foreach ($chain as $link) {
                if ($link['small'] === $unitId) { $rel = $link; break; }
            }
            if ($rel === null || $rel['ratio'] <= 0) return;

            $atas = SuppliesStock::where('supplies_id', $suppliesId)
                ->where('unit_id', $rel['big'])->where('status', 1)->first();

            // Satuan atas kosong → coba isi dulu dari satuan di atasnya lagi (rekursif satu tingkat).
            if (! $atas || $atas->ss_stock <= 0) {
                if ($atas === null) return;
                $this->bongkarSuppliesUntilEnough($suppliesId, (int) $rel['big'], 1, $sv, $p);
                $atas->refresh();
                if ($atas->ss_stock <= 0) return;
            }

            $atas->ss_stock -= 1;
            $atas->save();
            $target->ss_stock += $rel['ratio'];
            $target->save();

            (new LogStock())->insertLog([
                'log_date' => now(), 'log_kode' => '-', 'log_type' => 2, 'log_category' => 2,
                'log_item_id' => $suppliesId, 'log_notes' => 'Konversi unit (Bongkar) pembatalan PO',
                'log_jumlah' => 1, 'unit_id' => (int) $rel['big'],
            ]);
            (new LogStock())->insertLog([
                'log_date' => now(), 'log_kode' => '-', 'log_type' => 2, 'log_category' => 1,
                'log_item_id' => $suppliesId, 'log_notes' => 'Konversi unit (Hasil) pembatalan PO',
                'log_jumlah' => (int) $rel['ratio'], 'unit_id' => $unitId,
            ]);
        }
    }

    function tolakPO(Request $req) {
        $data = $req->all();
        $p = PurchaseOrder::find($data["po_id"]);

        if ($p->status == -1) {
            $staff = Staff::find($p->acc_by)->staff_name;
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }

        if ((int) $p->pembayaran === 2) {
            return response()->json([
                "status" => -1,
                "message" => "PO sudah terbayar dan tidak dapat ditolak",
            ]);
        }

        if ((int) $p->pembayaran === 3) {
            return response()->json([
                "status" => -1,
                "message" => "PO sedang menunggu tanda terima dan tidak dapat ditolak",
            ]);
        }

        DB::beginTransaction();
        try {
            //liat sebelumnya status apa
            if ($p->status == 2) {
                $details = PurchaseOrderDetail::where('po_id', '=', $data["po_id"])->get();
                $kurang = [];

                // Diperbaiki (2026-08-25): dulu ini cuma melihat stok di satuan yang DIPESAN dan
                // menolak pembatalan kalau satuan itu saja tidak cukup. Sejak penerimaan barang PO
                // menaikkan satuan berjenjang (lihat PurchaseOrderDeliveryDetail::insertPoDeliveryDetail()),
                // stok hasil penerimaan bisa saja sudah tidak berada di satuan yang dipesan lagi --
                // 24 Piece yang diterima jadi 2 DOS. Tanpa perubahan ini, membatalkan PO yang sudah
                // di-ACC akan SELALU gagal "Stok bahan tidak mencukupi" untuk bahan yang punya ladder.
                // Sekarang yang dibandingkan adalah TOTAL stok dalam satuan terkecil-setara.
                foreach ($details as $value) {
                    $sv = SuppliesVariant::find($value->supplies_variant_id);
                    if (!$sv) {
                        $name = trim(($value->pod_nama ?? '') . ' ' . ($value->pod_variant ?? ''));
                        $kurang[] = $name !== '' ? $name : 'Bahan';
                        continue;
                    }

                    if (!$this->suppliesLadderHasEnough((int) $sv->supplies_id, (int) $value->unit_id, (int) $value->pod_qty)) {
                        $name = trim(($value->pod_nama ?? '') . ' ' . ($value->pod_variant ?? ''));
                        if ($name === '' && $sv) {
                            $name = $sv->supplies_variant_name ?? 'Bahan';
                        }
                        $kurang[] = $name !== '' ? $name : 'Bahan';
                    }
                }

                if (count($kurang) > 0) {
                    DB::rollBack();
                    return response()->json([
                        "status" => -1,
                        "message" => "Stok bahan tidak mencukupi: " . implode(", ", array_unique($kurang)),
                    ]);
                }

                foreach ($details as $value) {
                    $sv = SuppliesVariant::find($value->supplies_variant_id);

                    // Bongkar satuan besar dulu kalau satuan yang dipesan tidak cukup sendirian
                    // (lihat komentar di blok validasi di atas). Kalau sudah cukup, ini no-op.
                    $this->bongkarSuppliesUntilEnough((int) $sv->supplies_id, (int) $value->unit_id, (int) $value->pod_qty, $sv, $p);

                    $s = SuppliesStock::where("supplies_id", "=", $sv->supplies_id)
                        ->where("unit_id", "=", $value->unit_id)
                        ->where("status", "=", 1)
                        ->first();

                    $s->ss_stock -= $value->pod_qty;
                    $s->save();

                    $sup = Supplier::find($sv->supplier_id);
                    (new LogStock())->insertLog([
                        'log_date' => now(),
                        'log_kode'    => $p->po_number,
                        'log_type'    => 2,
                        'log_category' => 2,
                        'log_item_id' => $sv->supplies_id,
                        'log_notes'  => "Pembatalan pembelian bahan mentah " . $sup->supplier_name . " " . LogStock::actorSuffix(),
                        'log_jumlah' => $value->pod_qty,
                        'unit_id'    => $value->unit_id,
                    ]);
                }
            }

            $p->status = -1; // Tolak
            $p->acc_by = session()->get('user') ? session()->get('user')->staff_id : null;
            $p->save();

            purchase_order_tt::where('tt_id', '=', $p->tt_id)->update(["status" => 0]);
            PurchaseOrderDelivery::where('po_id', '=', $data["po_id"])->update(["status" => 0]);
            PurchaseOrderDetailInvoice::where('po_id', '=', $data["po_id"])->update(["status" => 0]);

            DB::commit();
            return 1;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    function getReturnSupplies(Request $req){
        $data = (new ReturnSupplies())->getReturnSupplies($req->all());
        return response()->json($data);
    }

    function insertReturnSupplies(Request $req){
        $data = $req->all();
        $returs = json_decode($data["returs"], true);
        $total = 0;
        foreach ($returs as $key => $value) {
            $total += ($value['rsd_price'] * $value['rsd_qty']);
        }
        $po = PurchaseOrder::find($data['po_id']);
        if ($po->po_total-$total < 0){
            return [
                "status"=>-1,
                "message"=>"Jumlah retur melebihi total pembelian"
            ];
        }

        $bermasalah = [];
        $kurang = [];
        foreach ($returs as $key => $value) {
            $value['tipe_return'] = 1;
            $value['pid_qty'] = $value['rsd_qty'];
            $cek = (new ProductIssuesDetail())->stockCheck($value);
            if ($cek == -1){
                array_push($bermasalah, $value['supplies_variant_name']);
            }

            $ss = SuppliesStock::where('supplies_id', $value["supplies_id"])
                ->where('unit_id', $value['unit_id'])
                ->where('status', 1)
                ->first();

            // Ditambahkan (2026-08-24): dulu langsung $ss->ss_stock tanpa null-check -- baris stok
            // yang tidak ada (kombinasi supplies_id + unit_id yang tidak match) meledak jadi
            // "Attempt to read property on null" di tengah pre-check. Diperlakukan sama dengan stok
            // tidak mencukupi, karena memang tidak ada stok yang bisa diretur.
            if (!$ss || ($ss->ss_stock - $value['rsd_qty'] < 0)){
                $svr = SuppliesVariant::find($value['supplies_variant_id']);
                array_push($kurang, $svr->supplies_variant_name ?? ('id '.$value['supplies_variant_id']));
            }
        }
        if (count($bermasalah) > 0) {
            return [
                "status"=>-1,
                "message"=>"Stok bahan tidak mencukupi : ".implode(", ",$bermasalah)
            ];
        }
        if (count($kurang) > 0) {
            return [
                "status"=>-1,
                "message"=>"Stok bahan tidak mencukupi: ".implode(", ",$kurang)
            ];
        }

        // Ditambahkan (2026-08-24): mulai dari sini semuanya menulis ke DB (po_total, ProductIssues,
        // ReturnSupplies, lalu pengurangan ss_stock per item di loop bawah) dan dulu TIDAK ada
        // transaksi sama sekali. Paling parah: `return -1` di dalam loop bawah (stok tidak cukup)
        // keluar begitu saja padahal po_total sudah dikurangi, ProductIssues/ReturnSupplies sudah
        // dibuat, dan item-item sebelumnya sudah dipotong stoknya -- dan itu jalur bisnis normal,
        // bukan cuma skenario crash. Semua pre-check yang murni baca tetap di luar transaksi.
        DB::beginTransaction();
        try {
        $po->po_total -= $total;
        $po->save();

        $pi = (new ProductIssues())->insertProductIssues([
            "pi_type" => 2,
            "ref_num" => 0,
            "pi_date" => now()->format('d-m-Y'),
            "pi_notes" => "Retur tambahan dari Pembelian " . $po->po_number,
            "tipe_return" => 1,
            "po_id" => $po->po_id,
            "status" => 2,
        ]);

        $data['pi_id'] = $pi->pi_id;
        $rs_id = (new ReturnSupplies())->insertReturnSupplies($data);

        $data_retur = ReturnSupplies::where('po_id', $data['po_id'])->where('status', 1)->get();
        $total_retur = 0;
        foreach ($data_retur as $key => $value) {
            $total_retur += $value->rs_total;
        }

        foreach ($returs as $key => $value) {
            $value['pi_id'] = $pi->pi_id;
            $value['tipe_return'] = 1;
            $value['ref_num'] = 0;
            $value['pid_qty'] = $value['rsd_qty'];
            $value['retur_pembelian'] = 1;
            $value['total_retur'] = $total_retur;
            $value['po_id'] = $po->po_id;
            $pid_id = (new ProductIssuesDetail())->insertProductIssuesDetail($value);

            $s = SuppliesStock::where('supplies_id','=',$value['supplies_id'])->where('unit_id','=',$value["unit_id"])->where('status', 1)->first();

            // pengurangan qty stok
            // Ditambahkan (2026-08-24): null-check eksplisit untuk $s -- dulu `$s->ss_stock ?? 0`
            // aman saat MEMBACA, tapi `$s->ss_stock = ...` di bawah tetap meledak kalau $s null
            // (mis. rsd_qty = 0 sehingga cek di bawah lolos). Sekarang rollback + pesan jelas.
            $stocks = $s->ss_stock ?? 0;
            if (!$s || $stocks - $value["rsd_qty"] < 0) {
                DB::rollBack();
                return -1;
            }
            $stocks -= $value["rsd_qty"];

            $s->ss_stock = $stocks;
            $s->save();

            // Catat Log
            $sup = SuppliesVariant::find($value['supplies_variant_id']);

            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $pi->pi_code,
                'log_type'    => 2,
                'log_category' => 2,
                'log_item_id' => $sup->supplies_id,
                'log_notes'  => 'Retur pembelian dari pembelian ' . $po->po_number . ' ' . LogStock::actorSuffix(),
                'log_jumlah' => $value['pid_qty'],
                'unit_id'    => $value['unit_id'],
            ]);

            $value['rs_id'] = $rs_id;
            $value['pid_id'] = $pid_id;
            (new ReturnSuppliesDetail())->insertReturnSuppliesDetail($value);
        }


        DB::commit();
        return 1;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    function updateReturnSupplies(Request $req){
        $data = $req->all();
        return (new ReturnSupplies())->updateReturnSupplies($data);
    }

    function deleteReturnSupplies(Request $req){
        $data = $req->all();
        $rs = ReturnSupplies::find($data['rs_id']);
        $returs = ReturnSuppliesDetail::where('rs_id', $data['rs_id'])->where('status', 1)->get();
        $pi = ProductIssues::find($rs->pi_id);

        // Balikin ke awal sebelum ada retur ini
        $total = 0;
        foreach ($returs as $key => $value) {
            $total += ($value['rsd_price'] * $value['rsd_qty']);
        }

        // Ditambahkan (2026-08-24): kebalikan dari insertReturnSupplies() di atas -- method ini
        // MENGEMBALIKAN stok bahan (lewat deleteProductIssuesDetail() di loop bawah) sekaligus
        // mengembalikan po_total. Dulu tanpa transaksi: gagal di tengah meninggalkan po_total sudah
        // bertambah tapi stok baru sebagian yang dikembalikan, atau sebaliknya.
        DB::beginTransaction();
        try {
        $po = PurchaseOrder::find($data['po_id']);
        $po->po_total += $total;
        $po->save();

        // Return value intentionally not checked here — see ProductIssues::deleteProductIssues()'s
        // dead-code comment. Currently harmless (its ref_num guard never actually triggers today),
        // but would need to change if that guard is ever revived.
        (new ProductIssues())->deleteProductIssues($rs);
        (new ReturnSupplies())->deleteReturnSupplies($data);

        $data_retur = ReturnSupplies::where('po_id', $data['po_id'])->where('status', 1)->get();
        $total_retur = 0;
        foreach ($data_retur as $key => $value) {
            $total_retur += $value->rs_total;
        }

        foreach ($returs as $key => $value) {
            $value['retur_pembelian'] = 1;
            $value['total_retur'] = $total_retur;
            $value['po_id'] = $data['po_id'];
            (new ProductIssuesDetail())->deleteProductIssuesDetail($value);

            // Catat Log
            $sup = SuppliesVariant::find($value['supplies_variant_id']);

            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $pi->pi_code,
                'log_type'    => 2,
                'log_category' => 1,
                'log_item_id' => $sup->supplies_id,
                'log_notes'  => 'Pembatalan retur pembelian dari pembelian ' . $po->po_number . ' ' . LogStock::actorSuffix(),
                'log_jumlah' => $value['rsd_qty'],
                'unit_id'    => $value['unit_id'],
            ]);
        }
        foreach ($returs as $key => $value) {
            (new ReturnSuppliesDetail())->deleteReturnSuppliesDetail($value);
        }

        DB::commit();
        return 1;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
