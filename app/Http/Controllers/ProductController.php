<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\ProductUnits;
use App\Models\ProductVariants;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Product Category
    public function Category(){
        return view('Backoffice.Product.Category');
    }

    function getCategory(Request $req){
        $data = (new ProductCategory())->getCategory();
        return response()->json($data);
    }

    function insertCategory(Request $req){
        $data = $req->all();
        return (new ProductCategory())->insertCategory($data);
    }

    function updateCategory(Request $req){
        $data = $req->all();
        return (new ProductCategory())->updateCategory($data);
    }

    function deleteCategory(Request $req){
        $data = $req->all();
        return (new ProductCategory())->deleteCategory($data);
    }

    // Product Units
    public function Unit(){
        return view('Backoffice.Product.Units');
    }

    function getUnit(Request $req){
        $data = (new ProductUnits())->getUnit();
        return response()->json($data);
    }

    function insertUnit(Request $req){
        $data = $req->all();
        return (new ProductUnits())->insertUnit($data);
    }

    function updateUnit(Request $req){
        $data = $req->all();
        return (new ProductUnits())->updateUnit($data);
    }

    function deleteUnit(Request $req){
        $data = $req->all();
        return (new ProductUnits())->deleteUnit($data);
    }

    // Product Variants
    public function Variant(){
        return view('Backoffice.Product.Variants');
    }

    function getVariant(Request $req){
        $data = (new ProductVariants())->getVariant();
        return response()->json($data);
    }

    function insertVariant(Request $req){
        $data = $req->all();
        return (new ProductVariants())->insertVariant($data);
    }

    function updateVariant(Request $req){
        $data = $req->all();
        return (new ProductVariants())->updateVariant($data);
    }

    function deleteVariant(Request $req){
        $data = $req->all();
        return (new ProductVariants())->deleteVariant($data);
    }
}
