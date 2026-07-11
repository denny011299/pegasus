<?php

namespace App\Models;

use App\Support\UnitStockSorter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAlertSupplies extends Model
{
    use HasFactory;
    protected $table = "stock_alerts";
    protected $primaryKey = "stal_id";
    public $timestamps = true;
    public $incrementing = true;

    function getStockAlertSupplies($data = []){
        $data = array_merge([
            "mode"=>1,//1=low stock, 2= out of stock
        ], $data);

        $result = Supplies::where('supplies.status', '=', 1)->orderBy('created_at', 'asc')->get();
        if ($result->isEmpty()) {
            return $result;
        }

        $suppliesIds = $result->pluck('supplies_id')->map(fn ($id) => (int) $id)->all();
        $decodeUnit = static fn ($val) => is_array($val) ? $val : (json_decode($val ?? '[]', true) ?: []);

        $unitIdSet = [];
        foreach ($result as $supply) {
            foreach ($decodeUnit($supply->getAttributes()['supplies_unit'] ?? null) as $unitId) {
                $unitIdSet[(int) $unitId] = true;
            }
            if ($supply->supplies_default_unit) {
                $unitIdSet[(int) $supply->supplies_default_unit] = true;
            }
        }

        $relations = SuppliesRelation::where('status', 1)
            ->whereIn('supplies_id', $suppliesIds)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($relations as $rel) {
            $unitIdSet[(int) $rel->su_id_1] = true;
            $unitIdSet[(int) $rel->su_id_2] = true;
        }

        $unitsMap = $unitIdSet !== []
            ? Unit::whereIn('unit_id', array_keys($unitIdSet))->get()->keyBy('unit_id')
            : collect();

        foreach ($relations as $rel) {
            $u1 = $unitsMap->get((int) $rel->su_id_1);
            $u2 = $unitsMap->get((int) $rel->su_id_2);
            if ($u1) {
                $rel->pr_unit_id_1 = $u1->unit_id;
                $rel->pr_unit_name_1 = $u1->unit_short_name;
            }
            if ($u2) {
                $rel->pr_unit_id_2 = $u2->unit_id;
                $rel->pr_unit_name_2 = $u2->unit_short_name;
            }
        }

        $relationsBySupplies = $relations->groupBy('supplies_id');

        $stocks = SuppliesStock::where('status', 1)
            ->whereIn('supplies_id', $suppliesIds)
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('supplies_id');

        foreach ($result as $value) {
            $unitIds = $decodeUnit($value->getAttributes()['supplies_unit'] ?? null);
            $value->supplies_unit = $unitIds;
            $value->units = collect($unitIds)
                ->map(fn ($id) => $unitsMap->get((int) $id))
                ->filter()
                ->values();
            $value->relation = ($relationsBySupplies->get($value->supplies_id) ?? collect())->values();

            $stockRows = ($stocks->get($value->supplies_id) ?? collect())->map(function ($stockRow) use ($value, $unitsMap) {
                $stockRow->supplies_name = $value->supplies_name;
                $unit = $unitsMap->get((int) $stockRow->unit_id);
                $stockRow->unit_name = $unit->unit_name ?? '';
                $stockRow->unit_short_name = $unit->unit_short_name ?? '';
                return $stockRow;
            });

            if ($value->relation->isNotEmpty()) {
                $stockRows = UnitStockSorter::sort($stockRows, $value->relation);
            }

            $value->stock = $stockRows->values();
            $defaultUnit = $unitsMap->get((int) $value->supplies_default_unit);
            $value->default_unit = $defaultUnit ? $defaultUnit->unit_name : '-';
        }
        
        return $result;
    }
}
