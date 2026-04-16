<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class PettyCash extends Model
{
    protected $table = "petty_cashes";
    protected $primaryKey = "pc_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPettyCash($data = []){
        $data = array_merge([
            "pc_id"=>null,
            "pc_date"=>null,
            "staff_id"=>null,
            "cc_id"=>null,
            "filter_tanggal_start"=>null,
            "filter_tanggal_end"=>null,
        ], $data);

        $result = self::where('status', '>=', 1);
        if($data["pc_id"]) $result->where('pc_id','=',$data["pc_id"]);
        if($data["pc_date"]) $result->where('pc_date','=',$data["pc_date"]);
        if($data["staff_id"]) $result->where('staff_id','=',$data["staff_id"]);
        if($data["cc_id"]) $result->where('cc_id','=',$data["cc_id"]);
        if($data["filter_tanggal_start"]&& $data["filter_tanggal_end"]){
            $result->whereBetween('pc_date', [$data["filter_tanggal_start"], $data["filter_tanggal_end"]]);
        }
        $result->orderBy('status', 'asc')->orderBy('pc_date', 'desc')->orderBy('created_at', 'desc');
        $result = $result->get();


        foreach ($result as $key => $value) {
            $s = Staff::find($value->staff_id);
            $value->staff_name = $s->staff_name;
            $value->created_by_name = $value->created_by ? (Staff::find($value->created_by)->staff_name ?? '-') : '-';
            $value->acc_by_name = $value->acc_by ? (Staff::find($value->acc_by)->staff_name ?? '-') : '-';
        }

        $balance = 0;
        $reversed = $result->reverse()->values();
        foreach ($reversed as $key => $value) {
            if ($value->pc_type == 1){
                $balance += $value->pc_nominal;
            } else if ($value->pc_type == 2){
                $balance -= $value->pc_nominal;
            }
            $value->balance = $balance;
        }
        
        return $result;
    }

    function insertPettyCash($data){
        $t = new self();
        $t->pc_date = $data["pc_date"];
        $t->staff_id = $data["staff_id"];
        $t->pc_description = $data["pc_description"];
        $t->pc_nominal = $data["pc_nominal"];
        $t->pc_type = $data["pc_type"];
        $t->cc_id = $data["cc_id"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        
        $t->save();
        return $t->pc_id;
    }
}