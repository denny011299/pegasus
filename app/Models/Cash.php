<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

        $result->orderBy('cash_date', 'desc')->orderBy('status', 'asc')->orderBy('created_at', 'desc');
        $result = $result->get();

        foreach ($result as $key => $value) {
            // Armada
            if ($value->cash_type == 1 && $value->cash_tujuan == 3){

                $pengembalianIni = CashArmada::where('cash_id', $value->cash_id)
                    ->where('customer_id', $value->person_id)
                    ->where('cr_aksi', 1)
                    ->first();

                if (!$pengembalianIni) continue;

                $pengembalianSebelumnya = CashArmada::where('customer_id', $value->person_id)
                    ->where('cr_aksi', 1)
                    ->where('cash_id', '!=', 0)
                    ->where('cr_id', '<', $pengembalianIni->cr_id)
                    ->orderBy('cr_id', 'desc')
                    ->first();

                $penyerahanPertama = CashArmada::where('customer_id', $value->person_id)
                    ->where('cr_aksi', 0)
                    ->where('status', 2)
                    ->where('cash_id', 0)
                    ->when($pengembalianSebelumnya, function($q) use ($pengembalianSebelumnya) {
                        $q->where('cr_id', '>', $pengembalianSebelumnya->cr_id);
                    })
                    ->where('cr_id', '<', $pengembalianIni->cr_id)
                    ->orderBy('cr_id', 'asc')
                    ->first();

                // ← GANTI if (!$penyerahanPertama) continue; DENGAN INI
                if (!$penyerahanPertama && $pengembalianSebelumnya) {
                    $penyerahanPertama = CashArmada::where('customer_id', $value->person_id)
                        ->where('cr_aksi', 0)
                        ->where('cash_id', 0)
                        ->where('status', 2)
                        ->where('cr_id', '<', $pengembalianSebelumnya->cr_id)
                        ->orderBy('cr_id', 'desc')
                        ->first();
                }

                if (!$penyerahanPertama) continue;

                // Penyerahan — hanya yang baru di siklus ini
                $allPenyerahan = CashArmada::where('customer_id', $value->person_id)
                    ->where('cr_aksi', 0)
                    ->where('cash_id', 0)
                    ->where('status', 2)
                    ->when($pengembalianSebelumnya, function($q) use ($pengembalianSebelumnya) {
                        $q->where('cr_id', '>', $pengembalianSebelumnya->cr_id);
                    })
                    ->where('cr_id', '<', $pengembalianIni->cr_id)
                    ->orderBy('cr_id', 'asc')
                    ->get();

                // Kalau tidak ada penyerahan baru, tampilkan penyerahanPertama
                if ($allPenyerahan->isEmpty()) {
                    $allPenyerahan = collect([$penyerahanPertama]);
                }

                // Semua operasional dari penyerahanPertama sampai pengembalianIni
                $allOperasional = CashArmada::where('customer_id', $value->person_id)
                    ->where('cr_aksi', 2)
                    ->where('cash_id', 0)
                    ->where('status', 2)
                    ->where('cr_id', '>', $penyerahanPertama->cr_id)
                    ->where('cr_id', '<', $pengembalianIni->cr_id)
                    ->orderBy('cr_id', 'asc')
                    ->get();

                foreach ($allOperasional as $val) {
                    $val->detail_armada = CashArmadaDetail::where('cr_id', $val->cr_id)
                        ->where('status', 1)
                        ->get()
                        ->pluck('crd_notes')
                        ->implode(', ');
                }

                $value->armada_penyerahan  = $allPenyerahan;
                $value->armada_operasional = $allOperasional;
            }

            // Sales
            else if ($value->cash_type == 3 && $value->cash_tujuan == 4){

                $pengembalianIni = CashSales::where('cash_id', $value->cash_id)
                    ->where('staff_id', $value->person_id)
                    ->where('cs_type', 1)
                    ->whereIn('cs_aksi', [2, 3])
                    ->first();

                if (!$pengembalianIni) continue;

                $pengembalianSebelumnya = CashSales::where('staff_id', $value->person_id)
                    ->where('cs_type', 1)
                    ->whereIn('cs_aksi', [2, 3])
                    ->where('cash_id', '!=', 0)
                    ->where('cs_id', '<', $pengembalianIni->cs_id)
                    ->where('status', 2)
                    ->orderBy('cs_id', 'desc')
                    ->first();

                $penyerahanPertama = CashSales::where('staff_id', $value->person_id)
                    ->where('cs_type', 1)
                    ->where('cs_aksi', 1)
                    ->where('cash_id', 0)
                    ->where('status', 2)
                    ->when($pengembalianSebelumnya, function($q) use ($pengembalianSebelumnya) {
                        $q->where('cs_id', '>', $pengembalianSebelumnya->cs_id);
                    })
                    ->where('cs_id', '<', $pengembalianIni->cs_id)
                    ->orderBy('cs_id', 'asc')
                    ->first();

                // ← GANTI if (!$penyerahanPertama) continue; DENGAN INI
                if (!$penyerahanPertama && $pengembalianSebelumnya) {
                    $penyerahanPertama = CashSales::where('staff_id', $value->person_id)
                        ->where('cs_type', 1)
                        ->where('cs_aksi', 1)
                        ->where('cash_id', 0)
                        ->where('status', 2)
                        ->where('cs_id', '<', $pengembalianSebelumnya->cs_id)
                        ->orderBy('cs_id', 'desc')
                        ->first();
                }

                if (!$penyerahanPertama) continue;

                // Penyerahan — hanya yang baru di siklus ini
                $allPenyerahan = CashSales::where('staff_id', $value->person_id)
                    ->where('cs_type', 1)
                    ->where('cs_aksi', 1)
                    ->where('status', 2)
                    ->where('cash_id', 0)
                    ->when($pengembalianSebelumnya, function($q) use ($pengembalianSebelumnya) {
                        $q->where('cs_id', '>', $pengembalianSebelumnya->cs_id);
                    })
                    ->where('cs_id', '<', $pengembalianIni->cs_id)
                    ->orderBy('cs_id', 'asc')
                    ->get();

                // Kalau tidak ada penyerahan baru, tampilkan penyerahanPertama
                if ($allPenyerahan->isEmpty()) {
                    $allPenyerahan = collect([$penyerahanPertama]);
                }

                // Semua operasional dari penyerahanPertama sampai pengembalianIni
                $allOperasional = CashSales::where('staff_id', $value->person_id)
                    ->where('cs_type', 2)
                    ->where('cash_id', 0)
                    ->where('status', 2)
                    ->where('cs_id', '>', $penyerahanPertama->cs_id)
                    ->where('cs_id', '<', $pengembalianIni->cs_id)
                    ->orderBy('cs_id', 'asc')
                    ->get();

                foreach ($allOperasional as $val) {
                    $val->detail_armada = CashSalesDetail::where('cs_id', $val->cs_id)
                        ->where('status', 1)
                        ->get()
                        ->pluck('csd_notes')
                        ->implode(', ');
                }

                $value->sales_penyerahan  = $allPenyerahan;
                $value->sales_operasional = $allOperasional;
            }
        }
        
        return $result;
    }

    function insertCash($data){
        if ($data['cash_tujuan'] == "admin") $data['cash_tujuan'] = 1;
        else if ($data['cash_tujuan'] == "gudang") $data['cash_tujuan'] = 2;
        
        $t = new self();
        $t->person_id = $data["person_id"] ?? 0;
        $t->cash_date = $data["cash_date"];
        $t->cash_description = $data["cash_description"];
        $t->cash_nominal = $data["cash_nominal"];
        $t->cash_type = $data["cash_type"];
        $t->cash_tujuan = $data["cash_tujuan"];
        $t->status = $data['status'] ?? 2;
        
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
