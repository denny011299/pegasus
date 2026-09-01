<?php

namespace App\Models;

use App\Support\BatchLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class Cash extends Model
{
    protected $table = "cashes";
    protected $primaryKey = "cash_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCash($data = []){
        $data = array_merge([
            "cash_id"=>null,
            "cash_date"=>null,
            "dates" => null,
        ], $data);

        $result = self::where('status', '>=', 1)->where('status', '<', 3);
        if($data["cash_id"]) $result->where('cash_id','=',$data["cash_id"]);
        if($data["cash_date"]) $result->where('cash_date','=',$data["cash_date"]);

        if ($data["dates"]) {
            if (is_array($data["dates"]) && count($data["dates"]) === 2) {
                $startDate = \Carbon\Carbon::parse($data["dates"][0])->startOfDay();
                $endDate   = \Carbon\Carbon::parse($data["dates"][1])->endOfDay();

                $result->whereDate('cash_date', '>=', $startDate->toDateString())
                        ->whereDate('cash_date', '<=', $endDate->toDateString());
            } else {
                $date = \Carbon\Carbon::parse($data["dates"])->toDateString();
                $result->whereDate('cash_date', $date);
            }
        }

        $result->orderBy('status', 'asc')->orderBy('cash_date', 'desc')->orderBy('created_at', 'desc');
        $result = $result->get();

        $armadaCustomerIds = $result
            ->filter(fn ($row) => in_array($row->cash_type, [1, 2], true) && (int) $row->cash_tujuan === 3)
            ->pluck('person_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $armadaByCustomer = $armadaCustomerIds === []
            ? collect()
            : CashArmada::whereIn('customer_id', $armadaCustomerIds)
                ->orderBy('cr_id')
                ->get()
                ->groupBy('customer_id');

        $salesStaffIds = $result
            ->filter(fn ($row) => in_array($row->cash_type, [1, 2, 3], true) && (int) $row->cash_tujuan === 4)
            ->pluck('person_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $salesByStaff = $salesStaffIds === []
            ? collect()
            : CashSales::whereIn('staff_id', $salesStaffIds)
                ->orderBy('cs_id')
                ->get()
                ->groupBy('staff_id');

        $adminCashIds = $result
            ->filter(fn ($row) => in_array($row->cash_type, [1, 2], true) && (int) $row->cash_tujuan === 1)
            ->pluck('cash_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $pengembalianAdminByCashId = $adminCashIds === []
            ? collect()
            : CashAdmin::whereIn('cash_id', $adminCashIds)
                ->where('ca_aksi', 2)
                ->get()
                ->keyBy('cash_id');

        $adminStaffIds = $pengembalianAdminByCashId
            ->pluck('staff_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $adminByStaff = $adminStaffIds === []
            ? collect()
            : CashAdmin::whereIn('staff_id', $adminStaffIds)
                ->orderBy('ca_id')
                ->get()
                ->groupBy('staff_id');

        foreach ($result as $key => $value) {
            // Armada
            if (in_array($value->cash_type, [1, 2]) && $value->cash_tujuan == 3){
                $rows = $armadaByCustomer->get((int) $value->person_id, collect());

                $pengembalianIni = $rows->first(
                    fn ($row) => (int) $row->cash_id === (int) $value->cash_id && (int) $row->cr_aksi === 1
                );

                if (!$pengembalianIni) {
                    continue;
                }

                $pengembalianSebelumnya = $rows
                    ->filter(fn ($row) => (int) $row->cr_aksi === 1
                        && (int) $row->cash_id !== 0
                        && (int) $row->cr_id < (int) $pengembalianIni->cr_id)
                    ->sortByDesc('cr_id')
                    ->first();

                $penyerahanCandidates = $rows->filter(fn ($row) => (int) $row->cr_aksi === 0
                    && (int) $row->status === 2
                    && (int) $row->cash_id === 0
                    && (int) $row->cr_id < (int) $pengembalianIni->cr_id);

                if ($pengembalianSebelumnya) {
                    $penyerahanCandidates = $penyerahanCandidates
                        ->filter(fn ($row) => (int) $row->cr_id > (int) $pengembalianSebelumnya->cr_id);
                }

                $penyerahanPertama = $penyerahanCandidates->sortBy('cr_id')->first();

                if (!$penyerahanPertama && $pengembalianSebelumnya) {
                    $penyerahanPertama = $rows
                        ->filter(fn ($row) => (int) $row->cr_aksi === 0
                            && (int) $row->cash_id === 0
                            && (int) $row->status === 2
                            && (int) $row->cr_id < (int) $pengembalianSebelumnya->cr_id)
                        ->sortByDesc('cr_id')
                        ->first();
                }

                if (!$penyerahanPertama) {
                    if ($pengembalianSebelumnya) {
                        $batasOperasionalCrId = $pengembalianSebelumnya->cr_id;
                    } else {
                        continue;
                    }
                } else {
                    $batasOperasionalCrId = $pengembalianSebelumnya
                        ? max($penyerahanPertama->cr_id, $pengembalianSebelumnya->cr_id)
                        : $penyerahanPertama->cr_id;
                }

                $allPenyerahan = $rows
                    ->filter(fn ($row) => (int) $row->cr_aksi === 0
                        && (int) $row->cash_id === 0
                        && (int) $row->status === 2
                        && (int) $row->cr_id < (int) $pengembalianIni->cr_id
                        && (!$pengembalianSebelumnya || (int) $row->cr_id > (int) $pengembalianSebelumnya->cr_id))
                    ->sortBy('cr_id')
                    ->values();

                if ($allPenyerahan->isEmpty() && $penyerahanPertama) {
                    if (!$pengembalianSebelumnya ||
                        $penyerahanPertama->cr_id > $pengembalianSebelumnya->cr_id) {
                        $allPenyerahan = collect([$penyerahanPertama]);
                    }
                }

                $allOperasional = $rows
                    ->filter(fn ($row) => (int) $row->cr_aksi === 2
                        && (int) $row->cash_id === 0
                        && (int) $row->status === 2
                        && (int) $row->cr_id > (int) $batasOperasionalCrId
                        && (int) $row->cr_id < (int) $pengembalianIni->cr_id)
                    ->sortBy('cr_id')
                    ->values();

                $value->armada_penyerahan  = $allPenyerahan;
                $value->armada_operasional = $allOperasional;
            }

            // Sales
            else if (in_array($value->cash_type, [1, 2, 3]) && $value->cash_tujuan == 4){
                $rows = $salesByStaff->get((int) $value->person_id, collect());

                $pengembalianIni = $rows->first(
                    fn ($row) => (int) $row->cash_id === (int) $value->cash_id
                        && (int) $row->cs_type === 1
                        && (int) $row->cs_aksi === 3
                );

                if (!$pengembalianIni) {
                    continue;
                }

                $pengembalianSebelumnya = $rows
                    ->filter(fn ($row) => (int) $row->cs_type === 1
                        && (int) $row->cs_aksi === 3
                        && (int) $row->cash_id !== 0
                        && (int) $row->status === 2
                        && (int) $row->cs_id < (int) $pengembalianIni->cs_id)
                    ->sortByDesc('cs_id')
                    ->first();

                $penyerahanCandidates = $rows->filter(fn ($row) => (int) $row->cs_type === 1
                    && (int) $row->cs_aksi === 1
                    && (int) $row->cash_id === 0
                    && (int) $row->status === 2
                    && (int) $row->cs_id < (int) $pengembalianIni->cs_id);

                if ($pengembalianSebelumnya) {
                    $penyerahanCandidates = $penyerahanCandidates
                        ->filter(fn ($row) => (int) $row->cs_id > (int) $pengembalianSebelumnya->cs_id);
                }

                $penyerahanPertama = $penyerahanCandidates->sortBy('cs_id')->first();

                if (!$penyerahanPertama && $pengembalianSebelumnya) {
                    $penyerahanPertama = $rows
                        ->filter(fn ($row) => (int) $row->cs_type === 1
                            && (int) $row->cs_aksi === 1
                            && (int) $row->cash_id === 0
                            && (int) $row->status === 2
                            && (int) $row->cs_id < (int) $pengembalianSebelumnya->cs_id)
                        ->sortByDesc('cs_id')
                        ->first();
                }

                if (!$penyerahanPertama) {
                    if ($pengembalianSebelumnya) {
                        $batasOperasionalCsId = $pengembalianSebelumnya->cs_id;
                    } else {
                        continue;
                    }
                } else {
                    $batasOperasionalCsId = $pengembalianSebelumnya
                        ? max($penyerahanPertama->cs_id, $pengembalianSebelumnya->cs_id)
                        : $penyerahanPertama->cs_id;
                }

                $allPenyerahan = $rows
                    ->filter(fn ($row) => (int) $row->cs_type === 1
                        && (int) $row->cs_aksi === 1
                        && (int) $row->status === 2
                        && (int) $row->cash_id === 0
                        && (int) $row->cs_id < (int) $pengembalianIni->cs_id
                        && (!$pengembalianSebelumnya || (int) $row->cs_id > (int) $pengembalianSebelumnya->cs_id))
                    ->sortBy('cs_id')
                    ->values();

                if ($allPenyerahan->isEmpty() && $penyerahanPertama) {
                    if (!$pengembalianSebelumnya ||
                        $penyerahanPertama->cs_id > $pengembalianSebelumnya->cs_id) {
                        $allPenyerahan = collect([$penyerahanPertama]);
                    }
                }

                $allOperasional = $rows
                    ->filter(fn ($row) => (int) $row->cs_type === 2
                        && (int) $row->cash_id === 0
                        && (int) $row->status === 2
                        && (int) $row->cs_id > (int) $batasOperasionalCsId
                        && (int) $row->cs_id < (int) $pengembalianIni->cs_id)
                    ->sortBy('cs_id')
                    ->values();

                $value->sales_penyerahan  = $allPenyerahan;
                $value->sales_operasional = $allOperasional;
            }

            // Admin
            else if (in_array($value->cash_type, [1, 2]) && $value->cash_tujuan == 1) {
                $pengembalianIni = $pengembalianAdminByCashId->get((int) $value->cash_id);

                if (!$pengembalianIni) {
                    continue;
                }

                $rows = $adminByStaff->get((int) $pengembalianIni->staff_id, collect());

                $pengembalianSebelumnya = $rows
                    ->filter(fn ($row) => (int) $row->ca_aksi === 2
                        && (int) $row->cash_id !== 0
                        && (int) $row->ca_id < (int) $pengembalianIni->ca_id)
                    ->sortByDesc('ca_id')
                    ->first();

                $penyerahanCandidates = $rows->filter(fn ($row) => (int) $row->ca_aksi === 1
                    && (int) $row->ca_type === 1
                    && (int) $row->ca_id < (int) $pengembalianIni->ca_id);

                if ($pengembalianSebelumnya) {
                    $penyerahanCandidates = $penyerahanCandidates
                        ->filter(fn ($row) => (int) $row->ca_id > (int) $pengembalianSebelumnya->ca_id);
                }

                $penyerahanPertama = $penyerahanCandidates->sortBy('ca_id')->first();

                if (!$penyerahanPertama && $pengembalianSebelumnya) {
                    $penyerahanPertama = $rows
                        ->filter(fn ($row) => (int) $row->ca_aksi === 1
                            && (int) $row->ca_type === 1
                            && (int) $row->ca_id < (int) $pengembalianSebelumnya->ca_id)
                        ->sortByDesc('ca_id')
                        ->first();
                }

                if (!$penyerahanPertama) {
                    if ($pengembalianSebelumnya) {
                        $batasOperasionalCaId = $pengembalianSebelumnya->ca_id;
                    } else {
                        continue;
                    }
                } else {
                    $batasOperasionalCaId = $pengembalianSebelumnya
                        ? max($penyerahanPertama->ca_id, $pengembalianSebelumnya->ca_id)
                        : $penyerahanPertama->ca_id;
                }

                $allPenyerahan = $rows
                    ->filter(fn ($row) => (int) $row->ca_aksi === 1
                        && (int) $row->ca_type === 1
                        && (int) $row->ca_id < (int) $pengembalianIni->ca_id
                        && (!$pengembalianSebelumnya || (int) $row->ca_id > (int) $pengembalianSebelumnya->ca_id))
                    ->sortBy('ca_id')
                    ->values();

                if ($allPenyerahan->isEmpty() && $penyerahanPertama) {
                    if (!$pengembalianSebelumnya ||
                        $penyerahanPertama->ca_id > $pengembalianSebelumnya->ca_id) {
                        $allPenyerahan = collect([$penyerahanPertama]);
                    }
                }

                $allOperasional = $rows
                    ->filter(fn ($row) => (int) $row->ca_type === 2
                        && (int) $row->ca_id > (int) $batasOperasionalCaId
                        && (int) $row->ca_id < (int) $pengembalianIni->ca_id)
                    ->sortBy('ca_id')
                    ->values();

                $value->admin_penyerahan  = $allPenyerahan;
                $value->admin_operasional = $allOperasional;
            }
        }

        $crIds = [];
        $csIds = [];
        $caIds = [];
        foreach ($result as $value) {
            if (!empty($value->armada_operasional)) {
                foreach ($value->armada_operasional as $row) {
                    $crIds[] = (int) $row->cr_id;
                }
            }
            if (!empty($value->sales_operasional)) {
                foreach ($value->sales_operasional as $row) {
                    $csIds[] = (int) $row->cs_id;
                }
            }
            if (!empty($value->admin_operasional)) {
                foreach ($value->admin_operasional as $row) {
                    $caIds[] = (int) $row->ca_id;
                }
            }
        }

        $armadaDetailsByCrId = $crIds === []
            ? collect()
            : CashArmadaDetail::where('status', 1)
                ->whereIn('cr_id', array_values(array_unique($crIds)))
                ->get()
                ->groupBy('cr_id');

        $salesDetailsByCsId = $csIds === []
            ? collect()
            : CashSalesDetail::where('status', 1)
                ->whereIn('cs_id', array_values(array_unique($csIds)))
                ->get()
                ->groupBy('cs_id');

        $adminDetailsByCaId = $caIds === []
            ? collect()
            : CashAdminDetail::where('status', 1)
                ->whereIn('ca_id', array_values(array_unique($caIds)))
                ->get()
                ->groupBy('ca_id');

        foreach ($result as $value) {
            if (!empty($value->armada_operasional)) {
                foreach ($value->armada_operasional as $row) {
                    $row->detail_armada = $armadaDetailsByCrId->get($row->cr_id, collect())->values();
                }
            }
            if (!empty($value->sales_operasional)) {
                foreach ($value->sales_operasional as $row) {
                    $row->detail_armada = $salesDetailsByCsId->get($row->cs_id, collect())->values();
                }
            }
            if (!empty($value->admin_operasional)) {
                foreach ($value->admin_operasional as $row) {
                    $row->detail_admin = $adminDetailsByCaId->get($row->ca_id, collect())->values();
                }
            }
        }

        $staffNames = BatchLookup::staffNames(
            $result->flatMap(fn ($row) => [$row->created_by, $row->acc_by])
        );

        foreach ($result as $value) {
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
            $value->acc_by_name = $value->acc_by
                ? ($staffNames->get((int) $value->acc_by) ?? '-')
                : '-';
        }
        
        return $result;
    }

    function insertCash($data){
        // DEAD CODE, commented out (confirmed 2026-08-02, marked 2026-08-06 per user decision —
        // not revived, not removed, just flagged): this string→int mapping is the counterpart
        // ReportController::insertCash()'s admin/gudang auto-link branch expects — without it, a
        // literal "admin"/"gudang" string reaching `$t->cash_tujuan` below (an integer column)
        // would crash with a DB type error. Moot in practice: the real frontend (Cash.js) never
        // sends `cash_tujuan` as a string in the first place. See KNOWN_ISSUES.md for the full
        // trace.
        // if ($data['cash_tujuan'] == "admin") $data['cash_tujuan'] = 1;
        // else if ($data['cash_tujuan'] == "gudang") $data['cash_tujuan'] = 2;

        $t = new self();
        $t->person_id = $data["person_id"] ?? 0;
        $t->cash_date = $data["cash_date"];
        $t->cash_description = $data["cash_description"];
        $t->cash_nominal = $data["cash_nominal"];
        $t->cash_type = $data["cash_type"];
        $t->cash_tujuan = $data["cash_tujuan"] ?? 0;
        $t->status = $data['status'] ?? 2;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        
        $t->save();
        return $t->cash_id;
    }

    function updateCash($data){
        $t = Cash::find($data["cash_id"]);
        $t->person_id = $data["person_id"] ?? 0;
        $t->cash_date = $data["cash_date"];
        $t->cash_description = $data["cash_description"];
        $t->cash_nominal = $data["cash_nominal"];
        $t->cash_type = $data["cash_type"];
        $t->cash_tujuan = $data["cash_tujuan"];
        $t->status = $data['status'];

        // Saldo
        // $last = self::orderBy('cash_id', 'desc')->first();
        // $balance = $last ? $last->cash_balance : 0;
        // if ($data['cash_type'] == 1){
        //     $balance += $data['cash_nominal'];
        // } else if ($data['cash_type'] >= 2){
        //     $balance -= $data['cash_nominal'];
        // }
        // $t->cash_balance = $balance;
        
        $t->save();
        return $t->cash_id;
    }

    function deleteCash($data){
        $t = Cash::find($data["cash_id"]);
        $t->status = 0;
        $t->save();
    }
}
