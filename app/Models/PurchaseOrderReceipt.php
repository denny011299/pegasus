<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderReceipt extends Model
{
    protected $table = "purchase_order_receipts";
    protected $primaryKey = "por_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPoReceipt($data = []){
        $data = [
            [
                'por_id' => 1,
                'por_date' => '2025-08-14',
                'por_ref' => 'DN-0001',
                'por_status' => 1,
                'por_receiver' => "Andi",
            ],
            [
                'por_id' => 2,
                'por_date' => '2025-08-13',
                'por_ref' => 'DN-0002',
                'por_status' => 2,
                'por_receiver' => "Susanti",
            ],
        ];
        return $data;
    }
}
