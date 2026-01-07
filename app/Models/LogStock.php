<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
            "log_item_id"=>null,
            "date"=>null
        ], $data);

        $result = LogStock::where('log_notes','like','%'.$data["log_notes"].'%');
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
        }
        return $result;
    }

    function insertLog($data)
    {
        $t = new LogStock();
        $t->log_date = $data["log_date"];
        $t->log_kode = $data["log_kode"];
        $t->log_category = $data["log_category"];
        $t->log_item_id = $data["log_item_id"];
        $t->log_notes = $data["log_notes"];
        $t->log_jumlah = $data["log_jumlah"];
        $t->unit_id = $data["unit_id"];
        $t->save();
        return $t->log_id;
    }
}
