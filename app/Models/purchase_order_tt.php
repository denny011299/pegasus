<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class purchase_order_tt extends Model
{
    protected $table = "purchase_order_tts";
    protected $primaryKey = "tt_id";
    public $timestamps = true;
    public $incrementing = true;

    function getTt($data = [])
    {
        $data = array_merge([
            "tt_kode" => null,
            "tt_id" => null
        ], $data);

        $result = self::where('status', '>=', 0);

        if ($data["tt_kode"]) {
            $result->where('tt_kode', 'like', '%' . $data["tt_kode"] . '%');
        }
        if ($data["tt_id"]) $result->where("tt_id", "=", $data["tt_id"]);

        $result->orderByRaw('FIELD(status, 1, 2, 0)')->orderBy('tt_date', 'desc')->orderBy('tt_kode', 'desc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            $s = Supplier::find($value->supplier_id);
            if($s){
                $value->supplier_name = $s->supplier_name;
            }
        }
        return $result;
    }

    function insertTt($data)
    {
        $t = new self();
        $t->tt_date = $data["tt_date"];
        $t->tt_kode = $data["tt_kode"];
        $t->tt_total = $data["tt_total"];
        $t->supplier_id = $data["supplier_id"];
        $t->staff_name = $data["staff_name"];
        $t->save();
        return $t->tt_id;
    }

    function updateTt($data)
    {
        $t = self::find($data["tt_id"]);
        if (!$t) return null;

        $t->tt_date = $data["tt_date"];
        $t->tt_kode = $data["tt_kode"];
        $t->tt_total = $data["tt_total"];
        $t->staff_name = $data["staff_name"];
        $t->save();
        return $t->tt_id;
    }

    function deleteTt($data)
    {
        $t = self::find($data["tt_id"]);
        if ($t) {
            $t->status = 0;
            $t->save();
        }
    }
}
