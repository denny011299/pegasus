<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductUnits;
use App\Models\ProductVariant;
use App\Models\ProductVariants;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesUnit;
use App\Models\SuppliesVariant;
use App\Models\Unit;
use App\Models\Variant;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Product Category
    public function Category()
    {
        return view('Backoffice.Product.Category');
    }

    function getCategory(Request $req)
    {
        $data = (new Category())->getCategory();
        return response()->json($data);
    }

    function insertCategory(Request $req)
    {
        $data = $req->all();
        return (new Category())->insertCategory($data);
    }

    function updateCategory(Request $req)
    {
        $data = $req->all();
        return (new Category())->updateCategory($data);
    }

    function deleteCategory(Request $req)
    {
        $data = $req->all();
        return (new Category())->deleteCategory($data);
    }

    // Product Units
    public function Unit()
    {
        return view('Backoffice.Product.Units');
    }

    function getUnit(Request $req)
    {
        $data = (new Unit())->getUnit([
            "unit_name" => $req->unit_name
        ]);
        return response()->json($data);
    }

    function insertUnit(Request $req)
    {
        $data = $req->all();
        return (new Unit())->insertUnit($data);
    }

    function updateUnit(Request $req)
    {
        $data = $req->all();
        return (new Unit())->updateUnit($data);
    }

    function deleteUnit(Request $req)
    {
        $data = $req->all();
        return (new Unit())->deleteUnit($data);
    }

    // Product Variants
    public function Variant()
    {
        return view('Backoffice.Product.Variants');
    }

    function getVariant(Request $req)
    {
        $data = (new Variant())->getVariant();
        return response()->json($data);
    }

    function insertVariant(Request $req)
    {
        $data = $req->all();
        return (new Variant())->insertVariant($data);
    }

    function updateVariant(Request $req)
    {
        $data = $req->all();
        return (new Variant())->updateVariant($data);
    }

    function deleteVariant(Request $req)
    {
        $data = $req->all();
        return (new Variant())->deleteVariant($data);
    }

    // Product
    public function Product()
    {
        return view('Backoffice.Product.Product');
    }

    function viewInsertProduct()
    {
        $param["mode"] = 1; // 1 = insert, 2 = update
        $param["data"] = [];
        $param["title"] = "Insert Produk";
        return view('Backoffice.Product.insertProduct')->with($param);
    }

    function ViewUpdateProduct($id)
    {
        $param["mode"] = 2; // 1 = insert, 2 = update
        $param["data"] = (new Product())->getProduct(["product_id" => $id])[0];
        $param["title"] = "Update Produk";
        return view('Backoffice.Product.insertProduct')->with($param);
    }

    function getProduct(Request $req)
    {
        $data = (new Product())->getProduct();
        return $data;
    }

    function insertProduct(Request $req)
    {
        $data = $req->all();
        $id = (new Product())->insertProduct($data);
        $variant = json_decode($data['product_variant'], true);
        $relasi = json_decode($data['product_relasi'], true);
        foreach ($variant as $key => $value) {
            $value['product_id'] = $id;
            $variant[$key]["product_variant_id"] = (new ProductVariant())->insertProductVariant($value);
        }
        foreach ($relasi as $keyRelasi => $value) {
            foreach ($value as $key => $perVariant) {
                $perVariant['product_id'] = $id;
                $perVariant['product_variant_id'] = $variant[$keyRelasi]['product_variant_id'];
                $idv = (new ProductRelation())->insertProductRelation($perVariant);
            }
        }
        (new ProductStock())->syncStock($id);
    }

    function updateProduct(Request $req)
    {
        $data = $req->all();
        $id = [];
        $variant = json_decode($data['product_variant'], true);
        (new Product())->updateProduct($data);
        foreach ($variant as $key => $value) {
            $value['product_id'] = $data["product_id"];
            if (!isset($value["product_variant_id"])) $t = (new ProductVariant())->insertProductVariant($value);
            else $t = (new ProductVariant())->updateProductVariant($value);
            $variant[$key]["product_variant_id"] = $t;
            array_push($id, $t);
        }
        ProductVariant::where('product_id', '=', $data["product_id"])->whereNotIn("product_variant_id", $id)->update(["status" => 0]);
        $id = [];
        foreach (json_decode($data['product_relasi'], true) as $keyRelasi => $value) {
            foreach ($value as $key => $perVariant) {
                $perVariant['product_variant_id'] = $variant[$keyRelasi]['product_variant_id'];

                if (!isset($perVariant["pr_id"])) $t = (new ProductRelation())->insertProductRelation($perVariant);
                else $t = (new ProductRelation())->updateProductRelation($perVariant);
                array_push($id, $t);
            }
        }
        (new ProductStock())->syncStock($data["product_id"]);
        ProductRelation::whereNotIn("pr_id", $id)->update(["status" => 0]);
    }

    function deleteProduct(Request $req)
    {
        $data = $req->all();
        return (new Product())->deleteProduct($data);
    }

    function getProductVariant(Request $req)
    {
        $data = (new ProductVariant())->getProductVariant([
            "search_product" => $req->search_product,
            "category_id" => $req->category_id
        ]);
        return response()->json($data);
    }

    // Supplies
    public function Supplies()
    {
        return view('Backoffice.Product.Supplies');
    }

    function getSupplies(Request $req)
    {
        $data = (new Supplies())->getSupplies();
        return response()->json($data);
    }

    function insertSupplies(Request $req)
    {
        $data = $req->all();
        $id = (new Supplies())->insertSupplies($data);
        foreach (json_decode($data['supplies_variant'], true) as $key => $value) {
            $value['supplies_id'] = $id;
            (new SuppliesVariant())->insertSuppliesVariant($value);
        }
    }

    function updateSupplies(Request $req)
    {
        $data = $req->all();
        $id = [];
        (new Supplies())->updateSupplies($data);
        foreach (json_decode($data['supplies_variant'], true) as $key => $value) {
            $value['supplies_id'] = $data["supplies_id"];
            if (!isset($value["supplies_variant_id"])) $t = (new SuppliesVariant())->insertSuppliesVariant($value);
            else $t = (new SuppliesVariant())->updateSuppliesVariant($value);
            array_push($id, $t);
        }
        SuppliesVariant::where('supplies_id', '=', $data["supplies_id"])->whereNotIn("supplies_variant_id", $id)->update(["status" => 0]);
    }

    function deleteSupplies(Request $req)
    {
        $data = $req->all();
        return (new Supplies())->deleteSupplies($data);
    }

    function insertSuppliesUnit(Request $req)
    {
        $suppliesId = $req->supplies_id;
        $units = json_decode($req->units, true);
        $idUnits = [];

        foreach ($units as $u) {
            $data = [
                "supplies_id" => $suppliesId,
                "unit_id" => $u,
                "status" => 1
            ];
            $idUnits[] = (new SuppliesUnit())->insertSuppliesUnit($data);
        }

        return response()->json(["id_units" => $idUnits]);
    }

    function insertSuppliesRelation(Request $req)
    {
        $relations = json_decode($req->input('relations'), true);

        foreach ($relations as $rel) {
            $data = [
                "su_id_1" => $rel["su_id_1"],
                "su_id_2" => $rel["su_id_2"],
                "sr_value_1" => $rel["sr_value_1"],
                "sr_value_2" => $rel["sr_value_2"],
            ];

            (new SuppliesRelation())->insertSuppliesRelation($data);
        }

        return response()->json(["success" => true]);
    }
}
