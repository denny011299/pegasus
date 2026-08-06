<?php

namespace App\Models;

use App\Support\UnitStockSorter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SuppliesStock extends Model
{
    protected $table = "supplies_stocks";
    protected $primaryKey = "ss_id";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'supplies_id',
        'unit_id',
        'warehouse_id',
        'ss_stock',
        'status',
        'created_by',
    ];

    protected static function booted()
    {
        static::addGlobalScope('active_warehouse', function (Builder $builder) {
            $warehouseId = static::resolveWarehouseId();
            if ($warehouseId) {
                $builder->where($builder->getModel()->getTable() . '.warehouse_id', $warehouseId);
            }
        });
    }

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
            "supplies_id" => null,
            "supplies_unit" => null,
            "warehouse_id" => null,
            "relations" => null,
        ], $data);

        $warehouseId = self::resolveWarehouseId($data["warehouse_id"] ?? null);

        $result = self::withoutGlobalScope('active_warehouse')
            ->where('status', '=', 1);

        if ($warehouseId) {
            $result->where('warehouse_id', $warehouseId);
        }
        if ($data["supplies_id"]) {
            $result->where('supplies_id', '=', $data["supplies_id"]);
        }
        $result->orderBy('created_at', 'asc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            $s = Supplies::find($value->supplies_id);
            $value->supplies_name = $s->supplies_name ?? '-';
            $u = Unit::find($value->unit_id);
            $value->unit_name = $u->unit_name ?? '-';
            $value->unit_short_name = $u->unit_short_name ?? '-';
        }

        if (! empty($data['relations'])) {
            $result = UnitStockSorter::sort($result, $data['relations'], 'su_id_1', 'su_id_2');
        }

        return $result;
    }

    function insertProductStock($data)
    {
        $t = new self();
        $t->supplies_id = $data["supplies_id"];
        $t->unit_id = $data["unit_id"];
        $t->warehouse_id = self::resolveWarehouseId($data["warehouse_id"] ?? null) ?: null;
        $t->ss_stock = $data["ss_stock"] ?? 0;
        $t->status = $data["status"] ?? 1;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->ss_id;
    }

    function updateProductStock($data)
    {
        $t = self::withoutGlobalScope('active_warehouse')->find($data["ss_id"]);
        $t->supplies_id = $data["supplies_id"];
        $t->unit_id = $data["unit_id"];
        if (isset($data["warehouse_id"])) {
            $t->warehouse_id = $data["warehouse_id"];
        }
        $t->ss_stock = $data["ss_stock"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->ss_id;
    }

    function deleteProductStock($data)
    {
        $t = self::withoutGlobalScope('active_warehouse')->find($data["ss_id"] ?? $data["ps_id"] ?? null);
        if (! $t) {
            return;
        }
        $t->status = 0;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
    }

    /**
     * Seed stok bahan 0 untuk semua supplies aktif di satu gudang.
     */
    public function generateStocksForWarehouse($warehouseId): int
    {
        $warehouseId = (int) $warehouseId;
        if ($warehouseId <= 0) {
            return 0;
        }

        $supplies = Supplies::where('status', 1)->get(['supplies_id', 'supplies_unit']);
        $now = now();
        $createdBy = Session::get('user')->staff_id ?? null;
        $inserted = 0;
        $chunk = [];

        foreach ($supplies as $item) {
            $units = json_decode($item->supplies_unit ?? '[]', true) ?: [];
            if (empty($units)) {
                continue;
            }

            foreach ($units as $unitId) {
                $exists = self::withoutGlobalScope('active_warehouse')
                    ->where('warehouse_id', $warehouseId)
                    ->where('supplies_id', $item->supplies_id)
                    ->where('unit_id', $unitId)
                    ->where('status', 1)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $chunk[] = [
                    'supplies_id' => $item->supplies_id,
                    'unit_id' => $unitId,
                    'warehouse_id' => $warehouseId,
                    'ss_stock' => 0,
                    'status' => 1,
                    'created_by' => $createdBy,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $inserted++;

                if (count($chunk) >= 500) {
                    DB::table('supplies_stocks')->insert($chunk);
                    $chunk = [];
                }
            }
        }

        if (! empty($chunk)) {
            DB::table('supplies_stocks')->insert($chunk);
        }

        return $inserted;
    }

    function syncStock($supplies_id)
    {
        $product = Supplies::find($supplies_id);
        $units = json_decode($product->supplies_unit ?? '[]', true) ?: [];

        $warehouseIds = Warehouse::query()->where('status', 1)->pluck('id');
        if ($warehouseIds->isEmpty()) {
            $fallback = self::resolveWarehouseId();
            $warehouseIds = $fallback ? collect([$fallback]) : collect();
        }

        foreach ($warehouseIds as $warehouseId) {
            self::withoutGlobalScope('active_warehouse')
                ->where('warehouse_id', $warehouseId)
                ->where('supplies_id', '=', $supplies_id)
                ->whereNotIn('unit_id', $units)
                ->update(["status" => 0]);

            foreach ($units as $unit) {
                $dt = self::withoutGlobalScope('active_warehouse')
                    ->where('warehouse_id', $warehouseId)
                    ->where('supplies_id', '=', $supplies_id)
                    ->where('unit_id', '=', $unit)
                    ->count();

                if ($dt == 0) {
                    self::insertProductStock([
                        "supplies_id" => $supplies_id,
                        "unit_id" => $unit,
                        "warehouse_id" => $warehouseId,
                        "ss_stock" => 0,
                    ]);
                } else {
                    $d = self::withoutGlobalScope('active_warehouse')
                        ->where('warehouse_id', $warehouseId)
                        ->where('supplies_id', '=', $supplies_id)
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

    function cekStockBerlebih($data)
    {
        $warehouseId = self::resolveWarehouseId($data["warehouse_id"] ?? null);
        $t = self::withoutGlobalScope('active_warehouse')
            ->where('ss_id', $data["ss_id"] ?? $data["ps_id"] ?? null)
            ->when($warehouseId, fn ($q) => $q->where('warehouse_id', $warehouseId))
            ->first();

        if (! $t) {
            return;
        }

        $p = Supplies::find($data["supplies_id"]);
        if ($p && $p->unit_id != $data["unit_id"]) {
            $ada = 1;
            while ($ada == 1) {
                $r = SuppliesRelation::where('su_id_2', '=', $data["unit_id"])
                    ->where('supplies_id', '=', $data["supplies_id"])->first();
                if (! $r) {
                    $ada = -1;
                    break;
                }
                if ($t->ss_stock >= $r->sr_value_2) {
                    $tambah = floor($t->ss_stock / $r->sr_value_2);
                    $t->ss_stock %= $r->sr_value_2;
                    $t->save();

                    $stBaru = self::withoutGlobalScope('active_warehouse')
                        ->where('supplies_id', '=', $data["supplies_id"])
                        ->where("unit_id", '=', $r->su_id_1)
                        ->where('warehouse_id', $t->warehouse_id)
                        ->first();
                    if ($stBaru) {
                        $stBaru->ss_stock += $tambah;
                        $stBaru->save();
                    }

                    $cek = SuppliesRelation::where('su_id_2', '=', $r->su_id_1)
                        ->where('supplies_id', '=', $data["supplies_id"]);
                    if ($cek->count() <= 0) {
                        $ada = -1;
                    }
                } else {
                    $ada = -1;
                }
            }
        }
    }
}
