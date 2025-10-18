<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        ], $data);

        $result = self::where('status', '>=', 1);
        if($data["pc_id"]) $result->where('pc_id','=',$data["pc_id"]);
        if($data["pc_date"]) $result->where('pc_date','=',$data["pc_date"]);
        $result->orderBy('status', 'asc')->orderBy('pc_date', 'desc')->orderBy('created_at', 'desc');
        $result = $result->get();

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
        $t->staff_id = 1;
        $t->pc_description = $data["pc_description"];
        $t->pc_nominal = $data["pc_nominal"];
        $t->pc_type = $data["pc_type"];

        // Saldo
        $last = self::orderBy('pc_id', 'desc')->first();
        $balance = $last ? $last->pc_balance : 0;
        if ($data['pc_type'] == 1){
            $balance += $data['pc_nominal'];
        } else if ($data['pc_type'] == 2){
            $balance -= $data['pc_nominal'];
        }
        $t->pc_balance = $balance;
        
        $t->save();
        return $t->pc_id;
    }
}