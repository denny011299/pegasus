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
use App\Models\DashboardChangeLog;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\SuppliesUnit;
use App\Models\SuppliesVariant;
use App\Models\Unit;
use App\Models\Variant;
use App\Support\RoleAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;

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
        // Server-side DataTables (Yajra-compatible JSON)
        if ($req->has('draw')) {
            return $this->getProductDataTable($req);
        }

        // Legacy (client-side / pemakaian lain)
        $data = (new Product())->getProduct();
        return response()->json($data);
    }

    /**
     * DataTables server-side untuk halaman Daftar Produk.
     * Response: draw, recordsTotal, recordsFiltered, data[]
     */
    private function getProductDataTable(Request $req)
    {
        $draw = (int) $req->input('draw', 1);
        $start = max(0, (int) $req->input('start', 0));
        $length = (int) $req->input('length', 10);
        if ($length < 1) {
            $length = 10;
        }
        if ($length > 100) {
            $length = 100;
        }

        $search = trim((string) data_get($req->input('search'), 'value', ''));
        $orderColIdx = (int) data_get($req->input('order'), '0.column', 0);
        $orderDir = strtolower((string) data_get($req->input('order'), '0.dir', 'asc')) === 'desc'
            ? 'desc'
            : 'asc';

        $columns = [
            0 => 'products.product_name',
            1 => 'cat.category_name',
            2 => 'products.product_name', // unit_values (derived)
            3 => 'products.product_name', // variant_values (derived)
            4 => 'st.staff_name',
            5 => 'products.product_id',   // action — no meaningful sort
        ];
        $orderCol = $columns[$orderColIdx] ?? 'products.product_name';

        $base = Product::query()
            ->from('products')
            ->leftJoin('categories as cat', 'cat.category_id', '=', 'products.category_id')
            ->leftJoin('staffs as st', 'st.staff_id', '=', 'products.created_by')
            ->where('products.status', 1);

        $recordsTotal = (clone $base)->count('products.product_id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $base->where(function ($q) use ($like, $search) {
                $q->where('products.product_name', 'like', $like)
                    ->orWhere('cat.category_name', 'like', $like)
                    ->orWhere('st.staff_name', 'like', $like)
                    ->orWhereExists(function ($sq) use ($like) {
                        $sq->select(DB::raw(1))
                            ->from('product_variants')
                            ->whereColumn('product_variants.product_id', 'products.product_id')
                            ->where('product_variants.status', 1)
                            ->where('product_variants.product_variant_name', 'like', $like);
                    })
                    ->orWhereExists(function ($sq) use ($like) {
                        $sq->select(DB::raw(1))
                            ->from('units')
                            ->where('units.status', 1)
                            ->where('units.unit_name', 'like', $like)
                            ->whereRaw(
                                'JSON_CONTAINS(products.product_unit, JSON_QUOTE(CAST(units.unit_id AS CHAR)), "$")
                                 OR JSON_CONTAINS(products.product_unit, CAST(units.unit_id AS JSON), "$")'
                            );
                    });
            });
        }

        $recordsFiltered = (clone $base)->count('products.product_id');

        $rows = $base
            ->select([
                'products.product_id',
                'products.product_name',
                'products.category_id',
                'products.product_unit',
                'products.created_by',
                'cat.category_name as product_category',
                'st.staff_name as created_by_name',
            ])
            ->orderBy($orderCol, $orderDir)
            ->orderBy('products.product_id', 'asc')
            ->skip($start)
            ->take($length)
            ->get();

        $productIds = $rows->pluck('product_id')->all();

        $hasRetailUnitCol = Schema::hasColumn('product_variants', 'retail_unit');

        // Batch variants
        $variantsByProduct = collect();
        if ($productIds !== []) {
            $variantCols = ['product_id', 'product_variant_id', 'product_variant_name'];
            if ($hasRetailUnitCol) {
                $variantCols[] = 'retail_unit';
            }
            $variantsByProduct = ProductVariant::query()
                ->where('status', 1)
                ->whereIn('product_id', $productIds)
                ->orderBy('created_at', 'asc')
                ->get($variantCols)
                ->groupBy('product_id');
        }

        // Batch units
        $unitIdSet = [];
        foreach ($rows as $row) {
            foreach ((array) (json_decode($row->product_unit, true) ?: []) as $unitId) {
                $unitIdSet[(int) $unitId] = true;
            }
        }
        if ($hasRetailUnitCol && $variantsByProduct->isNotEmpty()) {
            foreach ($variantsByProduct as $variants) {
                foreach ($variants as $variantRow) {
                    $retailUnitId = (int) ($variantRow->retail_unit ?? 0);
                    if ($retailUnitId > 0) {
                        $unitIdSet[$retailUnitId] = true;
                    }
                }
            }
        }
        $unitsMap = $unitIdSet !== []
            ? Unit::whereIn('unit_id', array_keys($unitIdSet))->get()->keyBy('unit_id')
            : collect();

        $user = Session::get('user');
        $canEdit = RoleAccess::can($user, 'Daftar Produk', 'edit');
        $canDelete = RoleAccess::can($user, 'Daftar Produk', 'delete');

        $data = [];
        foreach ($rows as $row) {
            $unitIds = (array) (json_decode($row->product_unit, true) ?: []);
            $unitNames = [];
            foreach ($unitIds as $unitId) {
                $u = $unitsMap->get((int) $unitId);
                if ($u) {
                    $unitNames[] = $u->unit_name;
                }
            }

            $variantNames = ($variantsByProduct->get($row->product_id) ?? collect())
                ->map(function ($variantRow) use ($unitsMap, $hasRetailUnitCol) {
                    $variantName = trim((string) ($variantRow->product_variant_name ?? ''));
                    if (! $hasRetailUnitCol) {
                        return $variantName;
                    }
                    $retailUnitId = (int) ($variantRow->retail_unit ?? 0);
                    $retailUnitName = '-';
                    if ($retailUnitId > 0) {
                        $unit = $unitsMap->get($retailUnitId);
                        $retailUnitName = $unit->unit_short_name ?? $unit->unit_name ?? '-';
                    }
                    return trim($variantName . ' [Eceran: ' . $retailUnitName . ']');
                })
                ->filter()
                ->values()
                ->all();

            $data[] = [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'product_category' => $row->product_category ?: '-',
                'unit_values' => $unitNames !== [] ? implode(', ', $unitNames) : '-',
                'variant_values' => $variantNames !== [] ? implode(', ', $variantNames) : '-',
                'created_by_name' => $row->created_by_name ?: '-',
                'action' => $this->buildProductActionHtml(
                    (int) $row->product_id,
                    $canEdit,
                    $canDelete
                ),
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function buildProductActionHtml(int $productId, bool $canEdit, bool $canDelete): string
    {
        $html = '';

        if ($canEdit) {
            $html .= '<a class="me-2 btn-action-icon p-2" href="/updateProduct/' . $productId . '">'
                . '<i class="fe fe-edit"></i></a>';
        }

        if ($canDelete) {
            $html .= '<a class="p-2 btn-action-icon btn_delete" data-id="' . $productId . '" href="javascript:void(0);">'
                . '<i class="fe fe-trash-2"></i></a>';
        }

        return $html !== ''
            ? $html
            : '<span class="text-muted small">—</span>';
    }

    function insertProduct(Request $req)
    {
        $data = $req->all();

        // // Pengecekan Unique
        // $productName = trim(strtolower($data['product_name']));
        // $exists = Product::whereRaw('LOWER(product_name) = ?', [$productName])
        //     ->where('status', 1)
        //     ->exists();
        // if ($exists == true) {
        //     return response()->json([
        //         'message' => 'Nama produk sudah digunakan'
        //     ]);
        // }

        $id = (new Product())->insertProduct($data);
        $variant = $this->sanitizeVariantValues(json_decode($data['product_variant'], true) ?: []);
        $safetyPayload = $this->extractSafetyPayload($variant);
        $variant = $this->stripSafetyFromVariants($variant);
        $relasi = json_decode($data['product_relasi'], true);
        foreach ($variant as $key => $value) {
            $value['product_id'] = $id;
            $variant[$key]["product_variant_id"] = (new ProductVariant())->insertProductVariant($value);
            if (isset($safetyPayload[$key])) {
                $safetyPayload[$key]['product_variant_id'] = $variant[$key]["product_variant_id"];
            }
        }
        foreach ($relasi as $keyRelasi => $value) {
            foreach ($value as $key => $perVariant) {
                $perVariant['product_id'] = $id;
                $perVariant['product_variant_id'] = $variant[$keyRelasi]['product_variant_id'];
                $idv = (new ProductRelation())->insertProductRelation($perVariant);
            }
        }
        (new ProductStock())->syncStock($id);
        $this->applySafetyForActiveWarehouse($id, $safetyPayload);
        $this->applyAlertForActiveWarehouse($id, $variant);
        return 1;
    }

    function updateProduct(Request $req)
    {
        $data = $req->all();
        $id = [];
        $variant = $this->sanitizeVariantValues(json_decode($data['product_variant'], true) ?: []);
        $safetyPayload = $this->extractSafetyPayload($variant);
        $variant = $this->stripSafetyFromVariants($variant);
        (new Product())->updateProduct($data);
        foreach ($variant as $key => $value) {
            $value['product_id'] = $data["product_id"];
            if (!isset($value["product_variant_id"])) $t = (new ProductVariant())->insertProductVariant($value);
            else $t = (new ProductVariant())->updateProductVariant($value);
            $variant[$key]["product_variant_id"] = $t;
            if (isset($safetyPayload[$key])) {
                $safetyPayload[$key]['product_variant_id'] = $t;
            }
            array_push($id, $t);
        }
        ProductVariant::where('product_id', '=', $data["product_id"])->whereNotIn("product_variant_id", $id)->update(["status" => 0]);
        $id = [];
        foreach (json_decode($data['product_relasi'], true) as $keyRelasi => $value) {
            $pvr_id = $variant[$keyRelasi]['product_variant_id'] ?? 0;
            $id = [];
            $activeUnitPairs = [];
            
            // Jika ada data relasi dari frontend
            if (!empty($value)) {
                foreach ($value as $key => $perVariant) {
                    $perVariant['product_variant_id'] = $pvr_id;
                    
                    // Konversi pr_id dengan aman
                    $current_pr_id = isset($perVariant['pr_id']) ? intval($perVariant['pr_id']) : 0;
                    $perVariant['pr_id'] = $current_pr_id;

                    // Logic Insert atau Update
                    if ($current_pr_id == 0) {
                        $t = (new ProductRelation())->insertProductRelation($perVariant);
                    } else {
                        $t = (new ProductRelation())->updateProductRelation($perVariant);
                    }
                    
                    // Simpan ID yang aktif ke array $id
                    if ($t) $id[] = $t;

                    $activeUnitPairs[] = [
                        'pr_unit_id_1' => $perVariant['pr_unit_id_1'],
                        'pr_unit_id_2' => $perVariant['pr_unit_id_2'],
                    ];
                }
            }
            if ($pvr_id == 0) continue;
            ProductRelation::where('product_variant_id', $pvr_id)
                ->whereNotIn('pr_id', $id)
                ->update(['status' => 0]);
        }
        (new ProductStock())->syncStock($data["product_id"]);
        $this->applySafetyForActiveWarehouse((int) $data["product_id"], $safetyPayload);
        $this->applyAlertForActiveWarehouse((int) $data["product_id"], $variant);
        return 1;
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

    public function BarcodePrint()
    {
        return view('Backoffice.Product.barcode');
    }

    function getBarcodeProducts(Request $req)
    {
        $q = trim((string) $req->get('q', ''));

        $productRows = DB::table('product_variants as pv')
            ->join('products as p', 'p.product_id', '=', 'pv.product_id')
            ->where('pv.status', 1)
            ->where('p.status', 1)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('p.product_name', 'like', '%' . $q . '%')
                        ->orWhere('pv.product_variant_name', 'like', '%' . $q . '%')
                        ->orWhere('pv.product_variant_sku', 'like', '%' . $q . '%')
                        ->orWhere('pv.product_variant_barcode', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('p.product_name')
            ->orderBy('pv.product_variant_name')
            ->limit(25)
            ->get([
                DB::raw("'product' as item_type"),
                DB::raw('pv.product_variant_id as item_id'),
                'pv.product_variant_id',
                'p.product_name as nama_produk',
                'pv.product_variant_name as nama_varian',
                'pv.product_variant_sku as sku',
                'pv.product_variant_barcode as barcode',
                'pv.product_variant_price as harga',
            ]);

        $suppliesRows = DB::table('supplies_variants as sv')
            ->join('supplies as s', 's.supplies_id', '=', 'sv.supplies_id')
            ->where('sv.status', 1)
            ->where('s.status', 1)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('s.supplies_name', 'like', '%' . $q . '%')
                        ->orWhere('sv.supplies_variant_name', 'like', '%' . $q . '%')
                        ->orWhere('sv.supplies_variant_sku', 'like', '%' . $q . '%')
                        ->orWhere('sv.supplies_variant_barcode', 'like', '%' . $q . '%');
                });
            })
            ->orderBy('s.supplies_name')
            ->orderBy('sv.supplies_variant_name')
            ->limit(25)
            ->get([
                DB::raw("'supplies' as item_type"),
                DB::raw('sv.supplies_variant_id as item_id'),
                DB::raw('NULL as product_variant_id'),
                's.supplies_name as nama_produk',
                'sv.supplies_variant_name as nama_varian',
                'sv.supplies_variant_sku as sku',
                'sv.supplies_variant_barcode as barcode',
                'sv.supplies_variant_price as harga',
            ]);

        $rows = $productRows->concat($suppliesRows)->map(function ($r) {
            $barcode = trim((string) ($r->barcode ?? ''));
            if ($barcode === '') {
                $barcode = trim((string) ($r->sku ?? ''));
            }
            $r->barcode = $barcode;
            return $r;
        })->take(40)->values();

        return response()->json($rows);
    }

    function printBarcodePdf(Request $req)
    {
        $rawItems = $req->input('items_json', '[]');
        $decoded = json_decode((string) $rawItems, true);
        if (!is_array($decoded)) {
            return back()->with('error', 'Data barcode tidak valid.');
        }

        $list = [];
        foreach ($decoded as $item) {
            $qty = (int) ($item['qty_print'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $barcode = trim((string) ($item['barcode'] ?? ''));
            if ($barcode === '') {
                $barcode = trim((string) ($item['sku'] ?? ''));
            }
            if ($barcode === '') {
                continue;
            }
            $list[] = (object) [
                'nama_produk' => (string) ($item['nama_produk'] ?? '-'),
                'nama_varian' => (string) ($item['nama_varian'] ?? ''),
                'barcode' => $barcode,
                'harga' => (float) ($item['harga'] ?? 0),
                'qty_print' => min(500, $qty),
            ];
        }

        if (count($list) === 0) {
            return back()->with('error', 'Tidak ada item barcode yang dicetak.');
        }

        $showName = (int) $req->input('nama', 1) === 1 ? 1 : 0;
        $showPrice = (int) $req->input('harga', 1) === 1 ? 1 : 0;
        $paperSize = $req->input('paper_size', 'a4');

        $viewData = [
            'list'  => $list,
            'nama'  => $showName,
            'harga' => $showPrice,
        ];

        if ($paperSize === 'label') {
            $pdf = Pdf::loadView('Backoffice.PDF.Barcode', $viewData)
                ->setPaper([0, 0, 198.43, 48.19], 'portrait');
        } else {
            $viewData['paper_size'] = $paperSize;
            $paper = $paperSize === 'a5' ? 'a5' : 'a4';
            $pdf = Pdf::loadView('Backoffice.PDF.BarcodeSheet', $viewData)
                ->setPaper($paper, 'portrait');
        }

        return $pdf->stream('barcode-' . now()->format('YmdHis') . '.pdf');
    }

    // Supplies
    public function Supplies()
    {
        return view('Backoffice.Product.Supplies');
    }

    function getSupplies(Request $req)
    {
        $data = (new Supplies())->getSupplies($req->all());
        return response()->json($data);
    }

    function insertSupplies(Request $req)
    {
        $data = $this->sanitizeSuppliesValues($req->all());

        // Pengecekan Unique
        $suppliesName = trim(strtolower($data['supplies_name']));
        $exists = Supplies::whereRaw('LOWER(supplies_name) = ?', [$suppliesName])
            ->exists();
        if ($exists == true) {
            return response()->json([
                'message' => 'Nama bahan sudah digunakan'
            ]);
        }
        
        $id = (new Supplies())->insertSupplies($data);
        foreach (json_decode($data['supplies_variant'], true) ?: [] as $key => $value) {
            $value['supplies_id'] = $id;
            (new SuppliesVariant())->insertSuppliesVariant($value);
        }
        foreach (json_decode($data['supplies_relasi'], true) as $key => $value) {
            $value['supplies_id'] = $id;
            (new SuppliesRelation())->insertSuppliesRelation($value);
        }
        (new SuppliesStock())->syncStock($id);
        return 1;
    }

    function updateSupplies(Request $req)
    {
        $data = $this->sanitizeSuppliesValues($req->all());
        $id = [];
        $id_r = [];
        $before = Supplies::find($data["supplies_id"]);
        (new Supplies())->updateSupplies($data);
        foreach (json_decode($data['supplies_variant'], true) ?: [] as $key => $value) {
            $value['supplies_id'] = $data["supplies_id"];
            if (!isset($value["supplies_variant_id"])) $t = (new SuppliesVariant())->insertSuppliesVariant($value);
            else $t = (new SuppliesVariant())->updateSuppliesVariant($value);
            array_push($id, $t);
        }

        foreach (json_decode($data['supplies_relasi'], true) as $key => $value) {
            $value['supplies_id'] = $req->supplies_id;
            if (!isset($value["sr_id"]) || $value["sr_id"] == "") $t =  (new SuppliesRelation())->insertSuppliesRelation($value);
            else $t = (new SuppliesRelation())->updateSuppliesRelation($value);
            array_push($id_r, $t);
        }
        SuppliesRelation::whereNotIn("sr_id", $id_r)->where('supplies_id', '=', $data["supplies_id"])->update(["status" => 0]);
        SuppliesVariant::where('supplies_id', '=', $data["supplies_id"])->whereNotIn("supplies_variant_id", $id)->update(["status" => 0]);
        (new SuppliesStock())->syncStock($data["supplies_id"]);
        $after = Supplies::find($data["supplies_id"]);
        $beforeName = trim((string) ($before->supplies_name ?? ''));
        $afterName = trim((string) ($after->supplies_name ?? ($data['supplies_name'] ?? '')));
        $changeTexts = [];
        if ($beforeName !== '' && $afterName !== '' && strcasecmp($beforeName, $afterName) !== 0) {
            $changeTexts[] = 'Nama: "'.$beforeName.'" -> "'.$afterName.'"';
        }
        $beforeAlert = (float) ($before->supplies_alert ?? 0);
        $afterAlert = (float) ($after->supplies_alert ?? ($data['supplies_alert'] ?? 0));
        if (abs($beforeAlert - $afterAlert) > 0.000001) {
            $changeTexts[] = 'Batas min: '.$beforeAlert.' -> '.$afterAlert;
        }
        if ((int) ($before->supplies_default_unit ?? 0) !== (int) ($after->supplies_default_unit ?? ($data['supplies_default_unit'] ?? 0))) {
            $changeTexts[] = 'Satuan default diperbarui';
        }
        $whatChanged = count($changeTexts) > 0
            ? implode(' | ', $changeTexts)
            : 'Master bahan diperbarui.';
        $actor = session('user');
        DashboardChangeLog::create([
            'module_key' => 'master_bahan',
            'module_label' => 'Master Bahan',
            'reference' => 'BHN #'.(int) $data['supplies_id'],
            'what_changed' => $whatChanged,
            'summary' => $afterName !== '' ? $afterName : ($beforeName !== '' ? $beforeName : 'Bahan'),
            'url' => url('supplies').'?supplies_id='.(int) $data['supplies_id'],
            'url_label' => 'Buka master',
            'created_by' => $actor ? ($actor->staff_id ?? null) : null,
            'meta' => [
                'supplies_id' => (int) $data['supplies_id'],
                'before_name' => $beforeName,
                'after_name' => $afterName,
            ],
        ]);
        return 1;
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

    function getSuppliesVariant(Request $req)
    {
        $data = (new SuppliesVariant())->getSuppliesVariant([
            "search_product" => $req->search_product,
            // "category_id" => $req->category_id
        ]);
        return response()->json($data);
    }

    /**
     * Safety stock hanya boleh diubah oleh role yang punya akses edit.
     */
    private function sanitizeVariantValues(array $variants): array
    {
        $canEdit = RoleAccess::can(Session::get('user'), 'Safety Stock', 'edit');
        foreach ($variants as $i => $variant) {
            $variants[$i]['lead_time_days'] = max(0, (int) ($variant['lead_time_days'] ?? 0));
            if (! $canEdit) {
                unset($variants[$i]['safety_stock'], $variants[$i]['safety_unit_id']);
            } elseif (array_key_exists('safety_stock', $variant)) {
                $variants[$i]['safety_stock'] = max(0, (int) $variant['safety_stock']);
            }
        }

        return $variants;
    }

    private function sanitizeSuppliesValues(array $data): array
    {
        $data['lead_time_days'] = max(0, (int) ($data['lead_time_days'] ?? 0));
        $data['safety_stock'] = max(0, (int) ($data['safety_stock'] ?? 0));

        return $data;
    }

    /** Simpan payload safety terpisah (per index variant) sebelum strip dari save variant. */
    private function extractSafetyPayload(array $variants): array
    {
        $payload = [];
        foreach ($variants as $i => $variant) {
            if (! array_key_exists('safety_stock', $variant) && ! array_key_exists('safety_unit_id', $variant)) {
                continue;
            }
            $payload[$i] = [
                'product_variant_id' => $variant['product_variant_id'] ?? null,
                'safety_stock' => $variant['safety_stock'] ?? 0,
                'safety_unit_id' => $variant['safety_unit_id'] ?? null,
            ];
        }

        return $payload;
    }

    private function stripSafetyFromVariants(array $variants): array
    {
        foreach ($variants as $i => $variant) {
            unset($variants[$i]['safety_stock'], $variants[$i]['safety_unit_id']);
        }

        return $variants;
    }

    private function applySafetyForActiveWarehouse(int $productId, array $safetyPayload): void
    {
        if ($safetyPayload === []) {
            return;
        }
        if (! RoleAccess::can(Session::get('user'), 'Safety Stock', 'edit')) {
            return;
        }

        $warehouseId = ProductStock::resolveWarehouseId();
        if (! $warehouseId) {
            return;
        }

        (new ProductStock())->applySafetyStockForWarehouse($productId, $warehouseId, array_values($safetyPayload));
    }

    /** Simpan peringatan stok ke product_stocks gudang aktif. */
    private function applyAlertForActiveWarehouse(int $productId, array $variants): void
    {
        $warehouseId = ProductStock::resolveWarehouseId();
        if (! $warehouseId || $variants === []) {
            return;
        }

        $payload = [];
        foreach ($variants as $variant) {
            $vid = (int) ($variant['product_variant_id'] ?? 0);
            if ($vid <= 0) {
                continue;
            }
            $payload[] = [
                'product_variant_id' => $vid,
                'alert_stock' => $variant['variant_alert'] ?? 0,
                'alert_unit_id' => $variant['unit_id'] ?? null,
            ];
        }

        if ($payload === []) {
            return;
        }

        (new ProductStock())->applyAlertStockForWarehouse($productId, $warehouseId, $payload);
    }
}
