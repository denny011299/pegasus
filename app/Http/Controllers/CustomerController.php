<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CustomerController extends Controller
{
    function getSalesOrder(Request $req){
        $data = [
            [
                'order_date' => '2025-07-20',
                'due_date' => '2025-07-31',
                'so_number' => 'SO-1002',
                'invoice_number' => 'INV-2025-002',
                'customer_name' => 'CV Maju Lancar',
                'total' => 8750000,
                'status' => 2
            ],
            [
                'order_date' => '2025-07-25',
                'due_date' => '2025-08-10',
                'so_number' => 'SO-1001',
                'invoice_number' => 'INV-2025-001',
                'customer_name' => 'PT Sinar Abadi',
                'total' => 12500000,
                'status' => 1
            ],
            [
                'order_date' => '2025-07-28',
                'due_date' => '2025-08-15',
                'so_number' => 'SO-1003',
                'invoice_number' => 'INV-2025-003',
                'customer_name' => 'PT Berkah Jaya',
                'total' => 15000000,
                'status' => 3
            ],
        ];
        return response()->json($data);
    }
}
