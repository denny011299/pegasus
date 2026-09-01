<?php

namespace App\Models;

use App\Support\BatchLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
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

        $customer_saldo = 0;
        if ($data["customer_id"]) {
            $customer_saldo = (int) (Customer::where('customer_id', $data["customer_id"])->value('customer_saldo') ?? 0);
        }

        $sisa_kas = (int) (CashArmada::where('status', 2)
            ->selectRaw('COALESCE(SUM(CASE
                WHEN cr_type = 1 AND cr_nominal < 0 THEN -cr_nominal
                WHEN cr_type = 1 THEN cr_nominal
                WHEN cr_type >= 2 AND cr_nominal < 0 THEN cr_nominal
                WHEN cr_type >= 2 THEN -cr_nominal
                ELSE 0
            END), 0) as sisa_kas')
            ->value('sisa_kas') ?? 0);

        $total_all = (int) Customer::where('status', 1)->sum('customer_saldo');

        $customers = BatchLookup::customers($result->pluck('customer_id'));
        $staffNames = BatchLookup::staffNames(
            $result->flatMap(fn ($row) => [$row->created_by, $row->acc_by])
        );

        $crIds = $result->pluck('cr_id')->all();
        $detailsByCrId = $crIds === []
            ? collect()
            : CashArmadaDetail::where('status', 1)
                ->whereIn('cr_id', $crIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('cr_id');

        foreach ($result as $key => $value) {
            $customer = $customers->get((int) $value->customer_id);
            $value->customer_notes = $customer?->customer_notes ?? '';
            $value->customer_saldo = $customer?->customer_saldo ?? 0;
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
            $value->acc_by_name = $value->acc_by
                ? ($staffNames->get((int) $value->acc_by) ?? '-')
                : '-';
            $value->total_all = $total_all;

            $detail = $detailsByCrId->get($value->cr_id);
            if ($detail && $detail->count() > 0) {
                $value->detail = $detail->values();
            }
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
        // Audit trail (2026-08-05): kalau row ini lahir dari CashGudang::acceptCashGudang(),
        // catat cgd_id asalnya di sini supaya bisa ditelusuri balik — lihat migration
        // 2026_08_05_020000_add_source_cgd_id_to_cash_armadas_table. Dibungkus Schema::hasColumn()
        // supaya tetap aman kalau migration kolom ini belum ter-merge di branch/environment yang
        // menjalankan kode ini.
        if (isset($data['source_cgd_id']) && Schema::hasColumn('cash_armadas', 'source_cgd_id')) {
            $t->source_cgd_id = $data['source_cgd_id'];
        }
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
