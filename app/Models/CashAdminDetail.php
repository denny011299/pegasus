<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashAdminDetail extends Model
{
    protected $table = "cash_admin_details";
    protected $primaryKey = "cad_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCashAdminDetail($data = [])
    {

        $data = array_merge([
            "ca_id" => null,
        ], $data);

        $result = CashAdminDetail::where('status', '=', 1);
        if($data["ca_id"]) $result->where('ca_id', '=', $data["ca_id"]);
        $result->orderBy('created_at', 'desc');

        $result = $result->get();
        foreach ($result as $key => $value) {

        }
        return $result;
    }

    function insertCashAdminDetail($data)
    {
        $t = new CashAdminDetail();
        $t->ca_id = $data['ca_id'];
        $t->cad_nominal = $data["cad_nominal"];
        $t->cad_notes = $data["cad_notes"];
        $t->save();
        return $t->cad_id;
    }

    function updateCashAdminDetail($data)
    {
        $t = CashAdminDetail::find($data["cad_id"]);
        $t->ca_id = $data['ca_id'];
        $t->cad_nominal = $data["cad_nominal"];
        $t->cad_notes = $data["cad_notes"];
        $t->save();
        return $t->cad_id;
    }

    function deleteCashAdminDetail($data)
    {
        $t = CashAdminDetail::find($data["cad_id"]);
        $t->status = 0;
        $t->save();
    }
}
