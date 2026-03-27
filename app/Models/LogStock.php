<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class LogStock extends Model
{
    protected $table = "log_stocks";
    protected $primaryKey = "log_id";
    public $timestamps = true;
    public $incrementing = true;

    function getLog($data = [])
    {

        $data = array_merge([
            "log_notes"=>null,
            "log_type"=>null,
            "log_item_id"=>null,
            "date"=>null
        ], $data);

        $result = LogStock::where('status', '=', 1);
        if($data['log_notes'])$result->where('log_notes','like','%'.$data["log_notes"].'%');
        if($data["log_type"])$result->where('log_type','=',$data["log_type"]);
        if($data["log_item_id"])$result->where('log_item_id','=',$data["log_item_id"]);

        if ($data["date"]) {
            if (is_array($data["date"]) && count($data["date"]) === 2) {
                $startDate = \Carbon\Carbon::parse($data["date"][0])->startOfDay();
                $endDate   = \Carbon\Carbon::parse($data["date"][1])->endOfDay();

                $result->whereBetween('log_date', [$startDate, $endDate]);
            } else {
                $date = \Carbon\Carbon::parse($data["date"])->toDateString();
                $result->whereDate('log_date', $date);
            }
        }

        $result->orderBy('created_at', 'desc')->orderBy('log_id', 'desc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            $u = Unit::find($value->unit_id);
            $value->unit_name = $u->unit_name;
            if($value->staff_id){
                try {
                    $value->staff_name =Staff::find($value->staff_id)->staff_name; 
                } catch (\Throwable $th) {
                     $value->staff_name ="-"; 
                }
            }
            else{
                $value->staff_name ="-"; 
            }
        }
        return $result;
    }

    function insertLog($data)
    {
        $t = new LogStock();
        $t->log_date = $data["log_date"];
        $t->log_kode = $data["log_kode"];
        $t->log_type = $data["log_type"];
        $t->log_category = $data["log_category"];
        $t->log_item_id = $data["log_item_id"];
        $t->log_notes = $data["log_notes"];
        $t->log_jumlah = $data["log_jumlah"];
        $t->unit_id = $data["unit_id"];
        $t->staff_id = Session::get('user')->staff_id;
        $t->save();
        return $t->log_id;
    }

    function getRawMaterialUsageReport($data = [])
    {
        $data = array_merge([
            "date" => null,
            "supplier_id" => null,
            "supplies_id" => null
        ], $data);

        $query = DB::table('log_stocks as l')
            ->leftJoin('supplies as s', 's.supplies_id', '=', 'l.log_item_id')
            ->leftJoin('units as u', 'u.unit_id', '=', 'l.unit_id')
            ->leftJoin('productions as p', 'p.production_code', '=', 'l.log_kode')
            ->where('l.status', 1)
            ->where('l.log_type', 2)
            ->where('l.log_category', 2)
            ->where('l.log_notes', 'like', '%Pengurangan bahan untuk produksi%')
            ->select(
                'l.log_id',
                'l.log_date',
                'l.log_kode',
                'l.log_item_id',
                'l.log_jumlah',
                'l.log_notes',
                'u.unit_name',
                's.supplies_name',
                'p.production_date'
            );

        if ($data["date"]) {
            if (is_array($data["date"]) && count($data["date"]) === 2) {
                $startDate = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][0])->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][1])->endOfDay();
                $query->whereBetween('l.log_date', [$startDate, $endDate]);
            } else {
                $date = $data["date"];
                if (!\Carbon\Carbon::hasFormat($data["date"], 'Y-m-d')) {
                    $date = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"])->format('Y-m-d');
                }
                $query->whereDate('l.log_date', $date);
            }
        }

        if ($data["supplies_id"]) {
            $query->where('l.log_item_id', $data["supplies_id"]);
        }

        if ($data["supplier_id"]) {
            $supplierSupplies = DB::table('supplies_variants')
                ->where('supplier_id', $data["supplier_id"])
                ->pluck('supplies_id')
                ->unique()
                ->toArray();

            if (count($supplierSupplies) <= 0) return [];
            $query->whereIn('l.log_item_id', $supplierSupplies);
        }

        $rows = $query->orderBy('l.log_date', 'desc')->orderBy('l.log_id', 'desc')->get();

        $grouped = [];
        foreach ($rows as $row) {
            $key = $row->log_item_id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    "supplies_id" => $row->log_item_id,
                    "item_name" => $row->supplies_name ?? '-',
                    "transaction_count" => 0,
                    "details" => []
                ];
            }

            $grouped[$key]["transaction_count"] += 1;
            $grouped[$key]["details"][] = [
                "production_date" => $row->production_date ?: date('Y-m-d', strtotime($row->log_date)),
                "log_date" => $row->log_date,
                "production_code" => $row->log_kode,
                "qty" => (int)$row->log_jumlah,
                "unit_name" => $row->unit_name,
                "notes" => $row->log_notes
            ];
        }

        return array_values($grouped);
    }
}
