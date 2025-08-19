<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $table = "sales_orders";
    protected $primaryKey = "so_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSalesOrder($data = []){
        $data = [
            [
                'so_id' => 1,
                'so_order_date' => '2025-07-20',
                'so_due_date' => '2025-07-31',
                'so_reference' => 'SO002',
                'so_invoice_number' => 'INV-002',
                'so_name' => 'CV Maju Lancar',
                'so_total' => 8750000,
                'so_status' => 2,
                'so_paid' => 0,
                'so_difference' => 0,
                'so_cashier' => 1
            ],
            [
                'so_id' => 2,
                'so_order_date' => '2025-07-25',
                'so_due_date' => '2025-08-10',
                'so_reference' => 'SO001',
                'so_invoice_number' => 'INV-001',
                'so_name' => 'PT Sinar Abadi',
                'so_total' => 12500000,
                'so_status' => 1,
                'so_paid' => 0,
                'so_difference' => 0,
                'so_cashier' => 1
            ],
            [
                'so_id' => 3,
                'so_order_date' => '2025-07-28',
                'so_due_date' => '2025-08-15',
                'so_reference' => 'SO003',
                'so_invoice_number' => 'INV-003',
                'so_name' => 'PT Berkah Jaya',
                'so_total' => 15000000,
                'so_status' => 3,
                'so_paid' => 0,
                'so_difference' => 0,
                'so_cashier' => -1
            ],
        ];
        return $data;

    }
}
