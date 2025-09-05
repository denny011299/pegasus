<?php

namespace App\Http\Controllers;

use App\Models\ManageStock;
use App\Models\ProductIssues;
use App\Models\ProductVariant;
use App\Models\Stock;
use App\Models\StockAlert;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
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
        return (new StockOpname())->insertStockOpname($data);
    }

    function updateStockOpname(Request $req){
        $data = $req->all();
        return (new StockOpname())->updateStockOpname($data);
    }

    function deleteStockOpname(Request $req){
        $data = $req->all();
        return (new StockOpname())->deleteStockOpname($data);
    }

    // Stock Opname Detail
    public function DetailStockOpname($id){
        return view('Backoffice.Inventory.CreateStockOpname');
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
        $data = (new ProductIssues())->getProductIssues();
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
        return view('Backoffice.Inventory.Stock');
    }

    function getStock(Request $req){
        $data = (new ProductVariant())->getProductVariant();
        return response()->json($data);
    }
}
