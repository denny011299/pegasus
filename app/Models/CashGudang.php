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
            "staff_id"=>null,
            "dates" => null,
        ], $data);

        $result = CashGudang::where('status', '>=', 1);
        if($data["staff_id"]) $result->where('staff_id', '=', $data["staff_id"]);

        if ($data["dates"]) {
            if (is_array($data["dates"]) && count($data["dates"]) === 2) {
                $startDate = \Carbon\Carbon::parse($data["dates"][0])->startOfDay();
                $endDate   = \Carbon\Carbon::parse($data["dates"][1])->endOfDay();

                $result->whereDate('created_at', '>=', $startDate->toDateString())
                        ->whereDate('created_at', '<=', $endDate->toDateString());
            } else {
                $date = \Carbon\Carbon::parse($data["dates"])->toDateString();
                $result->whereDate('created_at', $date);
            }
        }

        $result->orderByRaw('FIELD(status, 2, 1, 3)')->orderBy('created_at', 'desc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            $value->staff_name = Staff::find($value->staff_id)->staff_name;

            $detail = (new CashGudangDetail())->getCashGudangDetail(['cg_id' => $value->cg_id]);
            if ($detail->count() > 0) $value->detail = $detail;
        }
        return $result;
    }

    function insertCashGudang($data)
    {
        $t = new CashGudang();
        $t->staff_id = $data["staff_id"];
        $t->cash_id = $data["cash_id"] ?? 0;
        $t->cg_nominal = $data["cg_nominal"];
        $t->cg_notes = $data["cg_notes"];
        $t->cg_type = $data["cg_type"];
        $t->cg_aksi = $data["oc_transaksi"] ?? 0;
        $t->cg_img = $data["cg_img"] ?? null;
        $t->status = $data['status'] ?? 1;
        $t->save();
        return $t->cg_id;
    }

    function updateCashGudang($data)
    {
        $t = CashGudang::find($data["cg_id"]);
        $t->staff_id = $data["staff_id"];
        $t->cash_id = $data["cash_id"] ?? 0;
        $t->cg_nominal = $data["cg_nominal"];
        $t->cg_notes = $data["cg_notes"];
        $t->cg_type = $data["cg_type"];
        $t->cg_aksi = $data["oc_transaksi"] ?? 0;
        $t->cg_img = $data["cg_img"] ?? null;
        $t->status = $data['status'] ?? 1;
        $t->save();
        return $t->cg_id;
    }

    function deleteCashGudang($data)
    {
        $t = CashGudang::find($data["cg_id"]);
        $t->status = 0;
        $t->save();
    }

    function acceptCashGudang($data)
    {
        if (!isset($data['cg_id'])){
            $t = CashGudang::where('cash_id', $data["cash_id"])->first();
            $t->status = 2;
            $k = Cash::find($data["cash_id"]);
            $k->status = 2;
            $k->save();
        } else {
            $t = CashGudang::find($data['cg_id']);
            $t->status = 2;
        }
        $t->save();
    }

    function declineCashGudang($data)
    {
        if (!isset($data['cg_id'])){
            $t = CashGudang::where('cash_id', $data["cash_id"])->first();
            $t->status = 3;
            $k = Cash::find($data["cash_id"]);
            $k->status = 3;
        } else {
            $t = CashGudang::find($data['cg_id']);
            $t->status = 3;
        }
        $t->save();
        $k->save();
    }
}
