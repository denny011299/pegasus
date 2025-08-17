<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManageStock extends Model
{
    protected $table = "manage_stocks";
    protected $primaryKey = "ms_id";
    public $timestamps = true;
    public $incrementing = true;

    function getManageStock($data = []){
        $data = [
            [
                'ms_date' => '2023-01-08',
                'ms_name' => 'Lenovo Ideapad 3',
                'ms_image' => 'assets/img/products/product-01.png',
                'ms_sku' => 'PT001',
                'ms_product_in' => 20,
                'ms_product_out' => 10,
            ],
            [
                'ms_date' => '2022-12-11',
                'ms_name' => 'Apple Series 5 Watch',
                'ms_image' => 'assets/img/products/product-03.png',
                'ms_sku' => 'PT002',
                'ms_product_in' => 20,
                'ms_product_out' => 10,
            ],
            [
                'ms_date' => '2023-01-17',
                'ms_name' => 'Red Premium Handy',
                'ms_image' => 'assets/img/products/product-04.png',
                'ms_sku' => 'PT006',
                'ms_product_in' => 20,
                'ms_product_out' => 10,
            ],
        ];
        return $data;
    }
}
