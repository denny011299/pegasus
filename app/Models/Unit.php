<?php

namespace App\Models;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Unit extends Model
{
    protected $table = "units";
    protected $primaryKey = "unit_id";
    public $timestamps = true;
    public $incrementing = true;

    function getUnit($data = [])
    {
        $data = array_merge([
            "unit_name"=>null,
            "unit_short_name"=>null
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["unit_short_name"]) $result->where('unit_short_name','like','%'.$data["unit_short_name"].'%');
        if($data["unit_name"]) $result->where('unit_name','like','%'.$data["unit_name"].'%');
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            //$value->product_count = Product::where('unit_id', $value->unit_id)->where('status','=',1)->count();
            $value->product_count = 0;
            $value->created_by_name = $value->created_by ? (Staff::find($value->created_by)->staff_name ?? '-') : '-';
        }
        return $result;
    }

    /**
     * Daftar satuan untuk External API.
     *
     * Aturan bisnisnya sama persis dengan getUnit(): hanya baris aktif
     * (status = 1). Yang dibedakan hanya dua hal, dan keduanya disengaja:
     *
     * 1. Tidak ikut mengambil created_by_name. getUnit() memanggil
     *    Staff::find() sekali per baris — wajar untuk satu tabel admin, tapi
     *    itu kueri N+1 untuk data yang justru tidak boleh keluar ke pihak
     *    ketiga (nama staf internal).
     * 2. Urutannya ditambah unit_id sebagai penentu akhir. Ada baris dengan
     *    created_at NULL, sehingga `order by created_at` saja membuat urutan
     *    keluaran tidak menentu — tidak layak untuk kontrak API.
     *
     * Kolom yang dipilih dibatasi di sini, bukan di controller, supaya kolom
     * internal tidak pernah sempat ikut terbaca.
     *
     * Mengembalikan query builder (bukan koleksi sudah dieksekusi) supaya
     * controller bisa memilih ->get() (daftar utuh) atau ->paginate() lewat
     * HandlesOptionalPagination, tanpa menduplikasi penyaringan/urutan di
     * dua tempat.
     */
    function getUnitForExternalApi()
    {
        return self::where('status', '=', 1)
            ->orderBy('created_at', 'asc')
            ->orderBy('unit_id', 'asc')
            ->select(['unit_id', 'ref_unit_id', 'unit_name', 'unit_short_name']);
    }

    function insertUnit($data)
    {
        $t = new self();
        $t->unit_short_name = $data["unit_short_name"];
        $t->unit_name = $data["unit_name"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->unit_id;
    }

    function updateUnit($data)
    {
        $t = self::find($data["unit_id"]);
        $t->unit_short_name = $data["unit_short_name"];
        $t->unit_name = $data["unit_name"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->unit_id;
    }

    function deleteUnit($data)
    {
        $t = self::find($data["unit_id"]);
        $t->status = 0;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
    }
}
