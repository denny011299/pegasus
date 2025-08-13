<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportProfit extends Model
{
    use HasFactory;
    protected $table = "profits";
    protected $primaryKey = "profit_id";
    public $timestamps = true;
    public $incrementing = true;
    function getProfit($data = []){
        $data = [
            [
                "Name" => "$3,06,386.00",
                "Year1" => "$2,61,738.00",
                "Year2" => "$2,82,463.00",
                "Year3" => "$8,50,587.00",
                "Income" => "Stripe Sales"
            ],
            [
                "Name" => "$3,06,386.00",
                "Year1" => "$2,61,738.00",
                "Year2" => "$2,82,463.00",
                "Year3" => "$8,50,587.00",
                "Income" => "Total Income"
            ]
        ];
        return $data;
    }
}
