<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use App\Support\UnitStockSorter;

class ProductVariant extends Model
{
    protected $table = "product_variants";
    protected $primaryKey = "product_variant_id";
    public $timestamps = true;
    public $incrementing = true;

    public function getProductVariant($data = [])
    {
        $data = array_merge([
            "product_id" => null,
            "product_variant_sku" => null,
            "product_variant_id" => null,
            "status" => 1,
            "search_product" => null,
            "category_id" => null,
            "search" => null,
        ], $data);

        $result = self::where("product_variants.status", "=", 1);
        $result->join("products as pr", 'pr.product_id', 'product_variants.product_id');
        $result->where("pr.status", "=", 1);

        // Filter berdasarkan product_id
        if ($data["product_id"]) {
            $result->where("product_variants.product_id", "=", $data["product_id"]);
        }

        // Filter berdasarkan SKU
        if ($data["product_variant_sku"]) {
            $result->where("product_variants.product_variant_sku", "like", "%" . $data["product_variant_sku"] . "%");
        }

        if ($data["search_product"]) {
            $result->whereAny([
                "pr.product_name",
                "product_variants.product_variant_name",
                "product_variants.product_variant_sku",
            ], "like", "%" . $data["search_product"] . "%");
        }

        // Filter berdasarkan product_variant_id
        if ($data["product_variant_id"]) {
            $result->where("product_variants.product_variant_id", "=", $data["product_variant_id"]);
        }


        if ($data["search"]) {
            $result->where(function ($q) use ($data) {
                $q->where("product_variants.product_variant_sku", "=", $data["search"])
                    ->orWhere("product_variants.product_variant_barcode", "=", $data["search"]);
            });
        }

        // Filter berdasarkan product_variant_id
        if ($data["category_id"]) {
            $result->join("products as p", 'p.product_id', 'product_variants.product_id');
            $result->where('category_id', '=', $data["category_id"]);
        }

        $result->select('product_variants.*');
        $result->orderBy("pr.product_name", "asc");

        $variants = $result->get();

        // Gudang eceran cuma boleh menghitung/menerima satuan eceran varian ini -- kembaran
        // persis aturan StockController::getStock() (list Stok Produk), supaya konsumen endpoint
        // ini (a.l. CreateStockOpname.js lewat /getProductVariant) tidak menawarkan satuan gudang
        // utama (mis. DOS) di gudang yang memang hanya menyimpan eceran (mis. Piece).
        $warehouseId = ProductStock::resolveWarehouseId(null);
        $isMainWarehouse = true;
        if ($warehouseId) {
            $warehouse = Warehouse::query()
                ->with(['type' => fn ($q) => $q->select('id', 'warehouse_type_name', 'is_main_warehouse')])
                ->find($warehouseId);
            $isMainWarehouse = ! $warehouse || ! $warehouse->type || (int) $warehouse->type->is_main_warehouse === 1;
        }
        $hasRetailUnitColumn = Schema::hasColumn('product_variants', 'retail_unit');

        // Menambahkan nama produk dari relasi
        foreach ($variants as $variant) {
            $p = Product::find($variant->product_id);
            $u =  Unit::whereIn('unit_id', json_decode($p->product_unit, true))->first();

            $variant->pr_name = $p ? $p->product_name : "-";
            $variant->product_unit = $u ? $u->unit_name : "-";
            $variant->product_category = Category::find($p->category_id)->category_name ?? "-";
            $variant->category_id = $p->category_id;
            $variant->pr_unit = Unit::whereIn('unit_id', json_decode($p->product_unit, true))->get();
            $variant->relasi = (new ProductRelation())->getProductRelation(['product_variant_id' => $variant->product_variant_id]);
            $variant->stock = (new ProductStock())->getProductStock([
                "product_variant_id" => $variant->product_variant_id,
                "relations" => $variant->relasi,
            ]);

            if (! $isMainWarehouse) {
                $retailUnitId = $hasRetailUnitColumn ? (int) ($variant->retail_unit ?? 0) : 0;
                $variant->stock = $retailUnitId > 0
                    ? $variant->stock->filter(fn ($s) => (int) $s->unit_id === $retailUnitId)->values()
                    : collect();
            }

            // Get nama unit default

            // Get stock
            $s = ProductStock::where('product_variant_id', $variant->product_variant_id)
                ->where('unit_id', $variant->unit_id)
                ->first();
            $variant->ps_stock = $s ? $s->ps_stock : 0;
        }

        return $variants;
    }

    /**
     * Query ringan untuk Select2 autocomplete — tanpa stok, max N baris.
     *
     * @param  array<string, mixed>  $data
     */
    public function searchForAutocomplete(array $data = [], int $limit = 30)
    {
        $data = array_merge([
            'product_id' => null,
            'search_product' => null,
            'search' => null,
        ], $data);

        $limit = max(1, min($limit, 50));
        $keyword = trim((string) ($data['search_product'] ?? ''));
        $exact = trim((string) ($data['search'] ?? ''));

        $query = self::query()
            ->from('product_variants')
            ->join('products as pr', 'pr.product_id', '=', 'product_variants.product_id')
            ->leftJoin('categories as cat', 'cat.category_id', '=', 'pr.category_id')
            ->where('product_variants.status', 1)
            ->where('pr.status', 1);

        if ($data['product_id']) {
            $query->where('product_variants.product_id', (int) $data['product_id']);
        }

        if ($exact !== '') {
            $query->where(function ($q) use ($exact) {
                $q->where('product_variants.product_variant_sku', $exact)
                    ->orWhere('product_variants.product_variant_barcode', $exact);
            });
        } elseif ($keyword !== '') {
            $like = '%' . $keyword . '%';
            $query->where(function ($q) use ($like) {
                $q->where('pr.product_name', 'like', $like)
                    ->orWhere('product_variants.product_variant_name', 'like', $like)
                    ->orWhere('product_variants.product_variant_sku', 'like', $like);
            });
        }

        $select = [
            'product_variants.product_variant_id',
            'product_variants.product_id',
            'product_variants.product_variant_sku',
            'product_variants.product_variant_name',
            'product_variants.product_variant_barcode',
            'product_variants.unit_id',
            'product_variants.status as product_variant_status',
            'pr.product_name as pr_name',
            'pr.category_id',
            'pr.product_unit as product_unit_ids',
            'pr.unit_id as default_unit_id',
            'pr.status as product_status',
            'cat.category_name as product_category',
        ];

        if (Schema::hasColumn('product_variants', 'retail_unit')) {
            $select[] = 'product_variants.retail_unit';
        }
        if (Schema::hasColumn('product_variants', 'qty_per_pallet')) {
            $select[] = 'product_variants.qty_per_pallet';
        }

        $variants = $query
            ->select($select)
            ->orderBy('pr.product_name')
            ->orderBy('product_variants.product_variant_id')
            ->limit($limit)
            ->get();

        if ($variants->isEmpty()) {
            return $variants;
        }

        $variantIds = $variants->pluck('product_variant_id')->all();
        $unitIdMap = [];

        foreach ($variants as $variant) {
            foreach (json_decode($variant->product_unit_ids, true) ?: [] as $uid) {
                $unitIdMap[(int) $uid] = true;
            }
            if ($variant->default_unit_id) {
                $unitIdMap[(int) $variant->default_unit_id] = true;
            }
            if ($variant->unit_id) {
                $unitIdMap[(int) $variant->unit_id] = true;
            }
        }

        $relations = ProductRelation::query()
            ->where('status', 1)
            ->whereIn('product_variant_id', $variantIds)
            ->orderBy('created_at')
            ->get();

        foreach ($relations as $relation) {
            $unitIdMap[(int) $relation->pr_unit_id_1] = true;
            $unitIdMap[(int) $relation->pr_unit_id_2] = true;
        }

        $units = $unitIdMap !== []
            ? Unit::whereIn('unit_id', array_keys($unitIdMap))->get()->keyBy('unit_id')
            : collect();

        $relationsGrouped = $relations->groupBy('product_variant_id');

        foreach ($variants as $variant) {
            $unitIds = json_decode($variant->product_unit_ids, true) ?: [];
            $prUnit = collect($unitIds)
                ->map(function ($uid) use ($units) {
                    $unit = $units->get((int) $uid);
                    if (! $unit) {
                        return null;
                    }

                    return [
                        'unit_id' => (int) $unit->unit_id,
                        'unit_name' => $unit->unit_name,
                        'unit_short_name' => $unit->unit_short_name ?? $unit->unit_name,
                    ];
                })
                ->filter()
                ->values();

            $variant->pr_unit = $prUnit->all();
            $firstUnit = $prUnit->first();
            $variant->product_unit = $firstUnit['unit_name'] ?? '-';

            $defaultUnit = $units->get((int) $variant->default_unit_id);
            $variant->default_unit = $variant->default_unit_id ? (int) $variant->default_unit_id : null;
            $variant->default_unit_name = $defaultUnit?->unit_name;
            $variant->default_unit_short_name = $defaultUnit?->unit_short_name;

            $variantUnit = $units->get((int) $variant->unit_id);
            $variant->unit_name = $variantUnit?->unit_name
                ?? $defaultUnit?->unit_name
                ?? '-';

            $variant->relasi = ($relationsGrouped->get($variant->product_variant_id) ?? collect())
                ->map(function ($relation) use ($units) {
                    $u1 = $units->get((int) $relation->pr_unit_id_1);
                    $u2 = $units->get((int) $relation->pr_unit_id_2);
                    $relation->pr_unit_name_1 = $u1?->unit_short_name ?? '-';
                    $relation->pr_unit_name_2 = $u2?->unit_short_name ?? '-';

                    return $relation;
                })
                ->values()
                ->all();

            unset($variant->product_unit_ids);
        }

        return $variants;
    }

    /**
     * Same enrichment as getProductVariant(), but one round-trip for many IDs
     * (used by stock opname list to avoid N+1).
     *
     * @param  array<int>  $productVariantIds
     * @return \Illuminate\Support\Collection<string|int, self>
     */
    public function getProductVariantBulk(array $productVariantIds)
    {
        $productVariantIds = array_values(array_unique(array_filter(array_map('intval', $productVariantIds))));
        if ($productVariantIds === []) {
            return collect();
        }

        $result = self::where('product_variants.status', '=', 1)
            ->join('products as pr', 'pr.product_id', '=', 'product_variants.product_id')
            ->where('pr.status', '=', 1)
            ->whereIn('product_variants.product_variant_id', $productVariantIds)
            ->select('product_variants.*')
            ->orderBy('product_variants.created_at', 'asc')
            ->get();

        if ($result->isEmpty()) {
            return collect();
        }

        $productIds = $result->pluck('product_id')->unique()->filter()->all();
        $products = Product::whereIn('product_id', $productIds)->get()->keyBy('product_id');

        $allUnitIds = [];
        foreach ($products as $p) {
            foreach (json_decode($p->product_unit, true) ?: [] as $uid) {
                $allUnitIds[(int) $uid] = true;
            }
        }

        $relations = ProductRelation::where('status', '=', 1)
            ->whereIn('product_variant_id', $productVariantIds)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($relations as $rel) {
            $allUnitIds[(int) $rel->pr_unit_id_1] = true;
            $allUnitIds[(int) $rel->pr_unit_id_2] = true;
        }

        $stocks = ProductStock::where('status', '=', 1)
            ->whereIn('product_variant_id', $productVariantIds)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($stocks as $stk) {
            $allUnitIds[(int) $stk->unit_id] = true;
        }

        $units = $allUnitIds !== []
            ? Unit::whereIn('unit_id', array_keys($allUnitIds))->get()->keyBy('unit_id')
            : collect();

        foreach ($relations as $rel) {
            $u1 = $units->get($rel->pr_unit_id_1);
            $u2 = $units->get($rel->pr_unit_id_2);
            $rel->pr_unit_name_1 = $u1 ? $u1->unit_short_name : '-';
            $rel->pr_unit_name_2 = $u2 ? $u2->unit_short_name : '-';
        }

        foreach ($stocks as $stk) {
            $u = $units->get($stk->unit_id);
            $stk->unit_name = $u ? $u->unit_name : '-';
            $stk->unit_short_name = $u ? $u->unit_short_name : '-';
        }

        $relationsGrouped = $relations->groupBy('product_variant_id');
        $stocksGrouped = $stocks->groupBy('product_variant_id');

        $categoryIds = $products->pluck('category_id')->unique()->filter()->all();
        $categories = $categoryIds !== []
            ? Category::whereIn('category_id', $categoryIds)->pluck('category_name', 'category_id')
            : collect();

        $prUnitsByProductId = [];
        foreach ($products as $pid => $p) {
            $uids = json_decode($p->product_unit, true) ?: [];
            $prUnitsByProductId[$pid] = collect($uids)
                ->map(fn($uid) => $units->get((int) $uid))
                ->filter()
                ->values();
        }

        $out = collect();
        foreach ($result as $variant) {
            $clone = clone $variant;
            $p = $products->get($clone->product_id);
            if (! $p) {
                $clone->pr_name = '-';
                $clone->product_unit = '-';
                $clone->product_category = '-';
                $clone->category_id = null;
                $clone->pr_unit = collect();
                $clone->relasi = collect();
                $clone->stock = collect();
                $clone->ps_stock = 0;
                $out->put($clone->product_variant_id, $clone);

                continue;
            }

            $prUnitCol = $prUnitsByProductId[$p->product_id] ?? collect();
            $uFirst = $prUnitCol->first();
            $clone->pr_name = $p->product_name ?? '-';
            $clone->product_unit = $uFirst ? $uFirst->unit_name : '-';
            $clone->product_category = $categories->get($p->category_id) ?? '-';
            $clone->category_id = $p->category_id;
            $clone->pr_unit = $prUnitCol;
            $clone->relasi = $relationsGrouped->get($clone->product_variant_id, collect());
            $clone->stock = UnitStockSorter::sort(
                $stocksGrouped->get($clone->product_variant_id, collect()),
                $relationsGrouped->get($clone->product_variant_id, collect())
            );

            $s = $clone->stock->firstWhere('unit_id', (int) $clone->unit_id)
                ?? $stocksGrouped->get($clone->product_variant_id, collect())->firstWhere('unit_id', (int) $clone->unit_id);
            $clone->ps_stock = $s ? $s->ps_stock : 0;

            $out->put($clone->product_variant_id, $clone);
        }

        return $out;
    }

    public function insertProductVariant($data)
    {
        if ($data["variant_name"] == "standar") $data["variant_name"] = "";
        $t = new self();
        $t->product_id = $data["product_id"];
        $t->product_variant_name = $data["variant_name"];
        $t->product_variant_sku = $data["variant_sku"];
        $t->unit_id = $data["unit_id"];
        //  $t->product_variant_price = $data["variant_price"]!=""? $data["variant_price"] : 0;
        $t->product_variant_barcode = $data["variant_barcode"] != "" ? $data["variant_barcode"] : $t->generateBarcode();
        $t->product_variant_alert = $data["variant_alert"] != "" ? $data["variant_alert"] : 0;
        $t->product_variant_stock = 0;
        $t->safety_stock = isset($data["safety_stock"]) && $data["safety_stock"] !== ""
            ? (int) $data["safety_stock"]
            : 0;
        $t->safety_unit_id = ! empty($data["safety_unit_id"])
            ? (int) $data["safety_unit_id"]
            : null;
        if (Schema::hasColumn($t->getTable(), 'retail_unit')) {
            $t->retail_unit = ! empty($data["retail_unit"]) ? (int) $data["retail_unit"] : null;
        }
        $t->qty_per_pallet = $this->normalizeQtyPerPallet($data['qty_per_pallet'] ?? null);
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->product_variant_id;
    }

    public function updateProductVariant($data)
    {
        $t = self::find($data["product_variant_id"]);
        if (!$t) {
            return $this->insertProductVariant([
                "product_id"      => $data["product_id"],
                "variant_name"    => $data["variant_name"],
                "variant_sku"     => $data["variant_sku"],
                "variant_alert"   => $data["variant_alert"],
                "variant_barcode" => $data["variant_barcode"] ?? "",
                "unit_id" => $data["unit_id"] ?? 0,
                "safety_stock" => $data["safety_stock"] ?? 0,
                "safety_unit_id" => $data["safety_unit_id"] ?? null,
                "retail_unit" => $data["retail_unit"] ?? null,
                "lead_time_days" => $data["lead_time_days"] ?? 0,
                "qty_per_pallet" => $data["qty_per_pallet"] ?? null,
            ]);
        }
        $t->product_id = $data["product_id"];
        $t->product_variant_name = $data["variant_name"];
        $t->product_variant_sku = $data["variant_sku"];
        //  $t->product_variant_price = $data["variant_price"]!=""? $data["variant_price"] : 0;
        $t->product_variant_barcode =  $data["variant_barcode"] != "" ? $data["variant_barcode"] : $t->generateBarcode();
        $t->product_variant_alert = $data["variant_alert"];
        $t->unit_id = $data["unit_id"];
        $t->lead_time_days = max(0, (int) ($data["lead_time_days"] ?? 0));
        if (array_key_exists('safety_stock', $data)) {
            $t->safety_stock = $data["safety_stock"] !== "" && $data["safety_stock"] !== null
                ? (int) $data["safety_stock"]
                : 0;
        }
        if (array_key_exists('safety_unit_id', $data)) {
            $t->safety_unit_id = ! empty($data["safety_unit_id"])
                ? (int) $data["safety_unit_id"]
                : null;
        }
        if (Schema::hasColumn($t->getTable(), 'retail_unit') && array_key_exists('retail_unit', $data)) {
            $t->retail_unit = ! empty($data["retail_unit"]) ? (int) $data["retail_unit"] : null;
        }
        $t->qty_per_pallet = $this->normalizeQtyPerPallet($data['qty_per_pallet'] ?? null);
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->product_variant_id;
    }

    private function normalizeQtyPerPallet($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $qty = (int) $value;

        return $qty > 0 ? $qty : null;
    }

    public function deleteProductVariant($data)
    {
        $t = self::find($data["product_variant_id"]);
        if (!$t) {
            throw new \Exception("Product variant with ID " . $data["product_variant_id"] . " not found.");
        }
        $t->status = 0; // soft delete
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
    }
    function generateBarcode()
    {
        do {
            // Generate angka acak sebanyak 12 digit
            $barcode = (string) random_int(100000000000, 999999999999);
        } while (self::where('product_variant_barcode', $barcode)->exists());
        return $barcode;
    }
}
