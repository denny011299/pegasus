<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bank;
use App\Models\Bom;
use App\Models\CashCategory;
use App\Models\Category;
use App\Models\CategoryCoa;
use App\Models\Cities;
use App\Models\Coa;
use App\Models\Customer;
use App\Models\District;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Provinces;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\Staff;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\Supplies;
use App\Models\SuppliesVariant;
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

    public function autocompleteArea(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Area();
        $data_city = $p->getArea([
            "area_name" => $keyword
        ]);

        foreach ($data_city as $r) {
            $r->id = $r["area_id"];
            $r->text = $r["area_code"] . " - " . $r["area_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }

    public function autocompleteDistrict(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new District();
        $data_city = $p->getDistrict([
            "name" => $keyword,
            "city_id" => $req->city_id,
        ]);
        foreach ($data_city["data"] as $r) {
            $r->id = $r["id"];
            $r->text = $r["name"];
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
            "search" => $keyword,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["bom_id"];
            $r->text = $r["product_variant_sku"] . " | " . $r["product_name"];
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


    public function autocompleteSuppliesVariant(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new SuppliesVariant();
        $data_city = $p->getSuppliesVariant([
            "supplies_variant_sku" => $keyword,
            "supplier_id" => $req->supplier_id,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["supplies_id"];
            $r->text = $r["supplies_variant_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }
    public function autocompleteSuppliesVariantOnly(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new SuppliesVariant();
        $data_city = $p->getSuppliesVariant([
            "supplies_variant_sku" => $keyword,
            "supplier_id" => $req->supplier_id,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["supplies_variant_id"];
            $r->text = $r["supplies_variant_name"];
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
            $r->text = $r["pr_name"] . " " . $r["product_variant_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }
    public function autocompleteProductVariants(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;
        $p = new ProductVariant();
        $data_city = $p->getProductVariant([
            "product_id" => $keyword,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["product_variant_id"];
            $r->text = $r["pr_name"] . " " . $r["product_variant_name"];
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

    public function autocompleteSupplier(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Supplier();
        $data_city = $p->getSupplier([
            "supplier_name" => $keyword,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["supplier_id"];
            $r->text = $r["supplier_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }

    public function autocompleteStaffSales(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Staff();
        $data_city = $p->getStaff([
            "staff_name" => $keyword,
            "role_id" => 3
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["staff_id"];
            $r->text = $r["staff_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }
    public function autocompleteCashCategory(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new CashCategory();
        $data_city = $p->getCashCategory([
            "cc_name" => $keyword,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["cc_id"];
            $r->text = $r["cc_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }

    public function autocompleteStaff(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Staff();
        $data_city = $p->getStaff([
            "staff_name" => $keyword
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["staff_id"];
            $r->text = $r["staff_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }

    public function autocompleteRole(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Role();
        $data_city = $p->getRole([
            "role_name" => $keyword
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["role_id"];
            $r->text = $r["role_name"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }

    public function autocompleteRekening(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new Bank();
        $data_city = $p->getBank([
            "bank_kode" => $keyword
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["bank_id"];
            $r->text = $r["bank_kode"];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }

    public function autocompletePO(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new PurchaseOrder();
        $data_city = $p->getPurchaseOrder([
            "po_id" => $keyword,
            "ids" => $req->ids,
            "pembayaran" => 1
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["poi_id"];
            $r->text = $r['po_supplier_name'] . ' - ' . $r['poi_code'];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }
}
