<?php

namespace App\Models;

use App\Support\BatchLookup;
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
            if (is_array($data["dates"])) {
                $start = $data["dates"][0] ?? null;
                $end = $data["dates"][1] ?? null;

                if ($start) $result->whereDate('cs_date', '>=', \Carbon\Carbon::parse($start)->toDateString());
                if ($end) $result->whereDate('cs_date', '<=', \Carbon\Carbon::parse($end)->toDateString());
            } else {
                $date = \Carbon\Carbon::parse($data["dates"])->toDateString();
                $result->whereDate('cs_date', $date);
            }
        }

        // $result->orderByRaw('FIELD(status, 2, 1, 3)')->orderBy('created_at', 'desc');
        $result->orderBy('status', 'asc')->orderBy('cs_date', 'desc')->orderBy('created_at', 'desc');

        $result = $result->get();

        $staff_saldo = 0;
        if ($data["staff_id"]) {
            $staff_saldo = (int) (Staff::where('staff_id', $data["staff_id"])->value('staff_saldo') ?? 0);
        }

        $sisa_kas = (int) (CashSales::where('status', 2)
            ->selectRaw('COALESCE(SUM(CASE
                WHEN cs_transaction = 1 THEN cs_nominal
                WHEN cs_transaction >= 2 THEN -cs_nominal
                ELSE 0
            END), 0) as sisa_kas')
            ->value('sisa_kas') ?? 0);

        $total_all = (int) Staff::where('status', 1)->sum('staff_saldo');

        $staffRows = BatchLookup::staffRows(
            $result->flatMap(fn ($row) => [$row->staff_id, $row->created_by, $row->acc_by])
        );

        $bankIds = $result->pluck('bank_id')
            ->filter(fn ($id) => (int) $id !== 0)
            ->unique()
            ->values()
            ->all();
        $bankCodes = $bankIds === []
            ? collect()
            : Bank::whereIn('bank_id', $bankIds)->pluck('bank_kode', 'bank_id');

        $csIds = $result->pluck('cs_id')->all();
        $detailsByCsId = $csIds === []
            ? collect()
            : CashSalesDetail::where('status', 1)
                ->whereIn('cs_id', $csIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('cs_id');

        foreach ($result as $key => $value) {
            $sales = $staffRows->get((int) $value->staff_id);
            $value->staff_name = $sales?->staff_name ?? '-';
            $value->staff_saldo = $sales?->staff_saldo ?? 0;
            $value->created_by_name = $value->created_by
                ? ($staffRows->get((int) $value->created_by)?->staff_name ?? '-')
                : '-';
            $value->acc_by_name = $value->acc_by
                ? ($staffRows->get((int) $value->acc_by)?->staff_name ?? '-')
                : '-';
            $value->total_all = $total_all;

            if ((int) $value->bank_id !== 0) {
                $value->bank_kode = $bankCodes->get((int) $value->bank_id);
            }

            $detail = $detailsByCsId->get($value->cs_id);
            if ($detail && $detail->count() > 0) {
                $value->detail = $detail->values();
            }
        }
        return [
            'data' => $result,
            'sisa_kas' => $sisa_kas,
            'staff_saldo' => $staff_saldo
        ];
    }

    /**
     * Cari pembayaran berdasarkan referensi sistem eksternal.
     *
     * Dipakai External API untuk menjawab GET sekaligus mengenali permintaan
     * POST yang diulang, agar tidak lahir transaksi kas ganda.
     */
    function findByRefPaymentId($refPaymentId)
    {
        return CashSales::where('ref_payment_id', '=', $refPaymentId)->first();
    }

    function insertCashSales($data)
    {
        $t = new CashSales();
        // Hanya terisi bila pembayaran datang lewat External API.
        $t->ref_payment_id = $data["ref_payment_id"] ?? null;
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
