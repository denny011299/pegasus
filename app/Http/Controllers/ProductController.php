<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnits;
use App\Models\ProductVariants;
use App\Models\Supplies;
use App\Models\Unit;
use App\Models\Variant;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Product Category
    public function Category(){
        return view('Backoffice.Product.Category');
    }

    function getCategory(Request $req){
        $data = (new Category())->getCategory();
        return response()->json($data);
    }

    function insertCategory(Request $req){
        $data = $req->all();
        return (new Category())->insertCategory($data);
    }

    function updateCategory(Request $req){
        $data = $req->all();
        return (new Category())->updateCategory($data);
    }

    function deleteCategory(Request $req){
        $data = $req->all();
        return (new Category())->deleteCategory($data);
    }

    // Product Units
    public function Unit(){
        return view('Backoffice.Product.Units');
    }

    function getUnit(Request $req){
        $data = (new Unit())->getUnit();
        return response()->json($data);
    }

    function insertUnit(Request $req){
        $data = $req->all();
        return (new Unit())->insertUnit($data);
    }

    function updateUnit(Request $req){
        $data = $req->all();
        return (new Unit())->updateUnit($data);
    }

    function deleteUnit(Request $req){
        $data = $req->all();
        return (new Unit())->deleteUnit($data);
    }

    // Product Variants
    public function Variant(){
        return view('Backoffice.Product.Variants');
    }

    function getVariant(Request $req){
        $data = (new Variant())->getVariant();
        return response()->json($data);
    }

    function insertVariant(Request $req){
        $data = $req->all();
        return (new Variant())->insertVariant($data);
    }

    function updateVariant(Request $req){
        $data = $req->all();
        return (new Variant())->updateVariant($data);
    }

    function deleteVariant(Request $req){
        $data = $req->all();
        return (new Variant())->deleteVariant($data);
    }

    // Product
    public function Product(){
        return view('Backoffice.Product.Product');
    }
    
    function getProduct(Request $req){
        $data = (new Product())->getProduct();
        return $data;
    }

    // Supplies
    public function Supplies(){
        return view('Backoffice.Product.Supplies');
    }

    function getSupplies(Request $req){
        $data = (new Supplies())->getSupplies();
        return response()->json($data);
    }

    function insertSupplies(Request $req){
        $data = $req->all();
        return (new Supplies())->insertSupplies($data);
    }

    function updateSupplies(Request $req){
        $data = $req->all();
        return (new Supplies())->updateSupplies($data);
    }

    function deleteSupplies(Request $req){
        $data = $req->all();
        return (new Supplies())->deleteSupplies($data);
    }
}
