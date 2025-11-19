<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDelivery;
use App\Models\PurchaseOrderDeliveryDetail;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\PurchaseOrderReceipt;
use App\Models\Supplier;
use App\Models\Supplies;
use App\Models\SuppliesVariant;
use Illuminate\Http\Request;

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
        $data = (new PurchaseOrder())->getPurchaseOrder();
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
        $data = (new PurchaseOrderDelivery())->getPoDelivery();
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
    }

    function updatePoDelivery(Request $req)
    {
        $data = $req->all();
        $id = [];
        (new PurchaseOrderDelivery())->updatePoDelivery($data);
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

    function deletePoDelivery(Request $req)
    {
        $data = $req->all();
        return (new PurchaseOrderDelivery())->deletePoDelivery($data);
    }

    function getPoInvoice(Request $req)
    {
        $data = (new PurchaseOrderDetailInvoice())->getPoInvoice();
        return response()->json($data);
    }

    function insertInvoicePO(Request $req)
    {
        $data = $req->all();
        return (new PurchaseOrderDetailInvoice())->insertInvoicePO($data);
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
}
