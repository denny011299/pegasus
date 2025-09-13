<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderDelivery;
use App\Models\SalesOrderDetailInvoice;
use App\Models\Customer;
use App\Models\SalesOrderDetail;
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

    function insertSalesOrder(Request $req){
        $data = $req->all();
        $id = (new SalesOrder())->insertSalesOrder($data);
        foreach (json_decode($data['products'],true) as $key => $value) {
            $value['so_id'] = $id;
            (new SalesOrderDetail())->insertSalesOrderDetail($value);
        }
    }

    function updateSalesOrder(Request $req){
        $data = $req->all();
        $list_id_detail = [];
        $so_id = (new SalesOrder())->updateSalesOrder($data);
        foreach (json_decode($req->products,true) as $key => $value) {
            $value['so_id'] = $so_id;
            $id = (new SalesOrderDetail())->updateSalesOrderDetail($value);
            array_push($list_id_detail, $id);
        }
        SalesOrderDetail::whereNotIn('sod_id', $list_id_detail)->where('so_id','=',$so_id)->update(['status' => 0]);
    }

    function deleteSalesOrder(Request $req){
        $data = $req->all();
        return (new SalesOrder())->deleteSalesOrder($data);
    }

    function getSoDelivery(Request $req){
        $data = (new SalesOrderDelivery())->getSoDelivery();
        return response()->json($data);
    }

    function getSoInvoice(Request $req){
        $data = (new SalesOrderDetailInvoice())->getSoInvoice();
        return response()->json($data);
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
        if(isset($req->image)&&$req->image!="undefined")$data["customer_image"] = (new HelperController)->insertFile($req->image, "customer");
        return (new Customer())->insertCustomer($data);
    }

    function updateCustomer(Request $req)
    {
        $data = $req->all();
        if(isset($req->image)&&$req->image!="undefined")$data["customer_image"] = (new HelperController)->insertFile($req->image, "customer");
        (new Customer())->updateCustomer($data);
    }

    function deleteCustomer(Request $req)
    {
        $data = $req->all();
        return (new Customer())->deleteCustomer($data);
    }
}
