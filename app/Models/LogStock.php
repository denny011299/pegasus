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
        ], $data);

        $result = LogStock::where('log_notes','like','%'.$data["log_notes"].'%');
        if($data["log_item_id"])$result->where('log_item_id','=',$data["log_item_id"]);
        $result->orderBy('created_at', 'asc');
       
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
        $t->log_item_id = $data["log_item_id"];
        $t->log_notes = $data["log_notes"];
        $t->log_jumlah = $data["log_jumlah"];
        $t->unit_id = $data["unit_id"];
        $t->save();
        return $t->log_id;
    }

    function updateLog($data)
    {
        $t = LogStock::find($data["log_id"]);
        $t->log_date = $data["log_date"];
        $t->log_kode = $data["log_kode"];
        $t->log_item_id = $data["log_item_id"];
        $t->log_notes = $data["log_notes"];
        $t->log_jumlah = $data["log_jumlah"];
        $t->unit_id = $data["unit_id"];
        $t->save();
        return $t->log_id;
    }

    function deleteLog($data)
    {
        $t = LogStock::find($data["log_id"]);
        $t->status = 0;
        $t->save();
    }
}
