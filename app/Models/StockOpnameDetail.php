<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockOpnameDetail extends Model
{
    protected $table = "stock_opname_details";
    protected $primaryKey = "stod_id";
    public $timestamps = true;
    public $incrementing = true;
    /**
     * Get detail list
     */
    public static function getDetail($data = [])
    {
        $data = array_merge([
            'sto_id' => null,
            'product_id' => null,
            'product_variant_id' => null,
        ], $data);

        $result = self::where('status', 1);

        if ($data['sto_id']) $result->where('sto_id', $data['sto_id']);
        if ($data['product_id']) $result->where('product_id', $data['product_id']);
        if ($data['product_variant_id']) $result->where('product_variant_id', $data['product_variant_id']);

        $result->orderBy('created_at', 'asc');
        $result = $result->get();
        if ($result->isEmpty()) {
            return $result;
        }

        // Pin ->stock ke gudang dokumen ini (bukan gudang aktif sesi yang generate PDF-nya) --
        // lihat catatan di ProductVariant::getProductVariantBulk(). Hanya bisa diresolve kalau
        // dipanggil untuk satu dokumen spesifik (filter sto_id diberikan).
        $warehouseId = null;
        if ($data['sto_id']) {
            $sto = StockOpname::find($data['sto_id']);
            $warehouseId = $sto && $sto->warehouse_id ? (int) $sto->warehouse_id : null;
        }

        // Enrich sekali via bulk (hindari N+1 getProductVariant per baris).
        $variantMap = (new ProductVariant())->getProductVariantBulk(
            $result->pluck('product_variant_id')->unique()->filter()->values()->all(),
            $warehouseId
        );

        foreach ($result as $key => $value) {
            $pv = $variantMap->get($value->product_variant_id);
            if (! $pv) {
                unset($result[$key]);
                continue;
            }

            $temp = clone $pv;
            $temp->stod_system = $value->stod_system;
            $temp->stod_real = $value->stod_real;
            $temp->stod_selisih = $value->stod_selisih;
            $temp->stod_notes = $value->stod_notes;
            $temp->stod_touched = $value->stod_touched;
            $temp->stod_id = $value->stod_id;
            $temp->sto_id = $value->sto_id;
            $result[$key] = $temp;
        }

        return $result->values();
    }

    /**
     * @param  array<int>  $stoIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, mixed>>
     */
    public static function getDetailBulk(array $stoIds)
    {
        if ($stoIds === []) {
            return collect();
        }

        $details = self::where('status', 1)
            ->whereIn('sto_id', $stoIds)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($details->isEmpty()) {
            return collect();
        }

        $stoWarehouses = StockOpname::whereIn('sto_id', $stoIds)
            ->pluck('warehouse_id', 'sto_id')
            ->toArray();

        $mapped = collect();
        foreach ($details->groupBy('sto_id') as $stoId => $stoDetails) {
            $warehouseId = (int) ($stoWarehouses[$stoId] ?? 0);
            $variantIds = $stoDetails->pluck('product_variant_id')->unique()->filter()->values()->all();
            $variantMap = (new ProductVariant())->getProductVariantBulk(
                $variantIds,
                $warehouseId > 0 ? $warehouseId : null
            );

            foreach ($stoDetails as $value) {
                $pv = $variantMap->get($value->product_variant_id);
                if (! $pv) {
                    continue;
                }

                $temp = clone $pv;
                $temp->stod_system = $value->stod_system;
                $temp->stod_real = $value->stod_real;
                $temp->stod_selisih = $value->stod_selisih;
                $temp->stod_notes = $value->stod_notes;
                $temp->stod_touched = $value->stod_touched;
                $temp->stod_id = $value->stod_id;
                $temp->sto_id = $value->sto_id;

                $mapped->push($temp);
            }
        }

        return $mapped->groupBy('sto_id')->map(function ($group) {
            return $group->sortBy('stod_id')->values();
        });
    }

    /**
     * Beberapa dokumen lama/restore DB kehilangan baris stock_opname_details padahal
     * header + log_stocks approval masih ada. Rekonstruksi dari log agar halaman detail/PDF
     * tidak kosong (hanya untuk dokumen yang sudah disetujui).
     */
    public static function rebuildMissingFromLogs(int $stoId): bool
    {
        if ($stoId <= 0) {
            return false;
        }

        if (self::where('sto_id', $stoId)->where('status', 1)->exists()) {
            return false;
        }

        $sto = StockOpname::find($stoId);
        if (! $sto || (int) $sto->status !== 2 || empty($sto->sto_code)) {
            return false;
        }

        $logs = LogStock::where('log_kode', $sto->sto_code)
            ->where('log_type', 1)
            ->where('status', 1)
            ->orderBy('log_id')
            ->get();

        if ($logs->isEmpty()) {
            return false;
        }

        $warehouseId = (int) ($sto->warehouse_id ?: $logs->first()->warehouse_id ?: 0);
        $unitIds = $logs->pluck('unit_id')->unique()->filter()->map(fn ($id) => (int) $id)->all();
        $unitNames = $unitIds !== []
            ? Unit::whereIn('unit_id', $unitIds)->pluck('unit_short_name', 'unit_id')
            : collect();

        $buildQtyString = function (array $qtyByUnitId) use ($unitNames): string {
            $parts = [];
            foreach ($qtyByUnitId as $unitId => $qty) {
                $name = $unitNames[$unitId] ?? ('unit#' . $unitId);
                $parts[] = (int) $qty . ' ' . $name;
            }

            return implode(', ', $parts);
        };

        DB::beginTransaction();
        try {
            foreach ($logs->groupBy('log_item_id') as $variantId => $variantLogs) {
                $variantId = (int) $variantId;
                $pv = ProductVariant::find($variantId);
                if (! $pv) {
                    continue;
                }

                $deltaByUnit = [];
                foreach ($variantLogs as $log) {
                    $uid = (int) $log->unit_id;
                    $sign = (int) $log->log_category === 1 ? 1 : -1;
                    $deltaByUnit[$uid] = ($deltaByUnit[$uid] ?? 0) + ($sign * (int) $log->log_jumlah);
                }

                $systemByUnit = [];
                $realByUnit = [];
                foreach ($deltaByUnit as $unitId => $delta) {
                    $currentQuery = ProductStock::withoutGlobalScope('active_warehouse')
                        ->where('status', 1)
                        ->where('product_variant_id', $variantId)
                        ->where('unit_id', $unitId);
                    if ($warehouseId > 0) {
                        $currentQuery->where('warehouse_id', $warehouseId);
                    }
                    $current = (int) ($currentQuery->value('ps_stock') ?? 0);
                    $realByUnit[$unitId] = $current;
                    $systemByUnit[$unitId] = $current - $delta;
                }

                $selisihByUnit = [];
                foreach ($systemByUnit as $unitId => $systemQty) {
                    $selisihByUnit[$unitId] = ($realByUnit[$unitId] ?? 0) - $systemQty;
                }

                self::insertDetail([
                    'sto_id' => $stoId,
                    'product_id' => $pv->product_id,
                    'product_variant_id' => $variantId,
                    'stod_system' => $buildQtyString($systemByUnit),
                    'stod_real' => $buildQtyString($realByUnit),
                    'stod_selisih' => $buildQtyString($selisihByUnit),
                    'stod_notes' => null,
                    'stod_touched' => 1,
                ]);
            }

            DB::commit();

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Insert detail
     */
    public static function insertDetail($data)
    {
        
        $t = new self();
        $t->sto_id = $data['sto_id'];
        $t->product_id = $data['product_id'];
        $t->product_variant_id = $data['product_variant_id'];
        $t->stod_system = $data['stod_system'] ?? null;
        $t->stod_real = $data['stod_real'] ?? null;
        $t->stod_selisih = $data['stod_selisih'] ?? null;
        $t->stod_notes = $data['stod_notes'] ?? null;
        $t->stod_touched = !empty($data['stod_touched']);
        $t->save();

        return $t->stod_id;
    }

    /**
     * Update detail
     */
    public static function updateDetail($data)
    {
        $t = self::find($data['stod_id']);
        if (!$t) return null;

        $t->sto_id = $data['sto_id'];
        $t->product_id = $data['product_id'];
        $t->product_variant_id = $data['product_variant_id'];
        $t->stod_system = $data['stod_system'] ?? null;
        $t->stod_real = $data['stod_real'] ?? null;
        $t->stod_selisih = $data['stod_selisih'] ?? null;
        $t->stod_notes = $data['stod_notes'] ?? null;
        $t->stod_touched = !empty($data['stod_touched']);
        $t->save();

        return $t->stod_id;
    }

    /**
     * Soft delete detail (status = 0)
     */
    public static function deleteDetail($data)
    {
        $t = self::find($data['stod_id']);
        if ($t) {
            $t->status = 0;
            $t->save();
        }
    }

}
