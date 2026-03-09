<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashSales extends Model
{
    protected $table = "cash_sales";
    protected $primaryKey = "cs_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCashSales($data = [])
    {

        $data = array_merge([
            "staff_id"=>null,
            "cash_id"=>null,
            "dates" => null,
        ], $data);

        $result = CashSales::where('status', '>=', 1);
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

        // $result->orderByRaw('FIELD(status, 2, 1, 3)')->orderBy('created_at', 'desc');
        $result->orderBy('status', 'asc')->orderBy('created_at', 'desc');

        $result = $result->get();

        // Ambil staff_saldo meski $result kosong
        $staff_saldo = 0;
        if ($data["staff_id"]) {
            $selectedStaff = Staff::find($data["staff_id"]);
            $staff_saldo   = $selectedStaff ? $selectedStaff->staff_saldo : 0;
        }
        
        $allData = CashSales::where('status', 2)->get();
        $sisa_kas = 0;
        foreach ($allData as $value) {
            if ($value->cs_type == 1) {
                $sisa_kas += $value->cs_nominal;
            } else if ($value->cs_type == 2) {
                $sisa_kas -= $value->cs_nominal;
            }
        }

        foreach ($result as $key => $value) {
            $sales = Staff::find($value->staff_id);
            $value->staff_name = $sales->staff_name;
            $value->staff_saldo = $sales->staff_saldo;

            $staffAll = (new Staff())->getStaff();
            $total = 0;
            foreach ($staffAll as $key => $val) {
                $total += $val->staff_saldo;
            }
            $value->total_all = $total;

            $detail = (new CashSalesDetail())->getCashSalesDetail(['cs_id' => $value->cs_id]);
            if ($detail->count() > 0) $value->detail = $detail;
        }
        return [
            'data' => $result,
            'sisa_kas' => $sisa_kas,
            'staff_saldo' => $staff_saldo
        ];
    }

    function insertCashSales($data)
    {
        $t = new CashSales();
        $t->staff_id = $data["staff_id"];
        $t->cash_id = $data["cash_id"] ?? 0;
        $t->cs_nominal = $data["cs_nominal"];
        $t->cs_notes = $data["cs_notes"];
        $t->cs_type = $data["cs_type"];
        $t->cs_transaction = $data["cs_transaction"] ?? 3;
        $t->cs_aksi = $data["cs_aksi"] ?? 0;
        $t->cs_img = $data["cs_img"] ?? null;
        $t->status = $data['status'] ?? 1;
        $t->save();
        return $t->cs_id;
    }

    function updateCashSales($data)
    {
        $t = CashSales::find($data["cs_id"]);
        $t->staff_id = $data["staff_id"];
        $t->cs_nominal = $data["cs_nominal"];
        $t->cs_notes = $data["cs_notes"];
        $t->cs_type = $data["cs_type"];
        $t->cs_transaction = $data["cs_transaction"] ?? 3;
        $t->cs_aksi = $data["cs_aksi"] ?? 0;
        $t->cs_img = $data["cs_img"] ?? null;
        $t->status = $data['status'] ?? 1;
        $t->save();
        return $t->cs_id;
    }

    function deleteCashSales($data)
    {
        $t = CashSales::find($data["cs_id"]);
        $t->status = 0;
        $t->save();
    }

    function acceptCashSales($data)
    {
        if (!isset($data['cs_id'])){
            $t = CashSales::where('cash_id', $data["cash_id"])->first();
            $t->status = 2;
            $k = Cash::find($data["cash_id"]);
            $k->status = 2;
            $k->save();
        } else {
            $t = CashSales::find($data['cs_id']);
            $t->status = 2;
        }
        $t->save();
    }

    function declineCashSales($data)
    {
        if (!isset($data['cs_id'])){
            $t = CashSales::where('cash_id', $data["cash_id"])->first();
            $t->status = 3;
            $k = Cash::find($data["cash_id"]);
            $k->status = 3;
            $k->save();
        } else {
            $t = CashSales::find($data['cs_id']);
            $t->status = 3;
        }
        $t->save();
    }
}
