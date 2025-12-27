<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Production extends Model
{
    protected $table = "productions";
    protected $primaryKey = "production_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProduction($data = [])
    {
        $data = array_merge([
            "production_product_id" => null,
            "date" => null,
            "report" => null
        ], $data);  

        if ($data["report"] == null) $result = Production::where('status', '>=', 1);
        else if ($data["report"]) $result = Production::where('status', '>=', 0);
        if ($data["production_product_id"]) $result->where('production_product_id', '=', $data["production_product_id"]);

        if ($data["date"]) {
            if (is_array($data["date"]) && count($data["date"]) === 2) {
                // Jika date adalah array [start_date, end_date]]
                $startDate = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][0])->format('Y-m-d');
                $endDate   = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][1])->format('Y-m-d');
                $result->whereBetween('production_date', [$startDate, $endDate]);
            } else {
                // Jika date hanya satu nilai
                $date = $data["date"];
                if (!\Carbon\Carbon::hasFormat($data["date"], 'Y-m-d')) $date = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"])->format('Y-m-d');

                $result->where('production_date', '=', $date);
            }
        }

        $result->orderBy('created_at', 'asc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            $u = ProductVariant::find($value->production_product_id);
            $value->product_sku = $u->product_variant_sku;
            $v = Product::find($u->product_id);
            $value->product_name = $v->product_name." ".$u->product_variant_name;
            $x = Unit::find($v->unit_id);
            $value->unit_name = $x->unit_name;
        }
        return $result;
    }

    function insertProduction($data)
    {
        $t = new Production();
        $t->production_date = $data["production_date"];
        $t->production_code = $this->generateProductionID();
        $t->production_bom_id = $data["production_bom_id"];
        $t->production_product_id = $data["production_product_id"];
        $t->production_qty = $data["production_qty"];
        $t->production_created_by = 0;
        $t->save();
        return $t;
    }

    function updateProduction($data)
    {
        $t = Production::find($data["production_id"]);
        $t->production_date = $data["production_date"];
        $t->production_code = $data["production_code"];
        $t->production_bom_id = $data["production_bom_id"];
        $t->production_product_id = $data["production_product_id"];
        $t->production_qty = $data["production_qty"];
        $t->production_created_by = 0;
        $t->save();
        return $t->production_id;
    }

    function deleteProduction($data)
    {
        $t = Production::find($data["production_id"]);
        $t->notes = $data["delete_reason"];
        $t->status = 2;
        $t->save();    
    }
    function tolakDeleteProduction($data)
    {
        $t = Production::find($data["production_id"]);
        $t->notes = null;
        $t->status = 1;
        $t->save();    
    }

    function cancelProduction($data) {
        $t = Production::find($data["production_id"]);
        $t->status = 3;
        $t->save();
        $b = (new BomDetail())->getBomDetail([
            "bom_id" => $t->production_bom_id
        ]);

        foreach ($b as $key => $value) {
            $s = SuppliesStock::where("supplies_id", "=", $value->supplies_id)
                ->where("unit_id", "=", $value->unit_id)
                ->first();
            $s->ss_stock +=  ($value->bom_detail_qty * $t->production_qty);
            $s->save();
        }
    }

    function generateProductionID()
    {
        $id = self::max('production_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "PR" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
