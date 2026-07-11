<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class BomDetail extends Model
{
    protected $table = "bom_details";
    protected $primaryKey = "bom_detail_id";
    public $timestamps = true;
    public $incrementing = true;

    function getBomDetail($data = [])
    {

        $data = array_merge([
            "bom_detail_id" => null,
            "bom_id" => null,
            "supplies_id" => null,
            "unit_id" => null,
        ], $data);

        $result = BomDetail::where('status', '=', 1);
        if ($data["bom_detail_id"]) $result->where('bom_detail_id', '=', $data["bom_detail_id"]);
        if ($data["bom_id"]) $result->where('bom_id', '=', $data["bom_id"]);
        if ($data["supplies_id"]) $result->where('supplies_id', '=', $data["supplies_id"]);
        if ($data["unit_id"]) $result->where('unit_id', '=', $data["unit_id"]);
        $result->orderBy('created_at', 'asc');

        $result = $result->get();

        foreach ($result as $key => $value) {
            $s = Supplies::find($value->supplies_id);
            if (isset($s->supplies_name)) $value->supplies_name = $s->supplies_name;

            $v = Unit::find($value->unit_id);
            $value->current_unit_id = $value->unit_id;
            $value->current_unit_name = $v
                ? ($v->unit_name ?? $v->unit_short_name ?? '-')
                : '-';
            $value->unit_name = $value->current_unit_name;

            if ($s) {
                $unitIds = json_decode($s->supplies_unit, true) ?: [];
                $value->active_units = Unit::whereIn('unit_id', $unitIds)
                    ->where('status', 1)
                    ->get(['unit_id', 'unit_name', 'unit_short_name', 'status'])
                    ->values();
                $value->units = $value->active_units;
                $value->supplies_relasi = (new SuppliesRelation())->getSuppliesRelation([
                    'supplies_id' => $value->supplies_id,
                ]);
                $value->supplies_default_unit = $s->supplies_default_unit;
            } else {
                $value->active_units = collect();
                $value->units = collect();
                $value->supplies_relasi = collect();
                $value->supplies_default_unit = null;
            }
        }

        return $result;
    }

    /**
     * @param  array<int>  $bomIds
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, mixed>>
     */
    public function getDetailBulk(array $bomIds, bool $full = false)
    {
        $bomIds = array_values(array_unique(array_filter(array_map('intval', $bomIds))));
        if ($bomIds === []) {
            return collect();
        }

        $details = self::where('status', '=', 1)
            ->whereIn('bom_id', $bomIds)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($details->isEmpty()) {
            return collect();
        }

        $suppliesIds = $details->pluck('supplies_id')->filter()->unique()->values()->all();
        $suppliesMap = $suppliesIds !== []
            ? Supplies::whereIn('supplies_id', $suppliesIds)->get()->keyBy('supplies_id')
            : collect();

        if (!$full) {
            return $details->groupBy('bom_id')->map(function ($group) use ($suppliesMap) {
                return $group->map(function ($value) use ($suppliesMap) {
                    $s = $suppliesMap->get($value->supplies_id);
                    $value->supplies_name = $s->supplies_name ?? '-';
                    return $value;
                })->values();
            });
        }

        $unitIdSet = $details->pluck('unit_id')->filter()->map(fn ($id) => (int) $id)->all();
        foreach ($suppliesMap as $supply) {
            foreach ((array) (json_decode($supply->supplies_unit, true) ?: []) as $unitId) {
                $unitIdSet[(int) $unitId] = true;
            }
        }
        $unitsMap = $unitIdSet !== []
            ? Unit::whereIn('unit_id', array_keys($unitIdSet))->get()->keyBy('unit_id')
            : collect();

        $mapped = $details->map(function ($value) use ($suppliesMap, $unitsMap) {
            $s = $suppliesMap->get($value->supplies_id);
            $value->supplies_name = $s->supplies_name ?? '-';

            $v = $unitsMap->get($value->unit_id);
            $value->current_unit_id = $value->unit_id;
            $value->current_unit_name = $v
                ? ($v->unit_name ?? $v->unit_short_name ?? '-')
                : '-';
            $value->unit_name = $value->current_unit_name;

            if ($s) {
                $unitIds = json_decode($s->supplies_unit, true) ?: [];
                $value->active_units = collect($unitIds)
                    ->map(fn ($id) => $unitsMap->get((int) $id))
                    ->filter(fn ($unit) => $unit && (int) $unit->status === 1)
                    ->values();
                $value->units = $value->active_units;
            } else {
                $value->active_units = collect();
                $value->units = collect();
            }

            return $value;
        });

        return $mapped->groupBy('bom_id')->map->values();
    }

    function insertBomDetail($data)
    {
        $t = new BomDetail();
        $t->bom_id = $data["bom_id"];
        $t->supplies_id = $data["supplies_id"];
        $t->bom_detail_qty = $data["bom_detail_qty"];
        $t->unit_id = $data["unit_id"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->bom_detail_id;
    }

    function updateBomDetail($data)
    {
        $t = BomDetail::find($data["bom_detail_id"]);
        $t->bom_id = $data["bom_id"];
        $t->supplies_id = $data["supplies_id"];
        $t->bom_detail_qty = $data["bom_detail_qty"];
        $t->unit_id = $data["unit_id"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->bom_detail_id;
    }
}
