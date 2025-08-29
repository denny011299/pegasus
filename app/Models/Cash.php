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
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["cash_id"]) $result->where('cash_id','=',$data["cash_id"]);
        if($data["cash_date"]) $result->where('cash_date','=',$data["cash_date"]);
        $result->orderBy('cash_date', 'desc')->orderBy('created_at', 'desc');
        $result = $result->get();
        
        return $result;
    }

    function insertCash($data){
        $t = new self();
        $t->cash_date = $data["cash_date"];
        $t->cash_description = $data["cash_description"];
        $t->cash_nominal = $data["cash_nominal"];
        $t->cash_type = $data["cash_type"];

        // Saldo
        $last = self::orderBy('cash_id', 'desc')->first();
        $balance = $last ? $last->cash_balance : 0;
        if ($data['cash_type'] == 1){
            $balance += $data['cash_nominal'];
        } else if ($data['cash_type'] >= 2){
            $balance -= $data['cash_nominal'];
        }
        $t->cash_balance = $balance;
        
        $t->save();
        return $t->cash_id;
    }
}
