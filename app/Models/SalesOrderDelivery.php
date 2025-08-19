<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderDelivery extends Model
{
    protected $table = "sales_order_deliveries";
    protected $primaryKey = "sod_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSoDelivery($data = []){
        $data = [
            [
                'sod_id' => 1,
                'sod_date' => '2025-07-20',
                'sod_receiver' => 'Andi',
                'sod_address' => 'Jl Maju Lancar 1 no.14',
            ],
            [
                'sod_id' => 2,
                'sod_date' => '2025-08-10',
                'sod_receiver' => 'Susanti',
                'sod_address' => 'Jl Jaya Negara 2 no.18',
            ],
        ];
        return $data;
    }
}
