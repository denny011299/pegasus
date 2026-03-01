<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashAdmin extends Model
{
    protected $table = "cash_admins";
    protected $primaryKey = "ca_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCashAdmin($data = [])
    {
        $data = array_merge([
            "staff_id"=>null,
            "cash_id" => null,
            "dates" => null,
        ], $data);

        $result = CashAdmin::where('status', '>=', 1);
        if($data["staff_id"]) $result->where('staff_id', '=', $data["staff_id"]);

        if($data["cash_id"]) $result->where('cash_id', '=', $data["cash_id"]);

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

            $detail = CashAdminDetail::where('ca_id', $value->ca_id)->where('status', 1)->get();
            if ($detail->count() > 0) $value->detail = $detail;
        }
        return $result;
    }

    function insertCashAdmin($data)
    {
        $t = new CashAdmin();
        $t->staff_id = $data["staff_id"];
        $t->cash_id = $data['cash_id'] ?? 0;
        $t->ca_nominal = $data["ca_nominal"];
        $t->ca_notes = $data["ca_notes"];
        $t->ca_type = $data["ca_type"];
        $t->ca_aksi = $data['oc_transaksi'] ?? 0;
        $t->ca_img = $data["ca_img"] ?? null;
        $t->status = $data['status'] ?? 1;
        $t->save();
        return $t->ca_id;
    }

    function updateCashAdmin($data)
    {
        $t = CashAdmin::find($data["ca_id"]);
        $t->staff_id = $data["staff_id"];
        $t->cash_id = $data['cash_id'] ?? 0;
        $t->ca_nominal = $data["ca_nominal"];
        $t->ca_notes = $data["ca_notes"];
        $t->ca_type = $data["ca_type"];
        $t->ca_aksi = $data['oc_transaksi'] ?? 0;
        $t->ca_img = $data["ca_img"] ?? null;
        $t->status = 1;
        $t->save();
        return $t->ca_id;
    }

    function deleteCashAdmin($data)
    {
        $t = CashAdmin::find($data["ca_id"]);
        $t->status = 0;
        $t->save();
    }

    function acceptCashAdmin($data)
    {
        if (!isset($data['ca_id'])){
            $t = CashAdmin::where('cash_id', $data["cash_id"])->first();
            $t->status = 2;
            $k = Cash::find($data["cash_id"]);
            $k->status = 2;
            $k->save();
        } else {
            $t = CashAdmin::find($data['ca_id']);
            $t->status = 2;
        }
        $t->save();
    }

    function declineCashAdmin($data)
    {
        if (!isset($data['ca_id'])){
            $t = CashAdmin::where('cash_id', $data["cash_id"])->first();
            $t->status = 3;
            $k = Cash::find($data["cash_id"]);
            $k->status = 3;
            $k->save();
        } else {
            $t = CashAdmin::find($data['ca_id']);
            $t->status = 3;
        }
        $t->save();
        $k->save();
    }
}
