<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

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
        
        $allData = CashSales::where('status', '=', 2)->get();
        $sisa_kas = 0;
        foreach ($allData as $value) {
            if ($value->cs_transaction == 1) {
                $sisa_kas += $value->cs_nominal;
            } else if ($value->cs_transaction >= 2) {
                $sisa_kas -= $value->cs_nominal;
            }
        }

        foreach ($result as $key => $value) {
            $sales = Staff::find($value->staff_id);
            $value->staff_name = $sales->staff_name;
            $value->staff_saldo = $sales->staff_saldo;
            $value->created_by_name = $value->created_by ? (Staff::find($value->created_by)->staff_name ?? '-') : '-';
            $value->acc_by_name = $value->acc_by ? (Staff::find($value->acc_by)->staff_name ?? '-') : '-';

            $staffAll = (new Staff())->getStaff();
            $total = 0;
            foreach ($staffAll as $key => $val) {
                $total += $val->staff_saldo;
            }
            $value->total_all = $total;

            if ($value->bank_id != 0) $value->bank_kode = Bank::find($value->bank_id)->bank_kode;

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
        $t->cash_id = $data["cash_id"] ?? 0;
        $t->staff_id = $data["staff_id"];
        $t->bank_id = $data["bank_id"];
        $t->cs_date = $data["cs_date"] ?? now();
        $t->cs_nominal = $data["cs_nominal"];
        $t->cs_notes = $data["cs_notes"];
        $t->cs_type = $data["cs_type"];
        $t->cs_transaction = $data["cs_transaction"] ?? 3;
        $t->cs_aksi = $data["cs_aksi"] ?? 0;
        $t->cs_img = $data["cs_img"] ?? null;
        $t->status = $data['status'] ?? 1;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->cs_id;
    }

    function updateCashSales($data)
    {
        $t = CashSales::find($data["cs_id"]);
        $t->staff_id = $data["staff_id"];
        $t->cs_date = $data["cs_date"] ?? now();
        $t->cs_nominal = $data["cs_nominal"];
        $t->cs_notes = $data["cs_notes"];
        $t->cs_type = $data["cs_type"];
        $t->cs_transaction = $data["cs_transaction"] ?? 3;
        $t->cs_aksi = $data["cs_aksi"] ?? 0;
        $t->cs_img = $data["cs_img"] ?? null;
        $incomingStatus = isset($data['status']) ? (int) $data['status'] : null;
        if ($incomingStatus !== null && $incomingStatus !== 3) {
            $t->status = $incomingStatus;
        } elseif ((int) ($t->status ?? 0) === 3) {
            // Pengajuan yang direvisi setelah ditolak harus kembali pending ACC.
            $t->status = 1;
            if (Schema::hasColumn($t->getTable(), 'acc_by')) {
                $t->acc_by = null;
            }
        } else {
            $t->status = $incomingStatus ?? 1;
        }
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
        $uid = Session::get('user') ? Session::get('user')->staff_id : null;
        if (!isset($data['cs_id'])){
            $t = CashSales::where('cash_id', $data["cash_id"])->first();
            $t->status = 2;
            $t->acc_by = $uid;
            $k = Cash::find($data["cash_id"]);
            $k->status = 2;
            $k->acc_by = $uid;
            $k->save();
        } else {
            $t = CashSales::find($data['cs_id']);
            $t->status = 2;
            $t->acc_by = $uid;
        }
        $t->save();
    }

    function declineCashSales($data)
    {
        $uid = Session::get('user') ? Session::get('user')->staff_id : null;
        if (!isset($data['cs_id'])){
            $t = CashSales::where('cash_id', $data["cash_id"])->first();
            $t->status = 3;
            $t->acc_by = $uid;
            $k = Cash::find($data["cash_id"]);
            $k->status = 3;
            $k->acc_by = $uid;
            $k->save();
        } else {
            $t = CashSales::find($data['cs_id']);
            $t->status = 3;
            $t->acc_by = $uid;
        }
        $t->save();
    }
}
