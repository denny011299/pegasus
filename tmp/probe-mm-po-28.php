<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Warehouses ===\n";
foreach (DB::table('warehouses')->where('status', 1)->get(['id', 'warehouse_name', 'warehouse_type_id']) as $w) {
    echo "WH{$w->id} {$w->warehouse_name} type={$w->warehouse_type_id}\n";
}

echo "\n=== MM suppliers ===\n";
$suppliers = DB::table('suppliers')
    ->where('supplier_name', 'like', '%MM%')
    ->orWhere('supplier_name', 'like', '%mm%')
    ->get(['supplier_id', 'supplier_name', 'status']);
foreach ($suppliers as $s) {
    echo "S{$s->supplier_id} {$s->supplier_name} status={$s->status}\n";
}

$ids = $suppliers->pluck('supplier_id')->all();
if ($ids === []) {
    echo "No MM suppliers\n";
    exit(0);
}

echo "\n=== PO around 2026-09-28 (and 2025) ===\n";
$pos = DB::table('purchase_orders as po')
    ->join('suppliers as s', 's.supplier_id', '=', 'po.po_supplier')
    ->whereIn('po.po_supplier', $ids)
    ->where(function ($q) {
        $q->whereBetween('po.po_date', ['2026-09-27', '2026-09-29'])
            ->orWhereBetween('po.created_at', ['2026-09-27 00:00:00', '2026-09-29 23:59:59'])
            ->orWhereBetween('po.po_date', ['2025-09-27', '2025-09-29'])
            ->orWhereBetween('po.created_at', ['2025-09-27 00:00:00', '2025-09-29 23:59:59']);
    })
    ->orderByDesc('po.po_id')
    ->get([
        'po.po_id', 'po.po_number', 'po.po_date', 'po.status', 'po.created_at',
        's.supplier_name', 'po.acc_by',
    ]);

foreach ($pos as $p) {
    echo "PO{$p->po_id} {$p->po_number} date={$p->po_date} status={$p->status} created={$p->created_at} supplier={$p->supplier_name}\n";
}

if ($pos->isEmpty()) {
    echo "No PO found on that window; listing latest 10 MM POs:\n";
    $pos = DB::table('purchase_orders as po')
        ->join('suppliers as s', 's.supplier_id', '=', 'po.po_supplier')
        ->whereIn('po.po_supplier', $ids)
        ->orderByDesc('po.po_id')
        ->limit(10)
        ->get([
            'po.po_id', 'po.po_number', 'po.po_date', 'po.status', 'po.created_at',
            's.supplier_name',
        ]);
    foreach ($pos as $p) {
        echo "PO{$p->po_id} {$p->po_number} date={$p->po_date} status={$p->status} created={$p->created_at} supplier={$p->supplier_name}\n";
    }
}

foreach ($pos as $p) {
    echo "\n--- Details PO{$p->po_id} {$p->po_number} ---\n";
    $details = DB::table('purchase_orders_details as pod')
        ->leftJoin('supplies_variants as sv', 'sv.supplies_variant_id', '=', 'pod.supplies_variant_id')
        ->leftJoin('supplies as su', 'su.supplies_id', '=', 'sv.supplies_id')
        ->leftJoin('units as u', 'u.unit_id', '=', 'pod.unit_id')
        ->where('pod.po_id', $p->po_id)
        ->get([
            'pod.pod_id', 'pod.pod_nama', 'pod.pod_variant', 'pod.pod_qty', 'pod.unit_id',
            'u.unit_name', 'sv.supplies_id', 'su.supplies_name', 'su.supplies_kind',
            'su.trading_product_variant_id',
        ]);
    foreach ($details as $d) {
        echo "  item supplies_id={$d->supplies_id} {$d->supplies_name} qty={$d->pod_qty} {$d->unit_name} kind={$d->supplies_kind}\n";

        if ($d->supplies_id) {
            $stocks = DB::table('supplies_stocks as ss')
                ->leftJoin('warehouses as w', 'w.id', '=', 'ss.warehouse_id')
                ->leftJoin('units as u', 'u.unit_id', '=', 'ss.unit_id')
                ->where('ss.supplies_id', $d->supplies_id)
                ->where('ss.status', 1)
                ->get(['ss.warehouse_id', 'w.warehouse_name as wh_name', 'ss.unit_id', 'u.unit_name', 'ss.ss_stock']);
            foreach ($stocks as $st) {
                echo "    stock WH{$st->warehouse_id} {$st->wh_name} {$st->unit_name}: {$st->ss_stock}\n";
            }
        }
    }

    $logs = DB::table('log_stocks')
        ->where('log_kode', $p->po_number)
        ->orderBy('log_id')
        ->get(['log_id', 'log_type', 'log_category', 'log_item_id', 'log_jumlah', 'unit_id', 'log_notes', 'warehouse_id', 'created_at']);
    echo "  logs for {$p->po_number}: " . count($logs) . "\n";
    foreach ($logs as $l) {
        echo "    L{$l->log_id} type={$l->log_type} cat={$l->log_category} item={$l->log_item_id} qty={$l->log_jumlah} unit={$l->unit_id} wh={$l->warehouse_id} {$l->log_notes} @{$l->created_at}\n";
    }
}
