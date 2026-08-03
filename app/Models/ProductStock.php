<?php

namespace App\Models;

use App\Support\UnitStockSorter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class ProductStock extends Model
{
    protected $table = "product_stocks";
    protected $primaryKey = "ps_id";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'unit_id',
        'warehouse_id',
        'ps_stock',
        'ps_safety_stock',
        'ps_alert_stock',
        'status',
        'created_by',
    ];

    protected static function booted()
    {
        // Query stok selalu terikat gudang aktif (session) / gudang utama.
        static::addGlobalScope('active_warehouse', function (Builder $builder) {
            $warehouseId = static::resolveWarehouseId();
            if ($warehouseId) {
                $builder->where($builder->getModel()->getTable() . '.warehouse_id', $warehouseId);
            }
        });
    }

    /**
     * Resolve warehouse id: explicit > session > main warehouse > first active.
     */
    public static function resolveWarehouseId($warehouseId = null)
    {
        if ($warehouseId !== null && $warehouseId !== '') {
            return (int) $warehouseId;
        }

        $sessionId = Session::get('active_warehouse_id');
        if ($sessionId !== null && $sessionId !== '') {
            return (int) $sessionId;
        }

        $mainId = Warehouse::query()
            ->where('warehouses.status', 1)
            ->whereHas('type', function ($q) {
                $q->where('status', 1)->where('is_main_warehouse', 1);
            })
            ->orderBy('warehouses.id')
            ->value('id');

        if ($mainId) {
            return (int) $mainId;
        }

        return (int) (Warehouse::query()->where('status', 1)->orderBy('id')->value('id') ?? 0);
    }

    function getProductStock($data = [])
    {
        $data = array_merge([
            "product_id" => null,
            "product_variant_id" => null,
            "unit_id" => null,
            "warehouse_id" => null,
            "relations" => null,
        ], $data);

        $warehouseId = self::resolveWarehouseId($data["warehouse_id"] ?? null);

        $result = self::withoutGlobalScope('active_warehouse')
            ->where('status', '=', 1);

        if ($warehouseId) {
            $result->where('warehouse_id', '=', $warehouseId);
        }
        if ($data["product_id"]) $result->where('product_id', '=', $data["product_id"]);
        if ($data["product_variant_id"]) $result->where('product_variant_id', '=', $data["product_variant_id"]);
        if ($data["unit_id"]) $result->where('unit_id', '=', $data["unit_id"]);
        $result->orderBy('created_at', 'asc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            $u = Unit::find($value->unit_id);
            $value->unit_name = $u->unit_name ?? '-';
            $value->unit_short_name = $u->unit_short_name ?? '-';
        }

        if (! empty($data['relations'])) {
            $result = UnitStockSorter::sort($result, $data['relations']);
        }

        return $result;
    }

    function insertProductStock($data)
    {
        $t = new self();
        $t->product_id = $data["product_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->unit_id = $data["unit_id"];
        $t->warehouse_id = self::resolveWarehouseId($data["warehouse_id"] ?? null) ?: null;
        $t->ps_stock = $data["ps_stock"] ?? 0;
        $t->ps_safety_stock = $data["ps_safety_stock"] ?? 0;
        $t->ps_alert_stock = $data["ps_alert_stock"] ?? 0;
        $t->status = $data["status"] ?? 1;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->ps_id;
    }

    function updateProductStock($data)
    {
        $t = self::withoutGlobalScope('active_warehouse')->find($data["ps_id"]);
        $t->product_id = $data["product_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->unit_id = $data["unit_id"];
        if (isset($data["warehouse_id"])) {
            $t->warehouse_id = $data["warehouse_id"];
        }
        $t->ps_stock = $data["ps_stock"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->ps_id;
    }

    function deleteProductStock($data)
    {
        $t = self::withoutGlobalScope('active_warehouse')->find($data["ps_id"]);
        $t->status = 0;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
    }

    /**
     * Seed stok 0 untuk semua produk/varian aktif di satu gudang.
     * Gudang utama: semua satuan di product_unit.
     * Gudang eceran: hanya retail_unit (skip jika belum di-set).
     */
    public function generateStocksForWarehouse($warehouseId): int
    {
        $warehouseId = (int) $warehouseId;
        if ($warehouseId <= 0) {
            return 0;
        }

        $isMain = Warehouse::query()
            ->whereKey($warehouseId)
            ->whereHas('type', fn ($q) => $q->where('is_main_warehouse', 1))
            ->exists();

        $hasRetailCol = Schema::hasColumn('product_variants', 'retail_unit');
        $variantCols = ['product_variant_id'];
        if ($hasRetailCol) {
            $variantCols[] = 'retail_unit';
        }

        $products = Product::where('status', 1)->get(['product_id', 'product_unit']);
        $now = now();
        $createdBy = Session::get('user')->staff_id ?? null;
        $inserted = 0;
        $chunk = [];

        foreach ($products as $product) {
            $productUnits = json_decode($product->product_unit ?? '[]', true) ?: [];
            if ($isMain && empty($productUnits)) {
                continue;
            }

            $variants = ProductVariant::where('product_id', $product->product_id)
                ->where('status', 1)
                ->get($variantCols);

            foreach ($variants as $variant) {
                if ($isMain) {
                    $units = $productUnits;
                } else {
                    $retailUnitId = $hasRetailCol ? (int) ($variant->retail_unit ?? 0) : 0;
                    if ($retailUnitId <= 0) {
                        continue; // belum ada satuan eceran → skip
                    }
                    $units = [$retailUnitId];
                }

                foreach ($units as $unitId) {
                    $unitId = (int) $unitId;
                    if ($unitId <= 0) {
                        continue;
                    }
                    $exists = self::withoutGlobalScope('active_warehouse')
                        ->where('warehouse_id', $warehouseId)
                        ->where('product_variant_id', $variant->product_variant_id)
                        ->where('unit_id', $unitId)
                        ->where('status', 1)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $chunk[] = [
                        'product_id' => $product->product_id,
                        'product_variant_id' => $variant->product_variant_id,
                        'unit_id' => $unitId,
                        'warehouse_id' => $warehouseId,
                        'ps_stock' => 0,
                        'ps_safety_stock' => 0,
                        'ps_alert_stock' => 0,
                        'status' => 1,
                        'created_by' => $createdBy,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $inserted++;

                    if (count($chunk) >= 500) {
                        DB::table('product_stocks')->insert($chunk);
                        $chunk = [];
                    }
                }
            }
        }

        if (! empty($chunk)) {
            DB::table('product_stocks')->insert($chunk);
        }

        return $inserted;
    }

    function syncStock($product_id)
    {
        $variant = ProductVariant::where("product_id", "=", $product_id)->where('status', '=', 1)->get();
        $product = Product::find($product_id);
        $productUnits = json_decode($product->product_unit ?? '[]', true) ?: [];
        $hasRetailCol = Schema::hasColumn('product_variants', 'retail_unit');

        $warehouses = Warehouse::query()
            ->where('status', 1)
            ->with(['type' => fn ($q) => $q->select('id', 'is_main_warehouse')])
            ->get(['id', 'warehouse_type_id']);
        if ($warehouses->isEmpty()) {
            $fallback = self::resolveWarehouseId();
            $warehouses = $fallback
                ? Warehouse::query()
                    ->whereKey($fallback)
                    ->with(['type' => fn ($q) => $q->select('id', 'is_main_warehouse')])
                    ->get(['id', 'warehouse_type_id'])
                : collect();
        }

        foreach ($warehouses as $warehouse) {
            $warehouseId = (int) $warehouse->id;
            $isMain = (int) ($warehouse->type->is_main_warehouse ?? 0) === 1;

            foreach ($variant as $value) {
                if ($isMain) {
                    $units = $productUnits;
                } else {
                    $retailUnitId = $hasRetailCol ? (int) ($value->retail_unit ?? 0) : 0;
                    $units = $retailUnitId > 0 ? [$retailUnitId] : [];
                }

                // Nonaktifkan satuan yang tidak relevan untuk tipe gudang ini
                $deactivate = self::withoutGlobalScope('active_warehouse')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', '=', $value["product_variant_id"]);
                if ($units !== []) {
                    $deactivate->whereNotIn('unit_id', $units);
                }
                $deactivate->update(["status" => 0]);

                foreach ($units as $unit) {
                    $unit = (int) $unit;
                    if ($unit <= 0) {
                        continue;
                    }
                    $dt = self::withoutGlobalScope('active_warehouse')
                        ->where('warehouse_id', $warehouseId)
                        ->where('product_variant_id', '=', $value["product_variant_id"])
                        ->where('unit_id', '=', $unit)
                        ->count();

                    if ($dt == 0) {
                        self::insertProductStock([
                            "product_id" => $product_id,
                            "product_variant_id" => $value->product_variant_id,
                            "unit_id" => $unit,
                            "warehouse_id" => $warehouseId,
                            "ps_stock" => 0,
                            "ps_safety_stock" => 0,
                        ]);
                    } else {
                        $d = self::withoutGlobalScope('active_warehouse')
                            ->where('warehouse_id', $warehouseId)
                            ->where('product_variant_id', '=', $value["product_variant_id"])
                            ->where('unit_id', '=', $unit)
                            ->get();
                        foreach ($d as $v) {
                            if ($v->status == 0) {
                                $v->status = 1;
                                $v->save();
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Set safety stock HANYA untuk satu gudang (master produk mengikuti gudang aktif).
     * $variants: [[product_variant_id, safety_stock, safety_unit_id], ...]
     */
    public function applySafetyStockForWarehouse($productId, $warehouseId, array $variants): void
    {
        $warehouseId = (int) $warehouseId;
        $productId = (int) $productId;
        if ($warehouseId <= 0 || $productId <= 0) {
            return;
        }

        foreach ($variants as $row) {
            $variantId = (int) ($row['product_variant_id'] ?? 0);
            if ($variantId <= 0) {
                continue;
            }

            $safetyQty = isset($row['safety_stock']) && $row['safety_stock'] !== ''
                ? (int) $row['safety_stock']
                : 0;
            $safetyUnit = ! empty($row['safety_unit_id']) ? (int) $row['safety_unit_id'] : 0;

            self::withoutGlobalScope('active_warehouse')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->where('status', 1)
                ->update(['ps_safety_stock' => 0]);

            if ($safetyUnit > 0 && $safetyQty >= 0) {
                self::withoutGlobalScope('active_warehouse')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->where('product_variant_id', $variantId)
                    ->where('unit_id', $safetyUnit)
                    ->where('status', 1)
                    ->update(['ps_safety_stock' => $safetyQty]);
            }
        }
    }

    /**
     * Ambil safety stock per variant untuk satu gudang (untuk form master produk).
     * @return array<int, array{safety_stock:int, safety_unit_id:int|null}>
     */
    public function getSafetyStockMapForWarehouse($warehouseId, array $variantIds): array
    {
        $warehouseId = (int) $warehouseId;
        $variantIds = array_values(array_filter(array_map('intval', $variantIds)));
        if ($warehouseId <= 0 || $variantIds === []) {
            return [];
        }

        $rows = self::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_variant_id', $variantIds)
            ->where('status', 1)
            ->where('ps_safety_stock', '>', 0)
            ->orderByDesc('ps_safety_stock')
            ->get(['product_variant_id', 'unit_id', 'ps_safety_stock']);

        $map = [];
        foreach ($rows as $row) {
            $vid = (int) $row->product_variant_id;
            if (isset($map[$vid])) {
                continue;
            }
            $map[$vid] = [
                'safety_stock' => (int) $row->ps_safety_stock,
                'safety_unit_id' => (int) $row->unit_id,
            ];
        }

        return $map;
    }

    /**
     * Set peringatan stok HANYA untuk satu gudang (master produk mengikuti gudang aktif).
     * $variants: [[product_variant_id, alert_stock, alert_unit_id], ...]
     */
    public function applyAlertStockForWarehouse($productId, $warehouseId, array $variants): void
    {
        if (! Schema::hasColumn($this->getTable(), 'ps_alert_stock')) {
            return;
        }

        $warehouseId = (int) $warehouseId;
        $productId = (int) $productId;
        if ($warehouseId <= 0 || $productId <= 0) {
            return;
        }

        foreach ($variants as $row) {
            $variantId = (int) ($row['product_variant_id'] ?? 0);
            if ($variantId <= 0) {
                continue;
            }

            $alertQty = isset($row['alert_stock']) && $row['alert_stock'] !== ''
                ? (int) $row['alert_stock']
                : 0;
            $alertUnit = ! empty($row['alert_unit_id']) ? (int) $row['alert_unit_id'] : 0;

            self::withoutGlobalScope('active_warehouse')
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('product_variant_id', $variantId)
                ->where('status', 1)
                ->update(['ps_alert_stock' => 0]);

            if ($alertUnit > 0 && $alertQty >= 0) {
                self::withoutGlobalScope('active_warehouse')
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->where('product_variant_id', $variantId)
                    ->where('unit_id', $alertUnit)
                    ->where('status', 1)
                    ->update(['ps_alert_stock' => $alertQty]);
            }
        }
    }

    /**
     * Ambil peringatan stok per variant untuk satu gudang (untuk form master produk).
     * @return array<int, array{alert_stock:int, alert_unit_id:int|null}>
     */
    public function getAlertStockMapForWarehouse($warehouseId, array $variantIds): array
    {
        if (! Schema::hasColumn($this->getTable(), 'ps_alert_stock')) {
            return [];
        }

        $warehouseId = (int) $warehouseId;
        $variantIds = array_values(array_filter(array_map('intval', $variantIds)));
        if ($warehouseId <= 0 || $variantIds === []) {
            return [];
        }

        $rows = self::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', $warehouseId)
            ->whereIn('product_variant_id', $variantIds)
            ->where('status', 1)
            ->where('ps_alert_stock', '>', 0)
            ->orderByDesc('ps_alert_stock')
            ->get(['product_variant_id', 'unit_id', 'ps_alert_stock']);

        $map = [];
        foreach ($rows as $row) {
            $vid = (int) $row->product_variant_id;
            if (isset($map[$vid])) {
                continue;
            }
            $map[$vid] = [
                'alert_stock' => (int) $row->ps_alert_stock,
                'alert_unit_id' => (int) $row->unit_id,
            ];
        }

        return $map;
    }

    function cekStockBerlebih($data)
    {
        $warehouseId = self::resolveWarehouseId($data["warehouse_id"] ?? null);
        $t = self::withoutGlobalScope('active_warehouse')
            ->where('ps_id', $data["ps_id"])
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->first();

        if (! $t) {
            return;
        }

        $p = Product::find($data["product_id"]);
        if ($p->unit_id != $data["unit_id"]) {
            $ada = 1;
            while ($ada == 1) {
                $r = ProductRelation::where('pr_unit_id_2', '=', $data["unit_id"])
                    ->where('product_variant_id', '=', $data["product_variant_id"])->first();
                if ($t->ps_stock >= $r->pr_unit_value_2) {
                    $tambah = floor($t->ps_stock / $r->pr_unit_value_2);
                    $t->ps_stock %= $r->pr_unit_value_2;

                    $t->save();
                    $stBaru = self::withoutGlobalScope('active_warehouse')
                        ->where('product_variant_id', '=', $data["product_variant_id"])
                        ->where("unit_id", '=', $r->pr_unit_id_1)
                        ->where('warehouse_id', $t->warehouse_id)
                        ->first();
                    if ($stBaru) {
                        $stBaru->ps_stock += $tambah;
                        $stBaru->save();
                    }

                    $cek = ProductRelation::where('pr_unit_id_2', '=', $r->pr_unit_id_1)
                        ->where('product_variant_id', '=', $data["product_variant_id"]);
                    if ($cek->count() <= 0) {
                        $ada = -1;
                    }
                } else  $ada = -1;
            }
        }
    }
}
