<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashArmada extends Model
{
    protected $table = "cash_armadas";
    protected $primaryKey = "cr_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCashArmada($data = [])
    {

        $data = array_merge([
            "customer_id"=>null,
            "cash_id"=>null,
            "dates" => null,
        ], $data);

        $result = CashArmada::where('status', '>=', 1);
        if($data["customer_id"]) $result->where('customer_id', '=', $data["customer_id"]);
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
            $customer = Customer::find($value->customer_id);
            $value->customer_notes = $customer->customer_notes;
            $value->customer_saldo = $customer->customer_saldo;

            $detail = (new CashArmadaDetail())->getCashArmadaDetail(['cr_id' => $value->cr_id]);
            if ($detail->count() > 0) $value->detail = $detail;
        }
        return $result;
    }

    function insertCashArmada($data)
    {
        $t = new CashArmada();
        $t->customer_id = $data["customer_id"];
        $t->cash_id = $data["cash_id"];
        $t->cr_nominal = $data["cr_nominal"];
        $t->cr_notes = $data["cr_notes"];
        $t->cr_img = $data["cr_img"] ?? null;
        $t->status = $data['status'] ?? 1;
        $t->save();
        return $t->cr_id;
    }

    function updateCashArmada($data)
    {
        $t = CashArmada::find($data["cr_id"]);
        $t->customer_id = $data["customer_id"];
        $t->cash_id = $data["cash_id"];
        $t->cr_nominal = $data["cr_nominal"];
        $t->cr_notes = $data["cr_notes"];
        $t->cr_img = $data["cr_img"] ?? null;
        $t->status = $data['status'] ?? 1;
        $t->save();
        return $t->cr_id;
    }

    function deleteCashArmada($data)
    {
        $t = CashArmada::find($data["cr_id"]);
        $t->status = 0;
        $t->save();
    }

    function acceptCashArmada($data)
    {
        if (!isset($data['cr_id'])){
            $t = CashArmada::where('cash_id', $data["cash_id"])->first();
            $t->status = 2;
            $k = Cash::find($data["cash_id"]);
            $k->status = 2;
            $k->save();
        } else {
            $t = CashArmada::find($data['cr_id']);
            $t->status = 2;
        }
        $t->save();
    }

    function declineCashArmada($data)
    {
        if (!isset($data['cr_id'])){
            $t = CashArmada::where('cash_id', $data["cash_id"])->first();
            $t->status = 3;
            $k = Cash::find($data["cash_id"]);
            $k->status = 3;
            $k->save();
        } else {
            $t = CashArmada::find($data['cr_id']);
            $t->status = 3;
        }
        $t->save();
    }
}
