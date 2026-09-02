<?php

namespace App\Models;

use App\Support\BatchLookup;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class Bom extends Model
{
    protected $table = "boms";
    protected $primaryKey = "bom_id";
    public $timestamps = true;
    public $incrementing = true;

    function getBom($data = [])
    {

        $data = array_merge([
            "bom_id" => null,
            "search" => null,
            "product_id" => null,
            "supplies_id" => null,
            "with_details" => false,
            "active_products_only" => false,
        ], $data);
        $data['with_details'] = filter_var($data['with_details'], FILTER_VALIDATE_BOOLEAN);
        $data['active_products_only'] = filter_var($data['active_products_only'], FILTER_VALIDATE_BOOLEAN);
        if ($data['bom_id']) {
            $data['with_details'] = true;
        }

        $result = Bom::where('boms.status', '=', 1)
          ->join('product_variants', 'product_variants.product_variant_id', '=', 'boms.product_id')
            ->join('products', 'products.product_id', '=', 'product_variants.product_id')
            ->select('boms.*');

        if ($data["product_id"]) $result->where('boms.product_id', '=', $data["product_id"]);
        if ($data["bom_id"]) $result->where('boms.bom_id', '=', $data["bom_id"]);
        if ($data['active_products_only']) {
            $result->where('product_variants.status', '=', 1)
                ->where('products.status', '=', 1);
        }
        if ($data["supplies_id"]) {
            $result->whereIn('boms.bom_id', function ($query) use ($data) {
                $query->select('bom_id')
                    ->from('bom_details')
                    ->where('supplies_id', '=', $data["supplies_id"])
                    ->where('status', '=', 1);
            });
        }

        if ($data['search']) {
            $s = $data['search'];
             $result->where(function ($q) use ($s) {
                $q->whereRaw("CONCAT(products.product_name, ' ', product_variants.product_variant_name) LIKE ?", ["%{$s}%"])
                ->orWhere("product_variants.product_variant_sku", "LIKE", "%{$s}%");
            });
        }

        $result->orderBy('created_at', 'asc');

        $result = $result->get();

        if ($result->isEmpty()) {
            return $result;
        }

        $variantIds = $result->pluck('product_id')->filter()->unique()->values()->all();
        $variants = ProductVariant::whereIn('product_variant_id', $variantIds)->get()->keyBy('product_variant_id');
        $products = Product::whereIn(
            'product_id',
            $variants->pluck('product_id')->filter()->unique()->values()->all()
        )->get()->keyBy('product_id');

        $unitIdSet = [];
        foreach ($result as $row) {
            if ($row->unit_id) {
                $unitIdSet[(int) $row->unit_id] = true;
            }
        }
        foreach ($products as $product) {
            foreach ((array) (json_decode($product->product_unit, true) ?: []) as $unitId) {
                $unitIdSet[(int) $unitId] = true;
            }
        }
        $unitsMap = $unitIdSet !== []
            ? Unit::whereIn('unit_id', array_keys($unitIdSet))->get()->keyBy('unit_id')
            : collect();

        $staffNames = BatchLookup::staffNames($result->pluck('created_by'));

        $detailsByBom = $data['with_details']
            ? (new BomDetail())->getDetailBulk($result->pluck('bom_id')->all(), true)
            : (new BomDetail())->getDetailBulk($result->pluck('bom_id')->all(), false);

        foreach ($result as $value) {
            $v = $variants->get($value->product_id);
            $u = $v ? $products->get($v->product_id) : null;
            $value->product_sku = $v ? $v->product_variant_sku : '-';
            $value->product_variant_id = $v ? $v->product_variant_id : null;
            $value->default_unit = $u ? $u->unit_id : null;
            $value->retail_unit = $v && $v->retail_unit ? (int) $v->retail_unit : null;
            $defaultUnit = $u ? $unitsMap->get($u->unit_id) : null;
            $value->default_unit_name = $defaultUnit ? $defaultUnit->unit_short_name : '-';
            $value->product_name = $v && $u
                ? trim($u->product_name . ' ' . $v->product_variant_name)
                : '-';
            $value->product_variant_sku = $v ? $v->product_variant_sku : '-';
            $value->qty_per_pallet = $v && (int) ($v->qty_per_pallet ?? 0) > 0
                ? (int) $v->qty_per_pallet
                : null;
            $bomUnit = $unitsMap->get($value->unit_id);
            $value->unit_name = $bomUnit ? ($bomUnit->unit_name ?? $bomUnit->unit_short_name ?? '-') : '-';
            $unitIds = $u ? (json_decode($u->product_unit, true) ?: []) : [];
            $value->pr_unit = collect($unitIds)
                ->map(fn ($id) => $unitsMap->get((int) $id))
                ->filter()
                ->values();
            $value->relasi = (new ProductRelation())->getProductRelation(['product_variant_id' => $value->product_id]);
            $details = ($detailsByBom->get($value->bom_id) ?? collect())->values();
            $value->details = $details;
            $value->items = $details;
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
            // Status produk & varian — untuk deteksi produk tidak aktif di frontend
            $value->product_status = $u ? (int) $u->status : 0;
            $value->product_variant_status = $v ? (int) $v->status : 0;
        }


        return $result;
    }

    /**
     * Server-side DataTables untuk list Resep Bahan Mentah.
     *
     * @param  array<string, mixed>  $data
     * @return array{draw:int, recordsTotal:int, recordsFiltered:int, data:array<int, array<string, mixed>>}
     */
    public function getBomDataTable(array $data = []): array
    {
        $draw = (int) ($data['draw'] ?? 1);
        $start = max(0, (int) ($data['start'] ?? 0));
        $length = (int) ($data['length'] ?? 10);
        if ($length < 1) {
            $length = 10;
        }
        if ($length > 100) {
            $length = 100;
        }

        $search = trim((string) data_get($data, 'search.value', $data['search'] ?? ''));
        $orderColIdx = (int) data_get($data, 'order.0.column', 0);
        $orderDir = strtolower((string) data_get($data, 'order.0.dir', 'asc')) === 'desc'
            ? 'desc'
            : 'asc';
        $productId = $data['product_id'] ?? null;
        $suppliesId = $data['supplies_id'] ?? null;

        $columns = [
            0 => 'product_variants.product_variant_sku',
            1 => 'products.product_name',
            2 => 'products.product_name', // material list (derived)
            3 => 'boms.bom_qty',
            4 => 'st.staff_name',
            5 => 'boms.bom_id', // action
        ];
        $orderCol = $columns[$orderColIdx] ?? 'products.product_name';

        $base = self::query()
            ->from('boms')
            ->join('product_variants', 'product_variants.product_variant_id', '=', 'boms.product_id')
            ->join('products', 'products.product_id', '=', 'product_variants.product_id')
            ->leftJoin('staffs as st', 'st.staff_id', '=', 'boms.created_by')
            ->leftJoin('units as u', 'u.unit_id', '=', 'boms.unit_id')
            ->where('boms.status', 1);

        $recordsTotal = (clone $base)->count('boms.bom_id');

        if ($productId) {
            $base->where('boms.product_id', '=', $productId);
        }
        if ($suppliesId) {
            $base->whereIn('boms.bom_id', function ($query) use ($suppliesId) {
                $query->select('bom_id')
                    ->from('bom_details')
                    ->where('supplies_id', '=', $suppliesId)
                    ->where('status', '=', 1);
            });
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $base->where(function ($q) use ($like) {
                $q->whereRaw(
                    "CONCAT(products.product_name, ' ', product_variants.product_variant_name) LIKE ?",
                    [$like]
                )
                    ->orWhere('product_variants.product_variant_sku', 'LIKE', $like)
                    ->orWhere('st.staff_name', 'LIKE', $like)
                    ->orWhereExists(function ($sq) use ($like) {
                        $sq->selectRaw('1')
                            ->from('bom_details as bd')
                            ->join('supplies as s', 's.supplies_id', '=', 'bd.supplies_id')
                            ->whereColumn('bd.bom_id', 'boms.bom_id')
                            ->where('bd.status', 1)
                            ->where('s.supplies_name', 'LIKE', $like);
                    });
            });
        }

        $recordsFiltered = (clone $base)->count('boms.bom_id');

        $rows = $base
            ->select([
                'boms.bom_id',
                'boms.product_id',
                'boms.bom_qty',
                'boms.unit_id',
                'boms.created_by',
                'product_variants.product_variant_sku',
                'product_variants.product_variant_name',
                'products.product_name as pr_name',
                'u.unit_name',
                'u.unit_short_name',
                'st.staff_name as created_by_name',
            ])
            ->orderBy($orderCol, $orderDir)
            ->orderBy('boms.bom_id', 'asc')
            ->skip($start)
            ->take($length)
            ->get();

        $detailsByBom = $rows->isEmpty()
            ? collect()
            : (new BomDetail())->getDetailBulk($rows->pluck('bom_id')->all(), false);

        $dataRows = [];
        foreach ($rows as $row) {
            $productName = trim(($row->pr_name ?? '') . ' ' . ($row->product_variant_name ?? '')) ?: '-';
            $unitName = $row->unit_name ?? $row->unit_short_name ?? '-';
            $details = ($detailsByBom->get($row->bom_id) ?? collect())->values();
            $details = $details->sortBy(function ($d) {
                return mb_strtolower((string) ($d->supplies_name ?? ''));
            })->values();
            $supplies = $details->pluck('supplies_name')->filter()->implode(', ');

            $dataRows[] = [
                'bom_id' => (int) $row->bom_id,
                'product_id' => (int) $row->product_id,
                'product_sku' => $row->product_variant_sku ?: '-',
                'product_name' => $productName,
                'supplies' => $supplies !== '' ? $supplies : '-',
                'bom_qty' => $row->bom_qty,
                'unit_name' => $unitName,
                'unit_text' => trim(($row->bom_qty ?? '') . ' ' . $unitName),
                'created_by_name' => $row->created_by_name ?: '-',
            ];
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $dataRows,
        ];
    }

    /**
     * Query ringan untuk Select2 autocomplete BOM — aktif saja, paginated, tanpa detail/relasi.
     *
     * @param  array<string, mixed>  $data
     * @return array{data: \Illuminate\Support\Collection, more: bool}
     */
    public function searchForAutocomplete(array $data = [], int $limit = 30): array
    {
        $data = array_merge([
            'search' => null,
            'page' => 1,
        ], $data);

        $limit = max(1, min((int) $limit, 50));
        $page = max(1, (int) ($data['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $query = self::query()
            ->from('boms')
            ->join('product_variants', 'product_variants.product_variant_id', '=', 'boms.product_id')
            ->join('products', 'products.product_id', '=', 'product_variants.product_id')
            ->where('boms.status', 1)
            ->where('product_variants.status', 1)
            ->where('products.status', 1);

        $search = trim((string) ($data['search'] ?? ''));
        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw(
                    "CONCAT(products.product_name, ' ', product_variants.product_variant_name) LIKE ?",
                    [$like]
                )->orWhere('product_variants.product_variant_sku', 'LIKE', $like);
            });
        }

        $select = [
            'boms.bom_id',
            'boms.product_id',
            'boms.bom_qty',
            'boms.unit_id',
            'product_variants.product_variant_id',
            'product_variants.product_variant_sku',
            'product_variants.product_variant_name',
            'product_variants.status as product_variant_status',
            'products.product_name as pr_name',
            'products.product_unit',
            'products.unit_id as product_default_unit',
            'products.status as product_status',
        ];
        if (Schema::hasColumn('product_variants', 'retail_unit')) {
            $select[] = 'product_variants.retail_unit';
        }
        if (Schema::hasColumn('product_variants', 'qty_per_pallet')) {
            $select[] = 'product_variants.qty_per_pallet';
        }

        $rows = $query
            ->select($select)
            ->orderBy('products.product_name')
            ->orderBy('boms.bom_id')
            ->offset($offset)
            ->limit($limit + 1)
            ->get();

        $more = $rows->count() > $limit;
        if ($more) {
            $rows = $rows->take($limit)->values();
        } else {
            $rows = $rows->values();
        }

        if ($rows->isEmpty()) {
            return ['data' => $rows, 'more' => false];
        }

        $unitIdSet = [];
        foreach ($rows as $row) {
            if ($row->unit_id) {
                $unitIdSet[(int) $row->unit_id] = true;
            }
            if ($row->product_default_unit) {
                $unitIdSet[(int) $row->product_default_unit] = true;
            }
            $retailUnitId = isset($row->retail_unit) ? (int) $row->retail_unit : 0;
            if ($retailUnitId > 0) {
                $unitIdSet[$retailUnitId] = true;
            }
            foreach ((array) (json_decode($row->product_unit, true) ?: []) as $unitId) {
                $uid = is_array($unitId)
                    ? (int) ($unitId['unit_id'] ?? $unitId['id'] ?? 0)
                    : (int) $unitId;
                if ($uid > 0) {
                    $unitIdSet[$uid] = true;
                }
            }
        }
        $unitsMap = $unitIdSet !== []
            ? Unit::whereIn('unit_id', array_keys($unitIdSet))->get()->keyBy('unit_id')
            : collect();

        foreach ($rows as $row) {
            $row->product_sku = $row->product_variant_sku ?: '-';
            $row->default_unit = $row->product_default_unit ? (int) $row->product_default_unit : null;
            $row->retail_unit = isset($row->retail_unit) && (int) $row->retail_unit > 0
                ? (int) $row->retail_unit
                : null;
            $defaultUnit = $row->default_unit ? $unitsMap->get($row->default_unit) : null;
            $row->default_unit_name = $defaultUnit
                ? ($defaultUnit->unit_short_name ?? $defaultUnit->unit_name ?? '-')
                : '-';
            $row->product_name = trim(
                ($row->pr_name ?? '') . ' ' . ($row->product_variant_name ?? '')
            ) ?: '-';
            $row->qty_per_pallet = isset($row->qty_per_pallet) && (int) $row->qty_per_pallet > 0
                ? (int) $row->qty_per_pallet
                : null;
            $bomUnit = $unitsMap->get((int) $row->unit_id);
            $row->unit_name = $bomUnit
                ? ($bomUnit->unit_name ?? $bomUnit->unit_short_name ?? '-')
                : '-';
            // product_unit bisa kosong/inkonsisten — selalu sertakan default + satuan BOM
            // supaya FE modal produksi punya opsi + nilai terpilih.
            $unitIds = [];
            foreach ((array) (json_decode($row->product_unit, true) ?: []) as $unitId) {
                $uid = is_array($unitId)
                    ? (int) ($unitId['unit_id'] ?? $unitId['id'] ?? 0)
                    : (int) $unitId;
                if ($uid > 0) {
                    $unitIds[$uid] = true;
                }
            }
            foreach ([$row->default_unit, $row->unit_id, $row->retail_unit] as $extraId) {
                $uid = (int) ($extraId ?? 0);
                if ($uid > 0) {
                    $unitIds[$uid] = true;
                }
            }
            $row->pr_unit = collect(array_keys($unitIds))
                ->map(function ($id) use ($unitsMap) {
                    $u = $unitsMap->get((int) $id);
                    if (! $u) {
                        return null;
                    }

                    return [
                        'unit_id' => (int) $u->unit_id,
                        'unit_name' => $u->unit_name ?? $u->unit_short_name ?? '-',
                        'unit_short_name' => $u->unit_short_name ?? $u->unit_name ?? '-',
                    ];
                })
                ->filter()
                ->values();
            $row->product_status = (int) ($row->product_status ?? 0);
            $row->product_variant_status = (int) ($row->product_variant_status ?? 0);
            unset($row->product_unit, $row->product_default_unit, $row->pr_name);
        }

        return ['data' => $rows, 'more' => $more];
    }

    function insertBom($data)
    {
        $t = new Bom();
        $t->product_id = $data["product_id"];
        $t->bom_qty = $data["bom_qty"];
        $t->unit_id = $data["unit_id"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->bom_id;
    }

    function updateBom($data)
    {
        $t = Bom::find($data["bom_id"]);
        $t->product_id = $data["product_id"];
        $t->bom_qty = $data["bom_qty"];
        $t->unit_id = $data["unit_id"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->bom_id;
    }

    function deleteBom($data)
    {
        $t = Bom::find($data["bom_id"]);
        $t->status = 0;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
    }

    /**
     * Soft-delete resep (header + detail) untuk product_variant_id tertentu.
     * Catatan: kolom boms.product_id menyimpan product_variant_id.
     *
     * @param  array<int|string>  $variantIds
     */
    public function softDeleteByVariantIds(array $variantIds): void
    {
        $variantIds = array_values(array_unique(array_filter(array_map('intval', $variantIds))));
        if ($variantIds === []) {
            return;
        }

        $staffId = Session::get('user') ? Session::get('user')->staff_id : null;
        $bomIds = self::where('status', 1)
            ->whereIn('product_id', $variantIds)
            ->pluck('bom_id');
        if ($bomIds->isEmpty()) {
            return;
        }

        self::whereIn('bom_id', $bomIds)->update([
            'status' => 0,
            'created_by' => $staffId,
        ]);
        BomDetail::whereIn('bom_id', $bomIds)->where('status', 1)->update([
            'status' => 0,
            'created_by' => $staffId,
        ]);
    }

    /**
     * QC21: soft-delete seluruh resep yang memakai bahan (supplies_id) di bom_details.
     *
     * @param  array<int|string>  $suppliesIds
     */
    public function softDeleteBySuppliesIds(array $suppliesIds): void
    {
        $suppliesIds = array_values(array_unique(array_filter(array_map('intval', $suppliesIds))));
        if ($suppliesIds === []) {
            return;
        }

        $bomIds = BomDetail::where('status', 1)
            ->whereIn('supplies_id', $suppliesIds)
            ->pluck('bom_id')
            ->unique()
            ->values()
            ->all();
        if ($bomIds === []) {
            return;
        }

        $staffId = Session::get('user') ? Session::get('user')->staff_id : null;
        self::whereIn('bom_id', $bomIds)->where('status', 1)->update([
            'status' => 0,
            'created_by' => $staffId,
        ]);
        BomDetail::whereIn('bom_id', $bomIds)->where('status', 1)->update([
            'status' => 0,
            'created_by' => $staffId,
        ]);
    }
}

