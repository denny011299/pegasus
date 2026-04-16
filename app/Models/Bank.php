<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Bank extends Model
{
    protected $table = "banks";
    protected $primaryKey = "bank_id";
    public $timestamps = true;
    public $incrementing = true;

    function getBank($data = [])
    {
        $data = array_merge([
            "bank_kode" => null
        ], $data);

        $result = Bank::where('status', '=', 1);

        if ($data["bank_kode"]) {
            $result->where('bank_kode', 'like', '%' . $data["bank_kode"] . '%');
        }

        $result->orderBy('created_at', 'asc');
        $rows = $result->get();
        foreach ($rows as $value) {
            $value->created_by_name = $value->created_by ? (Staff::find($value->created_by)->staff_name ?? '-') : '-';
        }
        return $rows;
    }

    function insertBank($data)
    {
        $t = new Bank();
        $t->bank_kode = $data["bank_kode"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->bank_id;
    }

    function updateBank($data)
    {
        $t = Bank::find($data["bank_id"]);
        $t->bank_kode = $data["bank_kode"];
        $t->save();
        return $t->bank_id;
    }

    function deleteBank($data)
    {
        $t = Bank::find($data["bank_id"]);
        $t->status = 0;
        $t->save();
    }
}
