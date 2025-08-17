<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    protected $table = "stocks";
    protected $primaryKey = "stk_id";
    public $timestamps = true;
    public $incrementing = true;

    function getStock($data = []){
        $data = [
            [
                'stk_id'   => 0001,
                'stk_name' => 'Case Samsung Mickey',
                'stk_sku' => 'PT002',
                'stk_category' => 'Elektronik',
                'stk_stock' => 100,
                'stk_merk' => 'Samsung',
                'stk_unit' => json_encode(["Dus", "Pcs"]),
                'stk_variant' => json_encode(["Merah", "Kuning", "Hitam"]),
                'stk_barcode' => 'assets/img/barcodes/barcode-01.png',
            ],
            [
                'stk_id'   => 0002,
                'stk_name' => 'Lenovo Ideapad 3',
                'stk_sku' => 'PT008',
                'stk_category' => 'Elektronik',
                'stk_stock' => 20,
                'stk_merk' => 'Lenovo',
                'stk_unit' => json_encode(["Pcs"]),
                'stk_variant' => json_encode(["Putih", "Hitam"]),
                'stk_barcode' => 'assets/img/barcodes/barcode-02.png',
            ],
        ];
        return $data;
    }
}
