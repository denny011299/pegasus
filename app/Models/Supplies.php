<?php

namespace App\Models;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Supplies extends Model
{
    protected $table = "supplies";
    protected $primaryKey = "supplies_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSupplies($data = [])
    {
        $data = array_merge([
            "supplies_id" => null,
            "supplies_name" => null,
            "supplies_desc" => null,
        ], $data);
        $result = Supplies::where('status', '=', 1);
        if ($data["supplies_id"]) $result->where('supplies_id', '=', $data["supplies_id"]);
        if ($data["supplies_name"]) $result->where('supplies_name', 'like', '%' . $data["supplies_name"] . '%');
        if ($data["supplies_desc"]) $result->where('supplies_desc', 'like', '%' . $data["supplies_desc"] . '%');
        $result->orderBy('created_at', 'asc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            $value->sup_variant = (new SuppliesVariant())->getSuppliesVariant([
                "supplies_id" => $value->supplies_id
            ]);
            if ($value->supplies_supplier) {
                $value->supplier =  Supplier::whereIn('supplier_id', json_decode($value->supplies_supplier, true))->get();
            }
            $value->supplies_unit = json_decode($value->supplies_unit);
            $value->supplies_relasi = (new SuppliesRelation())->getSuppliesRelation([
                "supplies_id" => $value->supplies_id
            ]);
            // dd($value->units);
            $value->units = Unit::whereIn('unit_id', $value->supplies_unit)->get();
            $value->stock = (new SuppliesStock())->getProductStock([
                "supplies_id" => $value->supplies_id
            ]);
            $value->created_by_name = $value->created_by ? (Staff::find($value->created_by)->staff_name ?? '-') : '-';
        }
        return $result;
    }

    // Khusus untuk stock opname bahan — isi field sama seperti getSupplies(), tapi query digabung (tanpa N+1).
    public function getSuppliesBulk(array $suppliesIds)
    {
        $suppliesIds = array_values(array_unique(array_filter(array_map('intval', $suppliesIds))));
        if ($suppliesIds === []) {
            return collect();
        }

        $result = Supplies::where('status', 1)
            ->whereIn('supplies_id', $suppliesIds)
            ->orderBy('created_at', 'asc')
            ->get();

        if ($result->isEmpty()) {
            return collect();
        }

        $ids = $result->pluck('supplies_id')->all();
        $suppliesById = $result->keyBy('supplies_id');

        $creatorIds = $result->pluck('created_by')->filter()->unique()->map(fn ($id) => (int) $id)->all();
        $staffNames = $creatorIds !== []
            ? Staff::whereIn('staff_id', $creatorIds)->pluck('staff_name', 'staff_id')
            : collect();

        $allUnitIds = [];
        foreach ($result as $s) {
            $unitArr = json_decode($s->getAttributes()['supplies_unit'] ?? '[]', true) ?: [];
            foreach ($unitArr as $uid) {
                $allUnitIds[(int) $uid] = true;
            }
        }

        $relations = SuppliesRelation::where('status', 1)
            ->whereIn('supplies_id', $ids)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($relations as $rel) {
            $allUnitIds[(int) $rel->su_id_1] = true;
            $allUnitIds[(int) $rel->su_id_2] = true;
        }

        $variants = SuppliesVariant::where('status', 1)
            ->whereIn('supplies_id', $ids)
            ->orderBy('created_at', 'asc')
            ->get();

        $supplierIdSet = [];
        foreach ($variants as $v) {
            if ($v->supplier_id) {
                $supplierIdSet[(int) $v->supplier_id] = true;
            }
        }
        foreach ($result as $s) {
            $supRaw = $s->getAttributes()['supplies_supplier'] ?? null;
            if ($supRaw) {
                foreach (json_decode($supRaw, true) ?: [] as $pid) {
                    $supplierIdSet[(int) $pid] = true;
                }
            }
        }

        $stocks = SuppliesStock::where('status', 1)
            ->whereIn('supplies_id', $ids)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($stocks as $stk) {
            $allUnitIds[(int) $stk->unit_id] = true;
        }

        $unitsMap = $allUnitIds !== []
            ? Unit::whereIn('unit_id', array_keys($allUnitIds))->get()->keyBy('unit_id')
            : collect();

        foreach ($relations as $rel) {
            $u1 = $unitsMap->get($rel->su_id_1);
            $u2 = $unitsMap->get($rel->su_id_2);
            if ($u1) {
                $rel->pr_unit_id_1 = $u1->unit_id;
                $rel->pr_unit_name_1 = $u1->unit_short_name;
            } else {
                $rel->pr_unit_id_1 = $rel->su_id_1;
                $rel->pr_unit_name_1 = '-';
            }
            if ($u2) {
                $rel->pr_unit_id_2 = $u2->unit_id;
                $rel->pr_unit_name_2 = $u2->unit_short_name;
            } else {
                $rel->pr_unit_id_2 = $rel->su_id_2;
                $rel->pr_unit_name_2 = '-';
            }
        }

        foreach ($stocks as $stk) {
            $sup = $suppliesById->get($stk->supplies_id);
            $stk->supplies_name = $sup ? $sup->supplies_name : '-';
            $u = $unitsMap->get($stk->unit_id);
            $stk->unit_name = $u ? $u->unit_name : '-';
            $stk->unit_short_name = $u ? $u->unit_short_name : '-';
        }

        $supplierMap = $supplierIdSet !== []
            ? Supplier::whereIn('supplier_id', array_keys($supplierIdSet))->pluck('supplier_name', 'supplier_id')
            : collect();

        $stocksBySupply = $stocks->groupBy('supplies_id');
        $relationsBySupply = $relations->groupBy('supplies_id');
        $variantsBySupply = $variants->groupBy('supplies_id');

        foreach ($result as $value) {
            $sid = $value->supplies_id;

            $supRaw = $value->getAttributes()['supplies_supplier'] ?? null;
            if ($supRaw) {
                $value->supplier = Supplier::whereIn(
                    'supplier_id',
                    json_decode($supRaw, true) ?: []
                )->get();
            }

            $value->supplies_unit = json_decode($value->getAttributes()['supplies_unit'] ?? '[]');

            $unitArr = json_decode($value->getAttributes()['supplies_unit'] ?? '[]', true) ?: [];
            $value->units = collect($unitArr)
                ->map(fn ($uid) => $unitsMap->get((int) $uid))
                ->filter()
                ->values();

            $value->supplies_relasi = $relationsBySupply->get($sid, collect());

            $stockForSupply = $stocksBySupply->get($sid, collect())->values();

            $value->sup_variant = $variantsBySupply->get($sid, collect())->map(function ($variant) use ($suppliesById, $supplierMap, $unitsMap, $stockForSupply) {
                $clone = clone $variant;
                $s = $suppliesById->get($clone->supplies_id);
                $clone->supplies_name = $s ? $s->supplies_name : '-';
                $ssId = $clone->supplier_id;
                $clone->supplier_name = $ssId ? ($supplierMap->get((int) $ssId) ?: null) : null;

                $uArr = json_decode($s->getAttributes()['supplies_unit'] ?? '[]', true) ?: [];
                $clone->supplies_unit = collect($uArr)
                    ->map(fn ($uid) => $unitsMap->get((int) $uid))
                    ->filter()
                    ->values();

                $clone->stock = $stockForSupply;

                return $clone;
            })->values();

            $value->stock = $stockForSupply;

            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
        }

        return $result->keyBy('supplies_id');
    }

    function insertSupplies($data)
    {
        $t = new Supplies();
        $t->supplies_name = $data["supplies_name"];
        $t->supplies_desc = $data["supplies_desc"];
        $t->supplies_unit = $data["supplies_unit"];
        $t->supplies_alert = $data["supplies_alert"];
        $t->supplies_default_unit = $data["supplies_default_unit"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->supplies_id;
    }

    function updateSupplies($data)
    {
        $t = Supplies::find($data["supplies_id"]);
        $t->supplies_name = $data["supplies_name"];
        $t->supplies_desc = $data["supplies_desc"];
        $t->supplies_unit = $data["supplies_unit"];
        $t->supplies_alert = $data["supplies_alert"];
        $t->supplies_default_unit = $data["supplies_default_unit"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->supplies_id;
    }

    function deleteSupplies($data)
    {
        $t = Supplies::find($data["supplies_id"]);
        $t->status = 0;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        SuppliesVariant::where("supplies_id", "=", $data["supplies_id"])->update(["status" => 0]);
        SuppliesStock::where("supplies_id", "=", $data["supplies_id"])->update(["status" => 0]);
    }
}
