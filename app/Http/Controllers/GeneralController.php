<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\Request;

class GeneralController extends Controller
{
    function testing() {
        $p = Product::where('status','=',1)->get();
        foreach ($p as $key => $value) {
            (new ProductStock())->syncStock($value->product_id);
        }
        
    }

    // Wilayah
    public function Area(){
        return view('Backoffice.Area.Area');
    }

    function getArea(Request $req){
        $data = (new Area())->getArea();
        return response()->json($data);
    }

    function insertArea(Request $req){
        $data = $req->all();
        return (new Area())->insertArea($data);
    }

    function updateArea(Request $req){
        $data = $req->all();
        return (new Area())->updateArea($data);
    }

    function deleteArea(Request $req){
        $data = $req->all();
        return (new Area())->deleteArea($data);
    }
}
