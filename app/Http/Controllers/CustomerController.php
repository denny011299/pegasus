<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderDelivery;
use App\Models\SalesOrderDetailInvoice;
use App\Models\Customer;
use App\Models\LogStock;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\SalesOrderDeliveryDetail;
use App\Models\SalesOrderDetail;
use Illuminate\Http\Request;

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
        $data = (new SalesOrder())->getSalesOrder();
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
                array_push($p, $value["pr_name"]." ".$value["product_variant_name"]);
                $valid=-1;
            }
        }
        if($valid==-1){
            return implode(", ",$p);
        }

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

            if(!isset($value["sod_id"])) $id = (new SalesOrderDetail())->insertSalesOrderDetail($value);
            
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
            }
            array_push($list_id_detail, $id);

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
