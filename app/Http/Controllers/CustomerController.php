<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderDelivery;
use App\Models\SalesOrderDetailInvoice;
use App\Models\Customer;
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

    function getCustomer(Request $req)
    {
        $data =  (new Customer())->getCustomer([
            "cus_name"=>$req->cus_name,
            "city_id"=>$req->city_id
        ]);
        return json_encode($data);
    }

    function insertCustomer(Request $req)
    {
        $data = $req->all();
        if(isset($req->main)&&$req->main!="undefined")$data["cus_img"] = (new HelperController)->insertFile($req->main, "customer");
        return (new Customer())->insertCustomer($data);
    }

    function updateCustomer(Request $req)
    {
        $data = $req->all();
        if(isset($req->main)&&$req->main!="undefined")$data["cus_img"] = (new HelperController)->insertFile($req->main, "customer");
        (new Customer())->updateCustomer($data);
    }

    function deleteCustomer(Request $req)
    {
        $data = $req->all();
        return (new Customer())->deleteCustomer($data);
    }
}
