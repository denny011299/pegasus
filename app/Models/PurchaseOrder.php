<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $table = "purchase_orders";
    protected $primaryKey = "po_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPurchaseOrder($data = []){
        $data = [
            [
                'po_id' => 1,
                'po_date' => '2025-07-20',
                'po_reference' => 'PO001',
                'po_invoice_number' => 'INV-001',
                'po_name' => 'CV Mitra Sejahtera',
                'po_total' => 9500000,
                'po_status' => 1,
                'po_created_by' => "Dewi",
                'po_payables' => 0,
                'po_paid' => 9500000
            ],
            [
                'po_id' => 2,
                'po_date' => '2025-07-22',
                'po_reference' => 'PO002',
                'po_invoice_number' => 'INV-002',
                'po_name' => 'PT Sumber Elektronik',
                'po_total' => 13250000,
                'po_status' => 2,
                'po_created_by' => "Dewi",
                'po_payables' => 250000,
                'po_paid' => 13000000
            ],
            [
                'po_id' => 3,
                'po_date' => '2025-07-25',
                'po_reference' => 'PO003',
                'po_invoice_number' => 'INV-003',
                'po_name' => 'UD Sinar Logam',
                'po_total' => 7800000,
                'po_status' => 2,
                'po_created_by' => "Dewi",
                'po_payables' => 7800000,
                'po_paid' => 0
            ],
        ];
        return $data;
    }
}
