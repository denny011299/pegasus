<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    use HasFactory;
    protected $table = "stock_opnames";
    protected $primaryKey = "stop_id";
    public $timestamps = true;
    public $incrementing = true;
    
    function getStockOpname(){
        $data = [
            [
                "stop_pic" => "Shawn Davies",
                "stop_id" => "OPN001",
                "created_at" => now()
            ],
            [
                "stop_pic" => "Maria Johnson",
                "stop_id" => "OPN002",
                "created_at" => now()
            ]
        ];
        return $data;
    }

    function insertStockOpname($data){
        
    }

    function updateStockOpname($data){

    }

    function deleteStockOpname($data){

    }
}
