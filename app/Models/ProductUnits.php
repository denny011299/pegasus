<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductUnits extends Model
{
    use HasFactory;
    protected $table = "units";
    protected $primaryKey = "unit_id";
    public $timestamps = true;
    public $incrementing = true;

    function getUnit($data = []){
        $data = [
            [
                "unit_id" => 1,
                "unit_name" => "Kilogram",
                "unit_short_name" => "Kg",
                "product_count" => 2,
                "unit_date" => now(),
            ],
            [
                "unit_id" => 2,
                "unit_name" => "Liter",
                "unit_short_name" => "L",
                "product_count" => 3,
                "unit_date" => now(),
            ],
            [
                "unit_id" => 3,
                "unit_name" => "Pcs",
                "unit_short_name" => "Pcs",
                "product_count" => 1,
                "unit_date" => now(),
            ],
            [
                "unit_id" => 4,
                "unit_name" => "Meter",
                "unit_short_name" => "M",
                "product_count" => 5,
                "unit_date" => now(),
            ],
            [
                "unit_id" => 5,
                "unit_name" => "Dus",
                "unit_short_name" => "Dus",
                "product_count" => 2,
                "unit_date" => now(),
            ],
        ];

        return $data;
    }

    function insertUnit($data){
        
    }

    function updateUnit($data){

    }

    function deleteUnit($data){

    }
}
