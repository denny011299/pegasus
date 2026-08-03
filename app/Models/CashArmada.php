<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

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
            if (is_array($data["dates"])) {
                $start = $data["dates"][0] ?? null;
                $end = $data["dates"][1] ?? null;

                if ($start) $result->whereDate('cr_date', '>=', \Carbon\Carbon::parse($start)->toDateString());
                if ($end) $result->whereDate('cr_date', '<=', \Carbon\Carbon::parse($end)->toDateString());
            } else {
                $date = \Carbon\Carbon::parse($data["dates"])->toDateString();
                $result->whereDate('cr_date', $date);
            }
        }

        // $result->orderByRaw('FIELD(status, 2, 1, 3)')->orderBy('created_at', 'desc');
        $result->orderBy('status', 'asc')->orderBy('cr_date', 'desc')->orderBy('created_at', 'desc');

        $result = $result->get();

        // Ambil customer_saldo meski $result kosong
        $customer_saldo = 0;
        if ($data["customer_id"]) {
            $selectedStaff = Customer::find($data["customer_id"]);
            $customer_saldo   = $selectedStaff ? $selectedStaff->customer_saldo : 0;
        }

        $allData = CashArmada::where('status', 2)->get();
        $sisa_kas = 0;
        foreach ($allData as $value) {
            if ($value->cr_type == 1) {
                if ($value->cr_nominal < 0) $sisa_kas -= $value->cr_nominal;
                else $sisa_kas += $value->cr_nominal;
            } else if ($value->cr_type >= 2) {
                if ($value->cr_nominal < 0) $sisa_kas += $value->cr_nominal;
                else $sisa_kas -= $value->cr_nominal;
            }
        }

        foreach ($result as $key => $value) {
            $customer = Customer::find($value->customer_id);
            $value->customer_notes = $customer->customer_notes;
            $value->customer_saldo = $customer->customer_saldo;
            $value->created_by_name = $value->created_by ? (Staff::find($value->created_by)->staff_name ?? '-') : '-';
            $value->acc_by_name = $value->acc_by ? (Staff::find($value->acc_by)->staff_name ?? '-') : '-';

            $custAll = (new Customer())->getCustomer();
            $total = 0;
            foreach ($custAll as $key => $val) {
                $total += $val->customer_saldo;
            }
            $value->total_all = $total;

            $detail = (new CashArmadaDetail())->getCashArmadaDetail(['cr_id' => $value->cr_id]);
            if ($detail->count() > 0) $value->detail = $detail;
        }
        return [
            'data' => $result,
            'sisa_kas' => $sisa_kas,
            'customer_saldo' => $customer_saldo
        ];
    }

    /**
     * Cari pembayaran berdasarkan referensi sistem eksternal.
     *
     * Dipakai External API untuk dua hal: menjawab GET, dan mengenali
     * permintaan POST yang diulang supaya tidak membuat transaksi ganda.
     * Baris terhapus (status 0) sengaja ikut terbaca — kalau sebuah referensi
     * pernah dipakai, permintaan ulang tidak boleh diam-diam membuat kas baru.
     */
    function findByRefPaymentId($refPaymentId)
    {
        return CashArmada::where('ref_payment_id', '=', $refPaymentId)->first();
    }

    function insertCashArmada($data)
    {
        $t = new CashArmada();
        // Hanya terisi bila pembayaran datang lewat External API.
        $t->ref_payment_id = $data["ref_payment_id"] ?? null;
        $t->customer_id = $data["customer_id"];
        $t->cash_id = $data["cash_id"];
        $t->cr_date = $data['cr_date'] ?? now();
        $t->cr_nominal = $data["cr_nominal"];
        $t->cr_notes = $data["cr_notes"];
        $t->cr_type = $data["cr_type"] ?? 3;
        $t->cr_aksi = $data["cr_aksi"] ?? 0;
        $t->cr_img = $data["cr_img"] ?? null;
        $t->status = $data['status'] ?? 1;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->cr_id;
    }

    function updateCashArmada($data)
    {
        $t = CashArmada::find($data["cr_id"]);
        $t->customer_id = $data["customer_id"];
        $t->cr_date = $data['cr_date'] ?? now();
        $t->cr_nominal = $data["cr_nominal"];
        $t->cr_notes = $data["cr_notes"];
        $t->cr_type = $data["cr_type"] ?? 3;
        $t->cr_aksi = $data["cr_aksi"] ?? 0;
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
        $uid = Session::get('user') ? Session::get('user')->staff_id : null;
        if (!isset($data['cr_id'])){
            $t = CashArmada::where('cash_id', $data["cash_id"])->first();
            $t->status = 2;
            $t->acc_by = $uid;
            $k = Cash::find($data["cash_id"]);
            $k->status = 2;
            $k->acc_by = $uid;
            $k->save();
        } else {
            $t = CashArmada::find($data['cr_id']);
            $t->status = 2;
            $t->acc_by = $uid;
        }
        $t->save();
    }

    function declineCashArmada($data)
    {
        $uid = Session::get('user') ? Session::get('user')->staff_id : null;
        if (!isset($data['cr_id'])){
            $t = CashArmada::where('cash_id', $data["cash_id"])->first();
            $t->status = 3;
            $t->acc_by = $uid;
            $k = Cash::find($data["cash_id"]);
            $k->status = 3;
            $k->acc_by = $uid;
            $k->save();
        } else {
            $t = CashArmada::find($data['cr_id']);
            $t->status = 3;
            $t->acc_by = $uid;
        }
        $t->save();
    }
}
