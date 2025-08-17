<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplies extends Model
{
    protected $table = "supplies";
    protected $primaryKey = "sup_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSupplies($data = []){
        $data = [
            [
                'sup_id'   => 1,
                'sup_name' => 'Case Samsung Mickey',
                'sup_desc' => 'Casing samsung dengan kualitas print gambar yang bagus',
                'sup_unit' => json_encode(["Dus", "Pcs"]),
                'sup_stock' => 100,
            ],
            [
                'sup_id'   => 2,
                'sup_name' => 'Lenovo Ideapad 3',
                'sup_desc' => 'Laptop dengan desain minimalis dan cakep',
                'sup_unit' => json_encode(["Pcs"]),
                'sup_stock' => 20,
            ],
        ];
        return $data;
    }
}
