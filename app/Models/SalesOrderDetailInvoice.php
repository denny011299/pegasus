<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderDetailInvoice extends Model
{
    protected $table = "sales_order_invoices";
    protected $primaryKey = "soi_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSoInvoice($data = []){
        $data = [
            [
                'soi_id' => 1,
                'soi_date' => '2025-08-14',
                'soi_due' => '2025-08-21',
                'soi_code' => 'INV-001',
                'soi_status' => 1,
                'soi_total' => 100000,
            ],
            [
                'soi_id' => 2,
                'soi_date' => '2025-08-13',
                'soi_due' => '2025-08-16',
                'soi_code' => 'INV-002',
                'soi_status' => 1,
                'soi_total' => 500000,
            ],
        ];
        return $data;
    }
}
