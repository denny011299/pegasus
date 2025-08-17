<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SupplierController extends Controller
{
    function getPurchaseOrder(Request $req){
        $data = [
            [
                'po_date' => '2025-07-20',
                'po_number' => 'PO-2025-001',
                'invoice_number' => 'INV-PO-001',
                'supplier_name' => 'CV Mitra Sejahtera',
                'total' => 9500000,
                'status' => 2
            ],
            [
                'po_date' => '2025-07-22',
                'po_number' => 'PO-2025-002',
                'invoice_number' => 'INV-PO-002',
                'supplier_name' => 'PT Sumber Elektronik',
                'total' => 13250000,
                'status' => 2
            ],
            [
                'po_date' => '2025-07-25',
                'po_number' => 'PO-2025-003',
                'invoice_number' => 'INV-PO-003',
                'supplier_name' => 'UD Sinar Logam',
                'total' => 7800000,
                'status' => 3
            ],
        ];
        return response()->json($data);
    }
}
