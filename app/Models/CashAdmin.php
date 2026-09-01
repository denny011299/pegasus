<?php

namespace App\Models;

use App\Support\BatchLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

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
            if (is_array($data["dates"])) {
                $start = $data["dates"][0] ?? null;
                $end = $data["dates"][1] ?? null;

                if ($start) $result->whereDate('ca_date', '>=', \Carbon\Carbon::parse($start)->toDateString());
                if ($end) $result->whereDate('ca_date', '<=', \Carbon\Carbon::parse($end)->toDateString());
            } else {
                $date = \Carbon\Carbon::parse($data["dates"])->toDateString();
                $result->whereDate('ca_date', $date);
            }
        }

        $result->orderBy('status', 'asc')->orderBy('ca_date', 'desc')->orderBy('created_at', 'desc');

        $result = $result->get();

        $sisa_kas = (int) (CashAdmin::where('status', 2)
            ->selectRaw('COALESCE(SUM(CASE
                WHEN ca_type = 1 AND ca_aksi = 1 THEN ca_nominal
                WHEN ca_type = 1 AND ca_aksi = 2 THEN -ca_nominal
                WHEN ca_type = 2 THEN -ca_nominal
                ELSE 0
            END), 0) as sisa_kas')
            ->value('sisa_kas') ?? 0);

        $staffNames = BatchLookup::staffNames(
            $result->flatMap(fn ($row) => [$row->staff_id, $row->created_by, $row->acc_by])
        );

        $caIds = $result->pluck('ca_id')->all();
        $detailsByCaId = $caIds === []
            ? collect()
            : CashAdminDetail::where('status', 1)
                ->whereIn('ca_id', $caIds)
                ->orderBy('created_at', 'desc')
                ->get()
                ->groupBy('ca_id');

        foreach ($result as $key => $value) {
            $value->staff_name = $staffNames->get((int) $value->staff_id) ?? '-';
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
            $value->acc_by_name = $value->acc_by
                ? ($staffNames->get((int) $value->acc_by) ?? '-')
                : '-';

            $detail = $detailsByCaId->get($value->ca_id);
            if ($detail && $detail->count() > 0) {
                $value->detail = $detail->values();
            }
        }
        return [
            'data' => $result,
            'sisa_kas' => $sisa_kas
        ];
    }

    function insertCashAdmin($data)
    {
        $t = new CashAdmin();
        $t->staff_id = $data["staff_id"];
        $t->cash_id = $data['cash_id'] ?? 0;
        $t->ca_date = $data['ca_date'] ?? now();
        $t->ca_nominal = $data["ca_nominal"];
        $t->ca_notes = $data["ca_notes"];
        $t->ca_type = $data["ca_type"];
        $t->ca_aksi = $data['oc_transaksi'] ?? 0;
        $t->ca_img = $data["ca_img"] ?? null;
        $t->status = $data['status'] ?? 1;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->ca_id;
    }

    function updateCashAdmin($data)
    {
        $t = CashAdmin::find($data["ca_id"]);
        $t->staff_id = $data["staff_id"];
        $t->cash_id = $data['cash_id'] ?? 0;
        $t->ca_date = $data['ca_date'] ?? now();
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
        $uid = Session::get('user') ? Session::get('user')->staff_id : null;
        if (!isset($data['ca_id'])){
            $t = CashAdmin::where('cash_id', $data["cash_id"])->first();
            $t->status = 2;
            $t->acc_by = $uid;
            $k = Cash::find($data["cash_id"]);
            $k->status = 2;
            $k->acc_by = $uid;
            $k->save();
        } else {
            $t = CashAdmin::find($data['ca_id']);
            $t->status = 2;
            $t->acc_by = $uid;
        }
        $t->save();
    }

    function declineCashAdmin($data)
    {
        $uid = Session::get('user') ? Session::get('user')->staff_id : null;
        $k = null;
        if (!isset($data['ca_id'])){
            $t = CashAdmin::where('cash_id', $data["cash_id"])->first();
            $t->status = 3;
            $t->acc_by = $uid;
            $k = Cash::find($data["cash_id"]);
            $k->status = 3;
            $k->acc_by = $uid;
            $k->save();
        } else {
            $t = CashAdmin::find($data['ca_id']);
            $t->status = 3;
            $t->acc_by = $uid;
        }
        $t->save();
    }
}
