<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuppliesStock extends Model
{
    protected $table = "supplies_stocks";
    protected $primaryKey = "ss_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProductStock($data = [])
    {
        $data = array_merge([
            "supplies_id" => null,
            "supplies_unit" => null,
        ], $data);

        $result = self::where('status', '=', 1);
        if ($data["supplies_id"]) $result->where('supplies_id', '=', $data["supplies_id"]);
        if ($data["supplies_unit"]) $result->whereIn('unit_id', $result->supplies_unit)->get();
        $result->orderBy('created_at', 'asc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            $s = Supplies::find($value->supplies_id);
            $value->supplies_name = $s->supplies_name;
            $u = Unit::find($value->unit_id);
            $value->unit_name = $u->unit_name;
            $value->unit_short_name = $u->unit_short_name;
        }
        return $result;
    }

    function insertProductStock($data)
    {
        $t = new self();
        $t->supplies_id = $data["supplies_id"];
        $t->unit_id = $data["unit_id"];
        $t->ss_stock = $data["ss_stock"] ?? 0;
        $t->save();

        return $t->role_id;
    }

    function updateProductStock($data)
    {
        $t = self::find($data["ss_id"]);
        $t->supplies_id = $data["supplies_id"];
        $t->unit_id = $data["unit_id"];
        $t->ss_stock = $data["ss_stock"];
        $t->save();
        return $t->role_id;
    }

    function deleteProductStock($data)
    {
        $t = self::find($data["ps_id"]);
        $t->status = 0;
        $t->save();
    }

    function syncStock($supplies_id)
    {
        $product = Supplies::find($supplies_id);
        self::where('supplies_id', '=', $supplies_id)->whereNotIn('unit_id', json_decode($product->supplies_unit, true))->update(["status" => 0]);
        foreach (json_decode($product->supplies_unit, true) as $key => $unit) {
            $dt = self::where('supplies_id', '=', $supplies_id)->where('unit_id', '=', $unit)->count();
            if ($dt == 0) {
                self::insertProductStock([
                    "supplies_id" => $supplies_id,
                    "unit_id" => $unit,
                    "ss_stock" => 0,
                ]);
            }
        }
    }

    function cekStockBerlebih($data)
    {
        $t = self::find($data["ps_id"]);
        $p = Product::find($data["supplies_id"]);
        if ($p->unit_id != $data["unit_id"]) {
            $ada = 1;
            while ($ada == 1) {
                $r = ProductRelation::where('pr_unit_id_2', '=', $data["unit_id"])
                    ->where('supplies_id', '=', $data["supplies_id"])->first();
                if ($t->ss_stock >= $r->pr_unit_value_2) {

                    $tambah = floor($t->ss_stock / $r->pr_unit_value_2);
                    $t->ss_stock %= $r->pr_unit_value_2;

                    $t->save();
                    $stBaru = self::where('supplies_id', '=', $data["supplies_id"])
                        ->where("unit_id", '=', $r->pr_unit_id_1)->first();
                    $stBaru->ss_stock += $tambah;
                    $stBaru->save();

                    $cek = $r = ProductRelation::where('pr_unit_id_2', '=', $r->pr_unit_id_1)
                        ->where('supplies_id', '=', $data["supplies_id"]);
                    if ($cek->count() <= 0) {
                        $ada = -1;
                    }
                } else  $ada = -1;
            }
        }
    }
}
