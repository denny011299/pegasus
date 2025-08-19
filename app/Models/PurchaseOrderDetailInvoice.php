<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetailInvoice extends Model
{
    protected $table = "purchase_order_invoices";
    protected $primaryKey = "poi_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPoInvoice($data = []){
        $data = [
            [
                'poi_id' => 1,
                'poi_date' => '2025-08-14',
                'poi_code' => 'INV-001',
                'poi_status' => 1,
                'poi_total' => 100000,
            ],
            [
                'poi_id' => 2,
                'poi_date' => '2025-08-13',
                'poi_code' => 'INV-002',
                'poi_status' => 2,
                'poi_total' => 500000,
            ],
        ];
        return $data;
    }
}
