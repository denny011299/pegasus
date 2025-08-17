<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InwardOutward extends Model
{
    protected $table = "inward_outwards";
    protected $primaryKey = "io_id";
    public $timestamps = true;
    public $incrementing = true;

    function getInwardOutward($data = []){
        $data = [
            [
                'io_sku' => 'PT001',
                'io_name' => 'Lenovo IdeaPad 3',
                'io_image' => 'assets/img/products/product-01.png',
                'io_type' => 'Computers',
                'io_unit' => 'In',
                'io_qty' => 100,
                'io_user' => 'Admin',
            ],
            [
                'io_sku' => 'PT002',
                'io_name' => 'Apple Watch 5 Series',
                'io_image' => 'assets/img/products/product-03.png',
                'io_type' => 'Electronics',
                'io_unit' => 'Out',
                'io_qty' => 140,
                'io_user' => 'Admin',
            ],
            [
                'io_sku' => 'PT003',
                'io_name' => 'Nike Jordan',
                'io_image' => 'assets/img/products/product-02.png',
                'io_type' => 'Shoe',
                'io_unit' => 'In',
                'io_qty' => 300,
                'io_user' => 'John',
            ],
        ];
        return $data;
    }
}
