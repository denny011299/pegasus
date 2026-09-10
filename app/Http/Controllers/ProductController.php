<?php

namespace App\Http\Controllers;

use App\Models\Bom;
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

    // Product / Bahan Kimia (product_kind)
    private function productPageConfig(string $kind): array
    {
        $kind = Product::normalizeKind($kind);
        $isChemical = $kind === Product::KIND_CHEMICAL;

        return [
            'product_kind' => $kind,
            'page_title' => $isChemical ? 'Bahan Kimia' : 'Produk',
            'module_name' => $isChemical ? 'Daftar Bahan Kimia' : 'Daftar Produk',
            'list_url' => $isChemical ? '/chemical' : '/product',
            'get_url' => $isChemical ? '/getChemical' : '/getProduct',
            'insert_url' => $isChemical ? '/insertChemical' : '/insertProduct',
            'update_url' => $isChemical ? '/updateChemical' : '/updateProduct',
            'delete_url' => $isChemical ? '/deleteChemical' : '/deleteProduct',
            'edit_url_prefix' => $isChemical ? '/updateChemical/' : '/updateProduct/',
            'search_placeholder' => $isChemical ? 'Cari Bahan Kimia' : 'Cari Produk',
            'delete_confirm' => $isChemical
                ? 'Apakah yakin ingin menghapus bahan kimia ini?'
                : 'Apakah yakin ingin menghapus produk ini?',
            'delete_success' => $isChemical
                ? 'Berhasil delete bahan kimia'
                : 'Berhasil delete produk',
            'name_label' => $isChemical ? 'Nama Bahan Kimia' : 'Nama Produk',
            'add_label' => $isChemical ? 'Tambah Bahan Kimia' : 'Tambah Produk',
            'update_label' => $isChemical ? 'Update Bahan Kimia' : 'Update Produk',
            'insert_success' => $isChemical ? 'Berhasil Tambah Bahan Kimia' : 'Berhasil Tambah Produk',
            'update_success' => $isChemical ? 'Berhasil Update Bahan Kimia' : 'Berhasil Update Produk',
        ];
    }

    public function Product()
    {
        return view('Backoffice.Product.Product', [
            'productPage' => $this->productPageConfig(Product::KIND_PRODUCT),
        ]);
    }

    public function Chemical()
    {
        return view('Backoffice.Product.Product', [
            'productPage' => $this->productPageConfig(Product::KIND_CHEMICAL),
        ]);
    }

    function viewInsertProduct()
    {
        return $this->viewInsertByKind(Product::KIND_PRODUCT);
    }

    function viewInsertChemical()
    {
        return $this->viewInsertByKind(Product::KIND_CHEMICAL);
    }

    private function viewInsertByKind(string $kind)
    {
        $cfg = $this->productPageConfig($kind);
        $param["mode"] = 1;
        $param["data"] = [];
        $param["title"] = $kind === Product::KIND_CHEMICAL ? 'Insert Bahan Kimia' : 'Insert Produk';
        $param["productPage"] = $cfg;
        return view('Backoffice.Product.insertProduct')->with($param);
    }

    function ViewUpdateProduct($id)
    {
        return $this->viewUpdateByKind($id, Product::KIND_PRODUCT);
    }

    function ViewUpdateChemical($id)
    {
        return $this->viewUpdateByKind($id, Product::KIND_CHEMICAL);
    }

    private function viewUpdateByKind($id, string $kind)
    {
        $kind = Product::normalizeKind($kind);
        $rows = (new Product())->getProduct(["product_id" => $id]);
        if ($rows->isEmpty()) {
            abort(404);
        }
        $row = $rows[0];
        if (Product::hasKindColumn()) {
            $rowKind = Product::normalizeKind($row->product_kind ?? Product::KIND_PRODUCT);
            if ($rowKind !== $kind) {
                abort(404);
            }
        } elseif ($kind === Product::KIND_CHEMICAL) {
            abort(404);
        }

        $cfg = $this->productPageConfig($kind);
        $param["mode"] = 2;
        $param["data"] = $row;
        $param["title"] = $kind === Product::KIND_CHEMICAL ? 'Update Bahan Kimia' : 'Update Produk';
        $param["productPage"] = $cfg;
        return view('Backoffice.Product.insertProduct')->with($param);
    }

    function getProduct(Request $req)
    {
        return $this->getProductByKind($req, Product::KIND_PRODUCT);
    }

    function getChemical(Request $req)
    {
        return $this->getProductByKind($req, Product::KIND_CHEMICAL);
    }

    private function getProductByKind(Request $req, string $kind)
    {
        // Server-side DataTables (Yajra-compatible JSON)
        if ($req->has('draw')) {
            return $this->getProductDataTable($req, $kind);
        }

        // Legacy (client-side / pemakaian lain)
        $data = (new Product())->getProduct(['product_kind' => Product::normalizeKind($kind)]);
        return response()->json($data);
    }

    /**
     * DataTables server-side untuk halaman Daftar Produk / Bahan Kimia.
     * Response: draw, recordsTotal, recordsFiltered, data[]
     */
    private function getProductDataTable(Request $req, string $kind = Product::KIND_PRODUCT)
    {
        $kind = Product::normalizeKind($kind);
        $cfg = $this->productPageConfig($kind);
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

        if (Product::hasKindColumn()) {
            $base->where('products.product_kind', $kind);
        } elseif ($kind === Product::KIND_CHEMICAL) {
            return response()->json([
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

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
                                // product_unit = JSON array id satuan, mis. ["7","9"] — LIKE saja (MariaDB-safe).
                                // products/units punya collation yang berbeda-beda antar tabel (drift lama),
                                // jadi kedua sisi di-COLLATE eksplisit supaya tidak "Illegal mix of collations".
                                "CONVERT(products.product_unit USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE CONCAT('%\"', CAST(units.unit_id AS CHAR) COLLATE utf8mb4_unicode_ci, '\"%')"
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
        $canEdit = RoleAccess::can($user, $cfg['module_name'], 'edit');
        $canDelete = RoleAccess::can($user, $cfg['module_name'], 'delete');

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
                    $canDelete,
                    $cfg['edit_url_prefix']
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

    private function buildProductActionHtml(
        int $productId,
        bool $canEdit,
        bool $canDelete,
        string $editUrlPrefix = '/updateProduct/'
    ): string
    {
        $html = '';

        if ($canEdit) {
            $html .= '<a class="me-2 btn-action-icon p-2 btn_edit" href="' . e($editUrlPrefix) . $productId . '">'
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

    /**
     * Precheck (no mutation) shared by insertProduct()/updateProduct(): SKU must be unique
     * among ALL active variants (any product — a barcode scan must never be ambiguous), and
     * variant name must be unique among active variants of the SAME product (a product-owner
     * choice; different products may reuse the same variant name, e.g. "Merah"). Checks both
     * within the submitted batch and against existing rows. $productId is null for a brand-new
     * product (nothing can exist for it yet, so only within-batch name dupes are checked).
     */
    private function validateVariantUniqueness(array $variants, ?int $productId): ?string
    {
        $seenSku = [];
        $seenName = [];

        foreach ($variants as $v) {
            $sku = trim((string) ($v['variant_sku'] ?? ''));
            $name = trim((string) ($v['variant_name'] ?? ''));
            if (strtolower($name) === 'standar') {
                $name = '';
            }
            $excludeId = !empty($v['product_variant_id']) ? (int) $v['product_variant_id'] : null;

            if ($sku !== '') {
                if (isset($seenSku[$sku])) {
                    return "SKU \"{$sku}\" dipakai lebih dari satu varian pada penyimpanan ini.";
                }
                $seenSku[$sku] = true;

                $q = ProductVariant::where('product_variant_sku', $sku)->where('status', 1);
                if ($excludeId) {
                    $q->where('product_variant_id', '<>', $excludeId);
                }
                if ($q->exists()) {
                    return "SKU \"{$sku}\" sudah dipakai produk lain.";
                }
            }

            if ($name !== '') {
                if (isset($seenName[$name])) {
                    return "Nama varian \"{$name}\" dipakai lebih dari satu kali pada produk ini.";
                }
                $seenName[$name] = true;

                if ($productId) {
                    $q = ProductVariant::where('product_id', $productId)
                        ->where('product_variant_name', $name)
                        ->where('status', 1);
                    if ($excludeId) {
                        $q->where('product_variant_id', '<>', $excludeId);
                    }
                    if ($q->exists()) {
                        return "Nama varian \"{$name}\" sudah dipakai varian lain pada produk ini.";
                    }
                }
            }
        }

        return null;
    }

    function insertProduct(Request $req)
    {
        return $this->insertProductByKind($req, Product::KIND_PRODUCT);
    }

    function insertChemical(Request $req)
    {
        return $this->insertProductByKind($req, Product::KIND_CHEMICAL);
    }

    private function insertProductByKind(Request $req, string $kind)
    {
        $data = $req->all();
        $data['product_kind'] = Product::normalizeKind($kind);

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

        $variant = $this->sanitizeVariantValues(json_decode($data['product_variant'], true) ?: []);
        $safetyPayload = $this->extractSafetyPayload($variant);
        $variant = $this->stripSafetyFromVariants($variant);
        $relasi = json_decode($data['product_relasi'], true);

        // Diperbaiki (2026-08-05): regresi dari fix 2026-08-03 — sempat berubah jadi
        // insertProduct() dipanggil DUA KALI, yang pertama sebelum precheck ini sama sekali
        // (jadi row Product sudah permanen dibuat walau precheck akhirnya menolak), yang kedua
        // (baris di bawah, yang sebenarnya dipakai) tepat setelah precheck lolos. Sekarang precheck
        // ini kembali jadi hal PERTAMA yang terjadi, sebelum satu row pun disentuh — lihat
        // KNOWN_ISSUES.md "Two product SKUs were shared across different products".
        $uniquenessError = $this->validateVariantUniqueness($variant, null);
        if ($uniquenessError) {
            return response()->json(['message' => $uniquenessError]);
        }

        $id = (new Product())->insertProduct($data);
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
        return $this->updateProductByKind($req, Product::KIND_PRODUCT);
    }

    function updateChemical(Request $req)
    {
        return $this->updateProductByKind($req, Product::KIND_CHEMICAL);
    }

    private function updateProductByKind(Request $req, string $kind)
    {
        $data = $req->all();
        if (! $this->productMatchesKind((int) ($data['product_id'] ?? 0), Product::normalizeKind($kind))) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $id = [];
        $variant = $this->sanitizeVariantValues(json_decode($data['product_variant'], true) ?: []);
        $safetyPayload = $this->extractSafetyPayload($variant);
        $variant = $this->stripSafetyFromVariants($variant);

        // Diperbaiki (2026-08-05): regresi dari fix 2026-08-03 — updateProduct() sempat kehilangan
        // panggilan ke precheck ini sama sekali, jadi mengganti nama sebuah varian supaya sama
        // dengan varian lain pada produk yang sama (atau SKU yang sudah dipakai produk lain) tidak
        // ditolak lagi. Dicek per varian, exclude dirinya sendiri lewat product_variant_id yang
        // sudah ada di payload — lihat validateVariantUniqueness().
        $uniquenessError = $this->validateVariantUniqueness($variant, (int) $data['product_id']);
        if ($uniquenessError) {
            return response()->json(['message' => $uniquenessError]);
        }

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
        // Varian yang dihapus dari form edit → soft delete + cascade BOM (QC19)
        $removedVariantIds = ProductVariant::where('product_id', '=', $data["product_id"])
            ->whereNotIn("product_variant_id", $id)
            ->where('status', 1)
            ->pluck('product_variant_id')
            ->all();
        if ($removedVariantIds !== []) {
            ProductVariant::whereIn('product_variant_id', $removedVariantIds)->update(["status" => 0]);
            (new Bom())->softDeleteByVariantIds($removedVariantIds);
        }
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
        return $this->deleteProductByKind($req, Product::KIND_PRODUCT);
    }

    function deleteChemical(Request $req)
    {
        return $this->deleteProductByKind($req, Product::KIND_CHEMICAL);
    }

    private function deleteProductByKind(Request $req, string $kind)
    {
        $data = $req->all();
        if (! $this->productMatchesKind((int) ($data['product_id'] ?? 0), Product::normalizeKind($kind))) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }
        return (new Product())->deleteProduct($data);
    }

    private function productMatchesKind(int $productId, string $kind): bool
    {
        if ($productId < 1) {
            return false;
        }
        $product = Product::where('product_id', $productId)->where('status', 1)->first();
        if (! $product) {
            return false;
        }
        if (! Product::hasKindColumn()) {
            return $kind === Product::KIND_PRODUCT;
        }

        return Product::normalizeKind($product->product_kind ?? Product::KIND_PRODUCT) === $kind;
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
        if ($req->has('draw')) {
            return $this->getSuppliesDataTable($req);
        }

        $data = (new Supplies())->getSupplies($req->all());
        return response()->json($data);
    }

    /**
     * DataTables server-side daftar Bahan Mentah.
     * Response: draw, recordsTotal, recordsFiltered, data[]
     */
    private function getSuppliesDataTable(Request $req)
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
        $kindFilter = trim((string) $req->input('supplies_kind', ''));
        $orderColIdx = (int) data_get($req->input('order'), '0.column', 0);
        $orderDir = strtolower((string) data_get($req->input('order'), '0.dir', 'asc')) === 'desc'
            ? 'desc'
            : 'asc';

        $columns = [
            0 => 'supplies.supplies_name',
            1 => 'supplies.supplies_kind',
            2 => 'supplies.supplies_name',
            3 => 'supplies.supplies_name',
            4 => 'supplies.supplies_desc',
            5 => 'st.staff_name',
            6 => 'supplies.supplies_id',
        ];
        $orderCol = $columns[$orderColIdx] ?? 'supplies.supplies_name';
        if ($orderCol === 'supplies.supplies_kind' && ! Supplies::hasKindColumn()) {
            $orderCol = 'supplies.supplies_name';
        }

        $base = Supplies::query()
            ->from('supplies')
            ->leftJoin('staffs as st', 'st.staff_id', '=', 'supplies.created_by')
            ->where('supplies.status', 1);

        if (Supplies::hasKindColumn() && $kindFilter !== '') {
            $base->where('supplies.supplies_kind', Supplies::normalizeKind($kindFilter));
        }

        $recordsTotal = (clone $base)->count('supplies.supplies_id');

        if ($search !== '') {
            $like = '%' . $search . '%';
            $base->where(function ($q) use ($like) {
                $q->where('supplies.supplies_name', 'like', $like)
                    ->orWhere('supplies.supplies_desc', 'like', $like)
                    ->orWhere('st.staff_name', 'like', $like)
                    ->orWhereExists(function ($sq) use ($like) {
                        $sq->select(DB::raw(1))
                            ->from('supplies_variants')
                            ->whereColumn('supplies_variants.supplies_id', 'supplies.supplies_id')
                            ->where('supplies_variants.status', 1)
                            ->where(function ($vq) use ($like) {
                                $vq->where('supplies_variants.supplies_variant_name', 'like', $like)
                                    ->orWhere('supplies_variants.supplies_variant_sku', 'like', $like);
                            });
                    });
                if (Supplies::hasKindColumn()) {
                    $q->orWhere('supplies.supplies_kind', 'like', $like);
                }
            });
        }

        $recordsFiltered = (clone $base)->count('supplies.supplies_id');

        $select = [
            'supplies.supplies_id',
            'supplies.supplies_name',
            'supplies.supplies_desc',
            'supplies.created_by',
            'st.staff_name as created_by_name',
        ];
        if (Supplies::hasKindColumn()) {
            $select[] = 'supplies.supplies_kind';
            $select[] = 'supplies.trading_product_variant_id';
        }

        $rows = $base
            ->select($select)
            ->orderBy($orderCol, $orderDir)
            ->orderBy('supplies.supplies_id', 'asc')
            ->skip($start)
            ->take($length)
            ->get();

        $ids = $rows->pluck('supplies_id')->map(fn ($id) => (int) $id)->all();
        $bulk = (new Supplies())->getSuppliesBulk($ids);

        $user = Session::get('user');
        $canEdit = RoleAccess::can($user, 'Daftar Bahan Mentah', 'edit');
        $canDelete = RoleAccess::can($user, 'Daftar Bahan Mentah', 'delete');

        $data = [];
        foreach ($rows as $row) {
            $full = $bulk->get((int) $row->supplies_id);
            $item = $full
                ? json_decode(json_encode($full), true)
                : [
                    'supplies_id' => (int) $row->supplies_id,
                    'supplies_name' => $row->supplies_name,
                    'supplies_desc' => $row->supplies_desc,
                    'created_by_name' => $row->created_by_name ?: '-',
                    'sup_variant' => [],
                    'units' => [],
                    'supplies_relasi' => [],
                ];

            $kind = Supplies::hasKindColumn()
                ? Supplies::normalizeKind($item['supplies_kind'] ?? $row->supplies_kind ?? Supplies::KIND_SUPPLY)
                : Supplies::KIND_SUPPLY;
            $item['supplies_kind'] = $kind;
            $item['kind_badge'] = $kind === Supplies::KIND_TRADING
                ? '<span class="badge bg-info-transparent text-info">Trading</span>'
                : '<span class="badge bg-secondary-transparent text-secondary">Bahan Mentah</span>';
            $item['desc'] = ($item['supplies_desc'] ?? null) !== null && $item['supplies_desc'] !== ''
                ? $item['supplies_desc']
                : '-';

            $variants = $item['sup_variant'] ?? [];
            $item['variant_values'] = $variants !== []
                ? implode(', ', array_map(
                    fn ($v) => (string) ($v['supplies_variant_name'] ?? ''),
                    $variants
                ))
                : '-';

            $units = $item['units'] ?? [];
            $item['unit_values'] = $units !== []
                ? implode(', ', array_map(
                    fn ($u) => (string) ($u['unit_name'] ?? $u['unit_short_name'] ?? ''),
                    $units
                ))
                : '-';

            $item['created_by_name'] = $item['created_by_name'] ?? ($row->created_by_name ?: '-');
            $item['action'] = $this->buildSuppliesActionHtml(
                (int) $row->supplies_id,
                $canEdit,
                $canDelete
            );

            $data[] = $item;
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    private function buildSuppliesActionHtml(int $suppliesId, bool $canEdit, bool $canDelete): string
    {
        $html = '';

        if ($canEdit) {
            $html .= '<a class="me-2 btn-action-icon p-2 btn_edit" data-id="' . $suppliesId
                . '" data-bs-target="#edit-supplies" href="javascript:void(0);">'
                . '<i class="fe fe-edit"></i></a>';
        }

        if ($canDelete) {
            $html .= '<a class="p-2 btn-action-icon btn_delete" data-id="' . $suppliesId
                . '" href="javascript:void(0);">'
                . '<i class="fe fe-trash-2"></i></a>';
        }

        return $html !== ''
            ? $html
            : '<span class="text-muted small">—</span>';
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

        $kindError = $this->validateSuppliesTradingLink($data);
        if ($kindError !== null) {
            return response()->json(['message' => $kindError]);
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
        $kindError = $this->validateSuppliesTradingLink($data);
        if ($kindError !== null) {
            return response()->json(['message' => $kindError]);
        }
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
            $changeTexts[] = 'Nama: "' . $beforeName . '" -> "' . $afterName . '"';
        }
        $beforeAlert = (float) ($before->supplies_alert ?? 0);
        $afterAlert = (float) ($after->supplies_alert ?? ($data['supplies_alert'] ?? 0));
        if (abs($beforeAlert - $afterAlert) > 0.000001) {
            $changeTexts[] = 'Batas min: ' . $beforeAlert . ' -> ' . $afterAlert;
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
            'reference' => 'BHN #' . (int) $data['supplies_id'],
            'what_changed' => $whatChanged,
            'summary' => $afterName !== '' ? $afterName : ($beforeName !== '' ? $beforeName : 'Bahan'),
            'url' => url('supplies') . '?supplies_id=' . (int) $data['supplies_id'],
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
        if (Supplies::hasKindColumn()) {
            $data['supplies_kind'] = Supplies::normalizeKind($data['supplies_kind'] ?? Supplies::KIND_SUPPLY);
            $data['trading_product_variant_id'] = Supplies::isTradingKind($data['supplies_kind'])
                ? (int) ($data['trading_product_variant_id'] ?? 0)
                : 0;
        }

        return $data;
    }

    /** @return string|null error message */
    private function validateSuppliesTradingLink(array $data): ?string
    {
        if (! Supplies::hasKindColumn()) {
            return null;
        }
        if (! Supplies::isTradingKind($data['supplies_kind'] ?? null)) {
            return null;
        }
        $pvId = (int) ($data['trading_product_variant_id'] ?? 0);
        if ($pvId <= 0) {
            return 'Trading wajib pilih varian produk yang direlasikan';
        }
        $pv = ProductVariant::where('product_variant_id', $pvId)->where('status', 1)->first();
        if (! $pv) {
            return 'Varian produk relasi tidak ditemukan / tidak aktif';
        }

        return null;
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
