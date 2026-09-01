<?php

namespace App\Models;

use App\Support\BatchLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

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
            if (is_array($data["dates"])) {
                $start = $data["dates"][0] ?? null;
                $end = $data["dates"][1] ?? null;

                if ($start) $result->whereDate('cg_date', '>=', \Carbon\Carbon::parse($start)->toDateString());
                if ($end) $result->whereDate('cg_date', '<=', \Carbon\Carbon::parse($end)->toDateString());
            } else {
                $date = \Carbon\Carbon::parse($data["dates"])->toDateString();
                $result->whereDate('cg_date', $date);
            }
        }

        $result->orderBy('status', 'asc')->orderBy('cg_date', 'desc')->orderBy('created_at', 'desc');

        $result = $result->get();

        $sisa_kas = (int) (CashGudang::where('status', 2)
            ->selectRaw('COALESCE(SUM(CASE
                WHEN cg_type = 1 AND cg_aksi = 1 THEN cg_nominal
                WHEN cg_type = 1 AND cg_aksi = 2 THEN -cg_nominal
                WHEN cg_type = 2 THEN -cg_nominal
                ELSE 0
            END), 0) as sisa_kas')
            ->value('sisa_kas') ?? 0);

        $staffNames = BatchLookup::staffNames(
            $result->flatMap(fn ($row) => [$row->staff_id, $row->created_by, $row->acc_by])
        );

        $cgIds = $result->pluck('cg_id')->all();
        $detailsByCgId = collect();
        if ($cgIds !== []) {
            $details = CashGudangDetail::where('status', 1)
                ->whereIn('cg_id', $cgIds)
                ->orderBy('created_at', 'desc')
                ->get();

            $customerNotes = BatchLookup::customers($details->pluck('customer_id'), ['customer_id', 'customer_notes'])
                ->mapWithKeys(fn ($row) => [(int) $row->customer_id => $row->customer_notes]);

            foreach ($details as $detailRow) {
                $detailRow->customer_notes = $customerNotes->get((int) $detailRow->customer_id) ?? '';
            }

            $detailsByCgId = $details->groupBy('cg_id');
        }

        foreach ($result as $key => $value) {
            $value->staff_name = $staffNames->get((int) $value->staff_id) ?? '-';
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
            $value->acc_by_name = $value->acc_by
                ? ($staffNames->get((int) $value->acc_by) ?? '-')
                : '-';

            $detail = $detailsByCgId->get($value->cg_id);
            if ($detail && $detail->count() > 0) {
                $value->detail = $detail->values();
            }
        }
        return [
            'data' => $result,
            'sisa_kas' => $sisa_kas
        ];
    }

    function insertCashGudang($data)
    {
        $t = new CashGudang();
        $t->staff_id = $data["staff_id"];
        $t->cash_id = $data["cash_id"] ?? 0;
        $t->cg_date = $data['cg_date'] ?? now();
        $t->cg_nominal = $data["cg_nominal"];
        $t->cg_notes = $data["cg_notes"];
        $t->cg_type = $data["cg_type"];
        $t->cg_aksi = $data["oc_transaksi"] ?? 0;
        $t->cg_img = $data["cg_img"] ?? null;
        $t->status = $data['status'] ?? 1;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->cg_id;
    }

    function updateCashGudang($data)
    {
        $t = CashGudang::find($data["cg_id"]);
        $t->staff_id = $data["staff_id"];
        $t->cash_id = $data["cash_id"] ?? 0;
        $t->cg_date = $data['cg_date'] ?? now();
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
        $uid = Session::get('user') ? Session::get('user')->staff_id : null;
        if (!isset($data['cg_id'])){
            // Cabang "Kas Besar" (entri "saldo") — punya baris `cashes` sungguhan (`cash_id`), jadi
            // status entri Cash-nya juga ikut di-flip di sini. Entri jenis ini TIDAK PERNAH punya
            // baris CashGudangDetail (lihat insertCashGudang()), jadi loop CashArmada di bawah akan
            // selalu jalan atas $detail kosong — itu memang disengaja, bukan celah. Jangan tambah
            // mutasi customer_saldo di cabang ini tanpa konfirmasi PM lagi (lihat KNOWN_ISSUES.md).
            $t = CashGudang::where('cash_id', $data["cash_id"])->first();
            $t->status = 2;
            $t->acc_by = $uid;
            $k = Cash::find($data["cash_id"]);
            $k->status = 2;
            $k->acc_by = $uid;
            $k->save();
            $t->save();
        } else {
            // Cabang "Kas Gudang" (entri "operasional") — $detail di bawah SELALU berisi baris
            // (lihat insertCashGudang()), ini yang benar-benar memicu insert CashArmada per baris.
            $t = CashGudang::find($data['cg_id']);
            $t->status = 2;
            $t->acc_by = $uid;
            $t->save();
        }
        $detail = CashGudangDetail::where('cg_id', $t->cg_id)->where('status', 1)->get();
        foreach ($detail as $key => $value) {
            (new CashArmada())->insertCashArmada([
                "cr_date" => $t->cg_date,
                "customer_id" => $value->customer_id,
                "cash_id" => 0,
                "cr_nominal" => $value->cgd_nominal,
                "cr_notes" => "Penyerahan kas dari gudang",
                "cr_type" => 1,
                "status" => 2,
                // Audit trail (2026-08-05): telusuri balik row ini ke baris cash_gudang_details
                // asalnya — lihat migration 2026_08_05_020000_add_source_cgd_id_to_cash_armadas_table.
                "source_cgd_id" => $value->cgd_id,
            ]);
        }
        return 1;
    }

    function declineCashGudang($data)
    {
        $uid = Session::get('user') ? Session::get('user')->staff_id : null;
        if (!isset($data['cg_id'])){
            $t = CashGudang::where('cash_id', $data["cash_id"])->first();
            $t->status = 3;
            $t->acc_by = $uid;
            $k = Cash::find($data["cash_id"]);
            $k->status = 3;
            $k->acc_by = $uid;
            $k->save();
        } else {
            $t = CashGudang::find($data['cg_id']);
            $t->status = 3;
            $t->acc_by = $uid;
        }
        $t->save();
    }
}
