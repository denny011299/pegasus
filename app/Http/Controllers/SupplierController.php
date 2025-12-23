<?php

namespace App\Http\Controllers;

use App\Models\purchase_order_tt;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderDeliveryDetail;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\PurchaseOrderReceipt;
use App\Models\Supplier;
use App\Models\Supplies;
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
        foreach (json_decode($req->po_detail, true) as $key => $value) {
            (new PurchaseOrderDetail())->updatePurchaseOrderDetail($value);
        }
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
        PurchaseOrder::where('tt_id','=',$req->tt_id)->update(["pembayaran"=>1]);
    }

    function declineTt(Request $req){
        $p = purchase_order_tt::find($req->tt_id);
        $p->staffFinance_name = Session::get('user')->staff_name;
        $p->status=0;
        $p->save();

        PurchaseOrder::where('tt_id','=',$req->tt_id)->update(["tt_id"=>null]);

    }
}

