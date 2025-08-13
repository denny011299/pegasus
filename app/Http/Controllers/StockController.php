<?php

namespace App\Http\Controllers;

use App\Models\ProductIssues;
use App\Models\StockAlert;
use App\Models\StockOpname;
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

    // Stock Alert
    public function StockAlert(){
        return view('Backoffice.Inventory.Stock_Alert');
    }

    function getStockAlert(Request $req){
        $data = (new StockAlert())->getStockAlert();
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
        $data = (new ProductIssues())->getProductIssue();
        return response()->json($data);
    }

    function insertProductIssue(Request $req){
        $data = $req->all();
        return (new ProductIssues())->insertProductIssue($data);
    }

    function updateProductIssue(Request $req){
        $data = $req->all();
        return (new ProductIssues())->updateProductIssue($data);
    }

    function deleteProductIssue(Request $req){
        $data = $req->all();
        return (new ProductIssues())->deleteProductIssue($data);
    }
}
