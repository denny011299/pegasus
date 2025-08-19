<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderDelivery;
use App\Models\SalesOrderDetailInvoice;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function SalesOrder(){
        return view('Backoffice.Customers.Sales_Order');
    }

    public function SalesOrderDetail($id){
        return view('Backoffice.Customers.Sales_Order_Detail');
    }
    
    function getSalesOrder(Request $req){
        $data = (new SalesOrder())->getSalesOrder();
        return response()->json($data);
    }

    function getSoDelivery(Request $req){
        $data = (new SalesOrderDelivery())->getSoDelivery();
        return response()->json($data);
    }

    function getSoInvoice(Request $req){
        $data = (new SalesOrderDetailInvoice())->getSoInvoice();
        return response()->json($data);
    }
}
