<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\PurchaseOrderReceipt;
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
}
