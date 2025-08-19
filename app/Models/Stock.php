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
                'stk_variant' => "Merah",
                'stk_sku' => 'PT002',
                'stk_category' => 'Elektronik',
                'stk_stock' => 100,
                'stk_merk' => 'Samsung',
            ],
            [
                'stk_id'   => 0001,
                'stk_name' => 'Case Samsung Mickey',
                'stk_variant' => "Kuning",
                'stk_sku' => 'PT002',
                'stk_category' => 'Elektronik',
                'stk_stock' => 100,
                'stk_merk' => 'Samsung',
            ],
            [
                'stk_id'   => 0001,
                'stk_name' => 'Case Samsung Mickey',
                'stk_variant' => "Hitam",
                'stk_sku' => 'PT002',
                'stk_category' => 'Elektronik',
                'stk_stock' => 100,
                'stk_merk' => 'Samsung',
            ],
            [
                'stk_id'   => 0002,
                'stk_name' => 'Lenovo Ideapad 3',
                'stk_variant' => "Putih",
                'stk_sku' => 'PT008',
                'stk_category' => 'Elektronik',
                'stk_stock' => 20,
                'stk_merk' => 'Lenovo',
            ],
            [
                'stk_id'   => 0002,
                'stk_name' => 'Lenovo Ideapad 3',
                'stk_variant' => "Hitam",
                'stk_sku' => 'PT008',
                'stk_category' => 'Elektronik',
                'stk_stock' => 20,
                'stk_merk' => 'Lenovo',
            ],
        ];
        return $data;
    }
}
