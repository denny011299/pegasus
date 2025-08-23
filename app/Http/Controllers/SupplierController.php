<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\PurchaseOrderReceipt;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    function PurchaseOrder(){
        return view('Backoffice.Suppliers.Purchase_Order');
    }

    function PurchaseOrderDetail($id){
        return view('Backoffice.Suppliers.Purchase_Order_Detail');
    }

    function getPurchaseOrder(Request $req){
        $data = (new PurchaseOrder())->getPurchaseOrder();
        return response()->json($data);
    }

    function getPoDelivery(Request $req){
        $data = (new PurchaseOrderDelivery())->getPoDelivery();
        return response()->json($data);
    }

    function getPoInvoice(Request $req){
        $data = (new PurchaseOrderDetailInvoice())->getPoInvoice();
        return response()->json($data);
    }

    function getPoReceipt(Request $req){
        $data = (new PurchaseOrderReceipt())->getPoReceipt();
        return response()->json($data);
    }

    // Supplier
    public function Supplier(){
        return view('Backoffice.Suppliers.Supplier');
    }

    public function SupplierDetail($id){
        // $param["data"] =(new Supplier())->getSupplier(["supplier_id"=>$id])[0];
        $param["supplier_id"] =$id;
        return view('Backoffice.Suppliers.Supplier_Detail')->with($param);
    }

    function viewInsertSupplier() {
        $param["mode"] =1;
        $param["data"] =[];
        return view('Backoffice.Suppliers.insertSupplier')->with($param);
    }

    function getSupplier(Request $req){
        $data = (new Supplier())->getSupplier([
            "supplier_id" => $req->supplier_id
        ]);
        return response()->json($data);
    }

    function insertSupplier(Request $req){
        $data = $req->all();
        return (new Supplier())->insertSupplier($data);
    }

    function updateSupplier(Request $req){
        $data = $req->all();
        return (new Supplier())->updateSupplier($data);
    }

    function deleteSupplier(Request $req){
        $data = $req->all();
        return (new Supplier())->deleteSupplier($data);
    }
}
