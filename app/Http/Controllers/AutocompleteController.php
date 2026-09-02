<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Bank;
use App\Models\Bom;
use App\Models\CashCategory;
use App\Models\Category;
use App\Models\Cities;
use App\Models\Customer;
use App\Models\District;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Provinces;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\Supplies;
use App\Models\SuppliesVariant;
use App\Models\Unit;
use App\Models\Variant;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

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
        $page = max(1, (int) ($req->page ?? 1));
        $limit = min(50, max(1, (int) ($req->limit ?? 30)));

        $result = (new Bom())->searchForAutocomplete([
            'search' => $keyword,
            'page' => $page,
        ], $limit);

        foreach ($result['data'] as $r) {
            $r->id = $r['bom_id'];
            // product_name sudah = nama produk + varian
            $r->text = $this->formatProductVariantLabel(
                $r['product_name'] ?? '',
                '',
                $r['product_variant_sku'] ?? ($r['product_sku'] ?? '')
            );
        }

        return response()->json([
            'data' => $result['data'],
            'pagination' => [
                'more' => (bool) $result['more'],
            ],
        ]);
    }

    /**
     * Label autocomplete produk varian (SOP): "SKU | Nama Produk Varian"
     * - Nama = product_name + product_variant_name (spasi ganda dirapikan)
     * - Jika SKU kosong / "-", tampilkan nama saja
     */
    private function formatProductVariantLabel($productName, $variantName = '', $sku = ''): string
    {
        $name = trim(preg_replace(
            '/\s+/',
            ' ',
            trim((string) $productName) . ' ' . trim((string) $variantName)
        ));
        $sku = trim((string) $sku);
        if ($sku !== '' && $sku !== '-') {
            return $name !== '' ? ($sku . ' | ' . $name) : $sku;
        }

        return $name !== '' ? $name : '-';
    }

    private function attachProductDefaultUnits($variants): void
    {
        $productIds = collect($variants)
            ->pluck('product_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return;
        }

        $defaults = Product::query()
            ->leftJoin('units as default_units', 'default_units.unit_id', '=', 'products.unit_id')
            ->whereIn('products.product_id', $productIds)
            ->get([
                'products.product_id',
                'products.unit_id as default_unit_id',
                'default_units.unit_name as default_unit_name',
                'default_units.unit_short_name as default_unit_short_name',
            ])
            ->keyBy('product_id');

        foreach ($variants as $variant) {
            $default = $defaults->get((int) ($variant->product_id ?? 0));
            $variant->default_unit_id = $default?->default_unit_id
                ? (int) $default->default_unit_id
                : null;
            $variant->default_unit_name = $default?->default_unit_name;
            $variant->default_unit_short_name = $default?->default_unit_short_name;
        }
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
        $page = max(1, (int) ($req->page ?? 1));
        $limit = min(50, max(1, (int) ($req->limit ?? 30)));

        $result = (new Supplies())->searchForAutocomplete([
            'search' => $keyword,
            'page' => $page,
        ], $limit);

        foreach ($result['data'] as $r) {
            $r->id = $r['supplies_id'];
            $r->text = $r['supplies_name'];
        }

        return response()->json([
            'data' => $result['data'],
            'pagination' => [
                'more' => (bool) $result['more'],
            ],
        ]);
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
            "supplies_variant_name" => $keyword,
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

    public function searchSuppliesVariantByScan(Request $req)
    {
        $keyword = $req->keyword ?? null;
        if (!$keyword) {
            return response()->json(["data" => []]);
        }

        $p = new SuppliesVariant();
        $results = $p->getSuppliesVariant([
            "search" => $keyword,
        ]);

        return response()->json(["data" => $results]);
    }

    public function searchProductVariantByScan(Request $req)
    {
        $keyword = $req->keyword ?? null;
        if (!$keyword) {
            return response()->json(["data" => []]);
        }

        $p = new ProductVariant();
        $results = $p->searchForAutocomplete([
            'search' => $keyword,
        ], 10);

        foreach ($results as $r) {
            $r->id = $r["product_variant_id"];
            $r->text = $this->formatProductVariantLabel(
                $r["pr_name"] ?? '',
                $r["product_variant_name"] ?? '',
                $r["product_variant_sku"] ?? ''
            );
        }

        return response()->json(["data" => $results]);
    }

    public function autocompleteProductVariant(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;

        $p = new ProductVariant();
        $data_city = $p->searchForAutocomplete([
            'product_id' => $req->product_id,
            'search_product' => $keyword,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["product_id"];
            $r->text = $this->formatProductVariantLabel(
                $r["pr_name"] ?? '',
                $r["product_variant_name"] ?? '',
                $r["product_variant_sku"] ?? ''
            );
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }
    public function autocompleteProductVariants(Request $req)
    {
        $keyword = isset($req->keyword) ? $req->keyword : null;
        $p = new ProductVariant();
        $data_city = $p->searchForAutocomplete([
            'product_id' => $req->product_id,
            'search_product' => $keyword,
        ]);

        foreach ($data_city as $r) {
            $r->id = $r["product_variant_id"];
            $r->text = $this->formatProductVariantLabel(
                $r["pr_name"] ?? '',
                $r["product_variant_name"] ?? '',
                $r["product_variant_sku"] ?? ''
            );
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
            "customer_notes" => $keyword,
            // GitHub #130 (item 37): optional exact lookup, dipakai Cash_Operational.js untuk
            // ambil customer_saldo TERBARU sebelum mengisi default nominal "Pengembalian Dana
            // Langsung" — opsi Select2 di modal itu sering dibuat dari data cache filter halaman
            // (bisa basi kalau saldo berubah sejak filter terakhir di-refresh).
            "customer_id" => $req->customer_id ?? null,
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["customer_id"];
            $r->text = $r["customer_notes"];
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
            "role_name" => "sales"
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
        $keyword = $req->keyword ?? $req->q ?? $req->term ?? null;

        $roles = (new Role())->getRole([
            'role_name' => $keyword,
        ]);

        // Hanya id + text agar Select2 tidak gagal parse payload besar (role_access)
        $data = $roles->map(static function ($role) {
            return [
                'id' => (int) $role->role_id,
                'text' => (string) $role->role_name,
            ];
        })->values()->all();

        return response()->json([
            'data' => $data,
        ]);
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
            "po_id" => $req->po_id,
            "ids" => $req->ids,
            "pembayaran" => 1,
            "search" => $keyword
        ]);


        foreach ($data_city as $r) {
            $r->id = $r["poi_id"];
            $r->text = $r['po_supplier_name'] . ' - ' . $r['poi_code'];
        };

        echo json_encode(array(
            "data" => $data_city
        ));
    }

    public function autocompleteWarehouseType(Request $req)
    {
        $keyword = $req->keyword ?? $req->q ?? $req->term ?? null;

        $data = (new WarehouseType())->getWarehouseType([
            'warehouse_type_name' => $keyword,
        ]);

        foreach ($data as $r) {
            $r->id = $r->id;
            $r->text = $r->warehouse_type_name
                . ((int) ($r->is_main_warehouse ?? 0) === 1 ? ' (Gudang Utama)' : '');
        }

        return response()->json([
            'data' => $data,
        ]);
    }

    public function autocompleteWarehouse(Request $req)
    {
        $keyword = trim((string) ($req->keyword ?? $req->q ?? $req->term ?? ''));

        $mainFirst = filter_var($req->main_first ?? false, FILTER_VALIDATE_BOOLEAN);

        $query = Warehouse::query()
            ->active()
            ->with(['type' => fn($q) => $q->select('id', 'warehouse_type_name', 'is_main_warehouse')]);

        if ($mainFirst) {
            $query->leftJoin('warehouse_types as wt', 'warehouses.warehouse_type_id', '=', 'wt.id')
                ->orderByDesc('wt.is_main_warehouse')
                ->orderBy('warehouses.warehouse_name')
                ->select('warehouses.id', 'warehouses.warehouse_name', 'warehouses.warehouse_type_id');
        } else {
            $query->orderBy('warehouse_name');
        }

        if ($keyword !== '') {
            $query->where(
                $mainFirst ? 'warehouses.warehouse_name' : 'warehouse_name',
                'like',
                '%' . $keyword . '%'
            );
        }

        // Gudang eceran saja (bukan tipe utama)
        if (filter_var($req->retail_only ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->whereHas('type', function ($q) {
                $q->where('is_main_warehouse', 0);
            });
            $user = Session::get('user');
            $assignedIds = Staff::assignedWarehouseIds($user);
            if ($assignedIds !== []) {
                $query->whereIn($mainFirst ? 'warehouses.id' : 'id', $assignedIds);
            }
        }

        // Semua gudang tipe utama (untuk request eceran)
        if (filter_var($req->main_only ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $query->whereHas('type', function ($q) {
                $q->where('is_main_warehouse', 1);
            });
        }

        $rows = $mainFirst
            ? $query->limit(30)->get()
            : $query->limit(30)->get(['id', 'warehouse_name', 'warehouse_type_id']);

        $data = $rows->map(static function ($wh) {
            $typeName = $wh->type->warehouse_type_name ?? null;
            return [
                'id' => (int) $wh->id,
                'text' => $typeName
                    ? $wh->warehouse_name . ' (' . $typeName . ')'
                    : $wh->warehouse_name,
                'warehouse_name' => $wh->warehouse_name,
                'warehouse_type_id' => (int) $wh->warehouse_type_id,
                'is_main_warehouse' => (int) ($wh->type->is_main_warehouse ?? 0),
            ];
        })->values()->all();

        return response()->json(['data' => $data]);
    }
}
