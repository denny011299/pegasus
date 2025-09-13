<?php

namespace App\Http\Controllers;

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
}
