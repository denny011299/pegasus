<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class CashCategory extends Model
{
    protected $table = "cash_categories";
    protected $primaryKey = "cc_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCashCategory($data = [])
    {

        $data = array_merge([
            "cc_name"=>null
        ], $data);

        $result = CashCategory::where('status', '=', 1);
        if($data["cc_name"]) $result->where('cc_name','like','%'.$data["cc_name"].'%');
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            $value->created_by_name = $value->created_by ? (Staff::find($value->created_by)->staff_name ?? '-') : '-';
        }
        return $result;
    }

    function insertCashCategory($data)
    {
        $t = new CashCategory();
        $t->cc_name = $data["cc_name"];
        $t->cc_type = $data["cc_type"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->cc_id;
    }

    function updateCashCategory($data)
    {
        $t = CashCategory::find($data["cc_id"]);
        $t->cc_name = $data["cc_name"];
        $t->cc_type = $data["cc_type"];
        $t->save();
        return $t->cc_id;
    }

    function deleteCashCategory($data)
    {
        $t = CashCategory::find($data["cc_id"]);
        $t->status = 0;
        $t->save();
    }
}
