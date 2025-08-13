<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAlert extends Model
{
    use HasFactory;
    protected $table = "stock_alerts";
    protected $primaryKey = "stal_id";
    public $timestamps = true;
    public $incrementing = true;

    function getStockAlert($data = []){
        $data = [
            [
                "stal_name" => "Lenovo 3rd Generation",
                "stal_image" => "assets/img/products/product-01.png",
                "stal_category" => "Laptop",
                "stal_sku" => "PT001",
                "stal_stock" => 15,
                "stal_qty" => 10
            ],
            [
                "stal_name" => "Apple Series 5 Watch",
                "stal_image" => "assets/img/products/product-02.png",
                "stal_category" => "Jam",
                "stal_sku" => "PT002",
                "stal_stock" => 0,
                "stal_qty" => 5
            ]
        ];
        return $data;
    }

    function insertStockAlert($data){
        
    }

    function updateStockAlert($data){

    }

    function deleteStockAlert($data){

    }
}
