<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\Category;
use App\Models\CategoryCoa;
use App\Models\Cities;
use App\Models\Coa;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Provinces;
use App\Models\Staff;
use App\Models\Store;
use App\Models\Supplies;
use App\Models\Unit;
use App\Models\Variant;
use Illuminate\Http\Request;

use function Laravel\Prompts\alert;

class AutocompleteController extends Controller
{
    public function autocompleteCity(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Cities();
        $data_city = $p->get_data_simple_city([
            "prov_id" => $req->prov_id,
            "city_name" => $keyword,
        ]);


        foreach ($data_city['data'] as $r) {
            $r->id = $r["city_id"];
            $r->text = $r["city_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }

    public function autocompleteProv(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Provinces();
        $data_city = $p->get_data([
            "prov_name" => $keyword
        ]);


        foreach ($data_city['data'] as $r) {
            $r->id = $r["prov_id"];
            $r->text = $r["prov_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }

    public function autocompleteUnit(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Unit();
        $data_city = $p->getUnit([
            "unit_short_name" => $keyword
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["unit_id"];
            $r->text = $r["unit_short_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }
    public function autocompleteCategory(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Category();
        $data_city = $p->getCategory([
            "category_name" => $keyword,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["category_id"];
            $r->text = $r["category_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }
    public function autocompleteVariant(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Variant();
        $data_city = $p->getVariant([
            "variant_name" => $keyword,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["variant_id"];
            $r->text = $r["variant_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }

    public function autocompleteBom(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Bom();
        $data_city = $p->getBom([
            "product_id" => $keyword,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["bom_id"];
            $r->text = $r["product_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }

    public function autocompleteProduct(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Product();
        $data_city = $p->getProduct([
            "product_name" => $keyword,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["product_id"];
            $r->text = $r["product_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }


    public function autocompleteSupplies(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Supplies();
        $data_city = $p->getSupplies([
            "supplies_name" => $keyword,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["supplies_id"];
            $r->text = $r["supplies_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }
    public function autocompleteProductVariant(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new ProductVariant();
        $data_city = $p->getProductVariant([
            "product_id" => $keyword,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["product_id"];
            $r->text = $r["product_name"] ." ". $r["product_variant_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }

    public function autocompleteCustomer(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Customer();
        $data_city = $p->getCustomer([
            "customer_name" => $keyword,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["customer_id"];
            $r->text = $r["customer_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }
}
