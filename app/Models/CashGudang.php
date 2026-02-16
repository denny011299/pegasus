<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashGudang extends Model
{
    protected $table = "cash_gudangs";
    protected $primaryKey = "cg_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCashGudang($data = [])
    {

        $data = array_merge([
            "staff_id"=>null
        ], $data);

        $result = CashGudang::where('status', '=', 1);
        if($data["staff_id"]) $result->where('staff_id', '=', $data["staff_id"]);
        $result->orderBy('created_at', 'asc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            $value->staff_name = Staff::find($value->staff_id)->staff_name;
        }
        return $result;
    }

    function insertCashGudang($data)
    {
        $t = new CashGudang();
        $t->staff_id = $data["staff_id"];
        $t->cg_nominal = $data["cg_nominal"];
        $t->cg_notes = $data["cg_notes"];
        $t->save();
        return $t->cg_id;
    }

    function updateCashGudang($data)
    {
        $t = CashGudang::find($data["cg_id"]);
        $t->staff_id = $data["staff_id"];
        $t->cg_nominal = $data["cg_nominal"];
        $t->cg_notes = $data["cg_notes"];
        $t->save();
        return $t->cg_id;
    }

    function deleteCashGudang($data)
    {
        $t = CashGudang::find($data["cg_id"]);
        $t->status = 0;
        $t->save();
    }
}
