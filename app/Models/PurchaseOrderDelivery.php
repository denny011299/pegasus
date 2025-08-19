<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDelivery extends Model
{
    protected $table = "purchase_order_deliveries";
    protected $primaryKey = "pod_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPoDelivery($data = []){
        $data = [
            [
                'pod_id' => 1,
                'pod_date' => '2025-07-20',
                'pod_receiver' => 'Andi',
                'pod_address' => 'Jl Maju Lancar 1 no.14',
                'pod_phone' => '082516727346',
                'pod_number' => "DN-0001",
                'pod_status' => 1
            ],
            [
                'pod_id' => 2,
                'pod_date' => '2025-08-10',
                'pod_receiver' => 'Susanti',
                'pod_address' => 'Jl Jaya Negara 2 no.18',
                'pod_phone' => '081857238874',
                'pod_number' => "DN-0002",
                'pod_status' => 2
            ],
        ];
        return $data;
    }
}
