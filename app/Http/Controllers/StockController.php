<?php

namespace App\Http\Controllers;

use App\Models\ManageStock;
use App\Models\ProductIssues;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockAlert;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use Illuminate\Http\Request;

class StockController extends Controller
{
    // Stock Opname
        public function StockOpname(){
        return view('Backoffice.Inventory.Stock_Opname');
    }

    function getStockOpname(Request $req){
        $data = (new StockOpname())->getStockOpname();
        return response()->json($data);
    }

    function insertStockOpname(Request $req){
        $data = $req->all();
        $id =  (new StockOpname())->insertStockOpname($data);
        foreach (json_decode($req->item,true) as $key => $value) {
            $value["sto_id"] = $id;
            (new StockOpnameDetail())->insertDetail($value);
        }
    }

    function updateStockOpname(Request $req){
        $data = $req->all();
        $id = (new StockOpname())->updateStockOpname($data);
        foreach (json_decode($req->item,true) as $key => $value) {
            $value["sto_id"] = $id;
            if(isset($value["stod_id"]))(new StockOpnameDetail())->updateDetail($value);
            else (new StockOpnameDetail())->insertDetail($value);
        }
    }

    function deleteStockOpname(Request $req){
        $data = $req->all();
        return (new StockOpname())->deleteStockOpname($data);
    }

    // Stock Opname Detail
    public function DetailStockOpname($id){
        if($id!=-1){
            $param["data"] = (new StockOpname())->getStockOpname(["sto_id"=>$id])[0];
            $param["mode"] = 2;
        }
        else {
            $param["data"] = [];
            $param["mode"] = 1;
        }
        return view('Backoffice.Inventory.CreateStockOpname')->with($param);
    }

    function getDetailStockOpname(Request $req){
        $data = (new StockOpnameDetail())->getDetailStockOpname();
        return response()->json($data);
    }

    function insertDetailStockOpname(Request $req){
        $data = $req->all();
        return (new StockOpnameDetail())->insertDetailStockOpname($data);
    }

    function updateDetailStockOpname(Request $req){
        $data = $req->all();
        return (new StockOpnameDetail())->updateDetailStockOpname($data);
    }

    function deleteDetailStockOpname(Request $req){
        $data = $req->all();
        return (new StockOpnameDetail())->deleteDetailStockOpname($data);
    }

    // Stock Opname
        public function StockOpnameBahan(){
        return view('Backoffice.Inventory.Stock_Opname_Bahan');
    }

    function getStockOpnameBahan(Request $req){
        $data = (new StockOpname())->getStockOpname();
        return response()->json($data);
    }

    function insertStockOpnameBahan(Request $req){
        $data = $req->all();
        $id =  (new StockOpname())->insertStockOpname($data);
        foreach (json_decode($req->item,true) as $key => $value) {
            $value["sto_id"] = $id;
            (new StockOpnameDetail())->insertDetail($value);
        }
    }

    function updateStockOpnameBahan(Request $req){
        $data = $req->all();
        $id = (new StockOpname())->updateStockOpname($data);
        foreach (json_decode($req->item,true) as $key => $value) {
            $value["sto_id"] = $id;
            if(isset($value["stod_id"]))(new StockOpnameDetail())->updateDetail($value);
            else (new StockOpnameDetail())->insertDetail($value);
        }
    }

    function deleteStockOpnameBahan(Request $req){
        $data = $req->all();
        return (new StockOpname())->deleteStockOpname($data);
    }

    // Stock Opname Detail
    public function DetailStockOpnameBahan($id){
        if($id!=-1){
            $param["data"] = (new StockOpname())->getStockOpname(["sto_id"=>$id])[0];
            $param["mode"] = 2;
        }
        else {
            $param["data"] = [];
            $param["mode"] = 1;
        }
        return view('Backoffice.Inventory.CreateStockOpname')->with($param);
    }

    function getDetailStockOpnameBahan(Request $req){
        $data = (new StockOpnameDetail())->getDetailStockOpname();
        return response()->json($data);
    }

    function insertDetailStockOpnameBahan(Request $req){
        $data = $req->all();
        return (new StockOpnameDetail())->insertDetailStockOpname($data);
    }

    function updateDetailStockOpnameBahan(Request $req){
        $data = $req->all();
        return (new StockOpnameDetail())->updateDetailStockOpname($data);
    }

    function deleteDetailStockOpnameBahan(Request $req){
        $data = $req->all();
        return (new StockOpnameDetail())->deleteDetailStockOpname($data);
    }

    // Stock Alert
    public function StockAlert(){
        return view('Backoffice.Inventory.Stock_Alert');
    }

    function getStockAlert(Request $req){
        $data = (new StockAlert())->getStockAlert(["mode"=>$req->mode]);
        return response()->json($data);
    }

    function insertStockAlert(Request $req){
        $data = $req->all();
        return (new StockAlert())->insertStockAlert($data);
    }

    function updateStockAlert(Request $req){
        $data = $req->all();
        return (new StockAlert())->updateStockAlert($data);
    }

    function deleteStockAlert(Request $req){
        $data = $req->all();
        return (new StockAlert())->deleteStockAlert($data);
    }


    // Product Issues
    public function ProductIssue(){
        return view('Backoffice.Inventory.Product_Issues');
    }

    function getProductIssue(Request $req){
        $data = (new ProductIssues())->getProductIssues([
            "pi_type"=>$req->pi_type,
            "tipe_return"=>$req->tipe_return,
            "date"=>$req->date,
        ]);
        return response()->json($data);
    }

    function insertProductIssue(Request $req){
        $data = $req->all();
        return (new ProductIssues())->insertProductIssues($data);
    }

    function updateProductIssue(Request $req){
        $data = $req->all();
        return (new ProductIssues())->updateProductIssues($data);
    }

    function deleteProductIssue(Request $req){
        $data = $req->all();
        return (new ProductIssues())->deleteProductIssues($data);
    }

    // Manage Stock
    public function ManageStock(){
        return view('Backoffice.Inventory.Manage_Stock');
    }

    function getManageStock(Request $req){
        $data = (new ManageStock())->getManageStocks();
        return response()->json($data);
    }
    function insertManageStocks(Request $req){
        $data = $req->all();
        return (new ManageStock())->insertManageStock($data);
    }
    // Stock
    public function Stock(){
        return view('Backoffice.Inventory.Stock_Product');
    }

    function getStock(Request $req){
        $data = (new ProductVariant())->getProductVariant();
        foreach ($data as $key => $value) {
            $value->stock = (new ProductStock())->getProductStock(["product_variant_id"=>$value->product_variant_id]);
        }
        return response()->json($data);
    }

    // Stock supplies
    public function StockSupplies(){
        return view('Backoffice.Inventory.Stock_Supplies');
    }

    function getStockSupplies(Request $req){
        $data = (new SuppliesVariant())->getSuppliesVariant();
        return response()->json($data);
    }
}
