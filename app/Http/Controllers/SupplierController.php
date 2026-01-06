<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\LogStock;
use App\Models\purchase_order_tt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderDeliveryDetail;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\PurchaseOrderReceipt;
use App\Models\Supplier;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SupplierController extends Controller
{
    function PurchaseOrder()
    {
        return view('Backoffice.Suppliers.Purchase_Order');
    }

    function PurchaseOrderDetail($id)
    {
        $param["data"] = (new PurchaseOrder())->getPurchaseOrder(["po_id" => $id])[0];
        return view('Backoffice.Suppliers.Purchase_Order_Detail')->with($param);
    }
    function PurchaseOrderDetailHutang($id)
    {
        $param["data"] = (new PurchaseOrder())->getPurchaseOrder(["po_id" => $id, "hutang" => 1])[0];
        return view('Backoffice.Suppliers.Purchase_Order_Detail')->with($param);
    }

    function getPurchaseOrder(Request $req)
    {
        $data = (new PurchaseOrder())->getPurchaseOrder($req->all());
        return response()->json($data);
    }

    function InsertPurchaseOrder(Request $req)
    {
        $data = (new PurchaseOrder())->InsertPurchaseOrder($req->all());
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

    function updatePurchaseOrderDetail(Request $req)
    {
        $total = 0;
        foreach (json_decode($req->po_detail, true) as $key => $value) {
            $total += $value["pod_subtotal"];
            (new PurchaseOrderDetail())->updatePurchaseOrderDetail($value);
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
            if($total+$value['pdod_qty']>$value["pdod_qty"] ){
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


    function generateTandaTerima($id,$kode) {
        $param["supplier"] = Supplier::find($id); 
        $param["data"] = PurchaseOrder::where('po_supplier','=',$id)->where('status','=',4)->where('pembayaran','=',0)
        ->whereNull('tt_id')
        ->get();
        if(count($param["data"])<=0){
            return -1;
        }
        $ada = -1;
        foreach ($param["data"] as $key => $value) {
            if($value["kodeTerima"]!=null) $ada=$value["kodeTerima"];
        };
        if($ada==-1)$ttid = (new PurchaseOrder())->generateTandaTerimaID($kode);
        else $ttid = $ada;
        date_default_timezone_set('Asia/Jakarta');
        $tt = (new purchase_order_tt())->insertTt([
            "tt_date"=> date('Y-m-d'),
            "staff_name"=> Session::get('user')->staff_name,
            "tt_kode"=> $ttid,
            "supplier_id"=> $id,
            "tt_total"=> 0,
        ]);
        $total = 0;
        foreach ($param["data"] as $key => $value) {
            $p = PurchaseOrder::find($value->po_id);
            $p->tt_id = $tt;
            $p->save();
            $total += $p->po_total;
        }
        
        $tt = purchase_order_tt::find($tt);
        $tt->tt_total = $total;
        $tt->save();
        $param["tt"] = $tt;

        $pdf = Pdf::loadView('Backoffice.PDF.TandaTerima', $param);
        //return $pdf->download('Tanda Terima'.$param["supplier"]["supplier_name"].'.pdf');
        return $tt->tt_id;
    }

    function generateTandaTerimaInvoice(Request $req) {
        $notValid = [];
        $valid = [];
        $param["supplier"] ="";
        foreach ($req->poi_id as $key => $value) {
            $p = PurchaseOrderDetailInvoice::find($value);
            $po = PurchaseOrder::find($p->po_id);
            $s = Supplier::find($po->po_supplier);
            $param["supplier"] = $s;
            if($p->pembayaran!=0||$po->tt_id !=null){
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
        $pdf = Pdf::loadView('Backoffice.PDF.TandaTerima', $param);
        //$pdf->stream();
        return $pdf->download(
            'Tanda-Terima-' . str_replace(' ', '-', $param["supplier"]["supplier_name"]) . '.pdf'
        );
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
        if (isset($req->image) && $req->image != "undefined") $data["tt_image"] = (new HelperController)->insertFile($req->image, "supplier");
        $p = purchase_order_tt::find($req->tt_id);
        $p->status=2;
        $p->tt_image = $data["tt_image"];
        $p->staffFinance_name = Session::get('user')->staff_name;
        $p->save();
        PurchaseOrder::where('tt_id','=',$req->tt_id)->update(["pembayaran"=>2]);
    }

    function declineTt(Request $req){
        $p = purchase_order_tt::find($req->tt_id);
        $p->staffFinance_name = Session::get('user')->staff_name;
        $p->status=0;
        $p->save();

        PurchaseOrder::where('tt_id','=',$req->tt_id)->update(["tt_id"=>null,"pembayaran"=>1]);

    }

    function accPO(Request $req) {
        $data = $req->data;
        $pod_id = (new PurchaseOrderDelivery())->insertPoDelivery(["po_id"=>$data["po_id"],"pdo_receiver"=>"Auto Generated","status"=>2]);
        $po = PurchaseOrder::find($data['po_id']);

        foreach ($data['items'] as $key => $value) {
            $value['pdo_id'] = $pod_id;
            $value["pdod_sku"] = $value["pod_sku"];
            $value["pdod_qty"] = $value["pod_qty"];
            $value["statusPO"] = 2;
            $value["status"] = 2;
            (new PurchaseOrderDeliveryDetail())->insertPoDeliveryDetail($value);

            // Catat Log
            $sv = SuppliesVariant::find($value['supplies_variant_id']);
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $po->po_number,
                'log_item_id' => $sv->supplies_id,
                'log_notes'  => "Pembelian bahan mentah",
                'log_jumlah' => $value["pdod_qty"],
                'unit_id'    => $value['unit_id'],
            ]);
        }
        $s = Supplier::find($data["po_supplier"]);
        $due  = date('Y-m-d', strtotime('+'.$s->supplier_top.' days'));
        (new PurchaseOrderDetailInvoice())->insertInvoicePO(["po_id"=>$data["po_id"],"poi_total"=>$data["po_total"],"status"=>2,"poi_due"=>$due,"bank_id"=>$s->bank_id]);
        $po->status = 2; // Lunas
        $po->save();
    }

    function tolakPO(Request $req) {
        $data = $req->all();
        $p = PurchaseOrder::find($data["po_id"]);

        //liat sebelumnya status apa
        if($p->status==2){
            $b = PurchaseOrderDetail::where('po_id','=',$data["po_id"])->get();;
            foreach ($b as $key => $value) {
                $s = SuppliesVariant::find($value->supplies_variant_id);
                $s = SuppliesStock::where("supplies_id", "=", $s->supplies_id)
                    ->where("unit_id", "=", $value->unit_id)
                    ->where("status", "=", 1)
                    ->first();
                $s->ss_stock -= $value->pod_qty;
                $s->save();
            }
        }

        $p->status = -1; // Tolak
        $p->save(); 

        purchase_order_tt::where('tt_id','=',$p->tt_id)->update(["status"=>0]);
        PurchaseOrderDelivery::where('po_id','=',$data["po_id"])->update(["status"=>0]);
        PurchaseOrderDetailInvoice::where('po_id','=',$data["po_id"])->update(["status"=>0]);
    }
}

