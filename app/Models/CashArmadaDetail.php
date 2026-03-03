<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashArmadaDetail extends Model
{
    protected $table = "cash_armada_details";
    protected $primaryKey = "crd_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCashArmadaDetail($data = [])
    {
        $data = array_merge([
            "cr_id" => null,
        ], $data);

        $result = CashArmadaDetail::where('status', '=', 1);
        if($data["cr_id"]) $result->where('cr_id', '=', $data["cr_id"]);
        $result->orderBy('created_at', 'desc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            
        }
        return $result;
    }

    function insertCashArmadaDetail($data)
    {
        $t = new CashArmadaDetail();
        $t->cr_id = $data['cr_id'];
        $t->crd_nominal = $data["crd_nominal"];
        $t->crd_notes = $data["crd_notes"];
        $t->crd_type = $data["crd_type"];
        $t->save();
        return $t->crd_id;
    }

    function updateCashArmadaDetail($data)
    {
        $t = CashArmadaDetail::find($data["crd_id"]);
        $t->cr_id = $data['cr_id'];
        $t->crd_nominal = $data["crd_nominal"];
        $t->crd_notes = $data["crd_notes"];
        $t->crd_type = $data["crd_type"];
        $t->save();
        return $t->crd_id;
    }

    function deleteCashArmadaDetail($data)
    {
        $t = CashArmadaDetail::find($data["crd_id"]);
        $t->status = 0;
        $t->save();
    }
}
