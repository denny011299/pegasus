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

    // Khusus untuk stock opname bahan
    public function getSuppliesBulk(array $suppliesIds)
    {
        if (empty($suppliesIds)) return collect();

        // Modifikasi getSupplies() support whereIn
        $result = Supplies::where('status', 1)
            ->whereIn('supplies_id', $suppliesIds)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($result as $key => $value) {
            $value->sup_variant = (new SuppliesVariant())->getSuppliesVariant([
                "supplies_id" => $value->supplies_id
            ]);
            if ($value->supplies_supplier) {
                $value->supplier = Supplier::whereIn('supplier_id', json_decode($value->supplies_supplier, true))->get();
            }
            $value->supplies_unit = json_decode($value->supplies_unit);
            $value->supplies_relasi = (new SuppliesRelation())->getSuppliesRelation([
                "supplies_id" => $value->supplies_id
            ]);
            $value->units = Unit::whereIn('unit_id', $value->supplies_unit)->get();
            $value->stock = (new SuppliesStock())->getProductStock([
                "supplies_id" => $value->supplies_id
            ]);
            $value->created_by_name = $value->created_by 
                ? (Staff::find($value->created_by)->staff_name ?? '-') 
                : '-';
        }

        // keyBy supaya bisa lookup O(1) by supplies_id
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
