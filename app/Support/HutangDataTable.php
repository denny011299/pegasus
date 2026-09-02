<?php

namespace App\Support;

use App\Models\PurchaseOrderDetailInvoice;

class HutangDataTable
{
    public static function paginate(array $data): array
    {
        $dt = DataTableParams::from($data);

        $recordsTotal = (int) PurchaseOrderDetailInvoice::queryPoInvoice([
            'poi_id' => null,
            'po_id' => null,
            'bank_id' => null,
            'status' => null,
            'po_supplier' => null,
            'dates' => null,
        ])->count('purchase_order_detail_invoices.poi_id');

        $filtered = PurchaseOrderDetailInvoice::queryPoInvoice($data);
        $recordsFiltered = (int) (clone $filtered)->count('purchase_order_detail_invoices.poi_id');

        $sumRow = (clone $filtered)
            ->selectRaw('COALESCE(SUM(purchase_order_detail_invoices.poi_total), 0) as total_hutang')
            ->first();
        $totalHutang = (int) ($sumRow->total_hutang ?? 0);

        $rows = PurchaseOrderDetailInvoice::applyPoInvoiceOrder(clone $filtered)
            ->select('purchase_order_detail_invoices.*')
            ->skip($dt['start'])
            ->take($dt['length'])
            ->get();

        PurchaseOrderDetailInvoice::enrichPoInvoiceRows($rows);

        return [
            'draw' => $dt['draw'],
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows->values()->all(),
            'meta' => [
                'total_invoice' => $recordsFiltered,
                'total_hutang' => $totalHutang,
            ],
        ];
    }
}
