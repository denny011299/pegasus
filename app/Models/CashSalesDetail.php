<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashSalesDetail extends Model
{
    protected $table = "cash_sales_details";
    protected $primaryKey = "csd_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCashSalesDetail($data = [])
    {
        $data = array_merge([
            "cs_id" => null,
        ], $data);

        $result = CashSalesDetail::where('status', '=', 1);
        if($data["cs_id"]) $result->where('cs_id', '=', $data["cs_id"]);
        $result->orderBy('created_at', 'desc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            
        }
        return $result;
    }

    function insertCashSalesDetail($data)
    {
        $t = new CashSalesDetail();
        $t->cs_id = $data['cs_id'];
        $t->csd_nominal = $data["csd_nominal"];
        $t->csd_notes = $data["csd_notes"];
        $t->csd_type = $data["csd_type"];
        $t->save();
        return $t->csd_id;
    }

    function updateCashSalesDetail($data)
    {
        $t = CashSalesDetail::find($data["csd_id"]);
        $t->cs_id = $data['cs_id'];
        $t->csd_nominal = $data["csd_nominal"];
        $t->csd_notes = $data["csd_notes"];
        $t->csd_type = $data["csd_type"];
        $t->save();
        return $t->csd_id;
    }

    function deleteCashSalesDetail($data)
    {
        $t = CashSalesDetail::find($data["csd_id"]);
        $t->status = 0;
        $t->save();
    }
}
