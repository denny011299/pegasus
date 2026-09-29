<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Any PO on 2026-09-28 ===\n";
$rows = DB::table('purchase_orders as po')
    ->leftJoin('suppliers as s', 's.supplier_id', '=', 'po.po_supplier')
    ->where(function ($q) {
        $q->whereDate('po.po_date', '2026-09-28')
            ->orWhereDate('po.created_at', '2026-09-28')
            ->orWhereDate('po.updated_at', '2026-09-28');
    })
    ->orderByDesc('po.po_id')
    ->get(['po.po_id', 'po.po_number', 'po.po_date', 'po.status', 'po.created_at', 'po.updated_at', 's.supplier_name']);
echo 'count=' . count($rows) . "\n";
foreach ($rows as $r) {
    echo "PO{$r->po_id} {$r->po_number} date={$r->po_date} status={$r->status} supplier={$r->supplier_name} created={$r->created_at} upd={$r->updated_at}\n";
}

echo "\n=== PO0829 / PO829 / like %829% ===\n";
foreach (DB::table('purchase_orders')->where('po_number', 'like', '%829%')->orWhere('po_id', 829)->get(['po_id', 'po_number', 'po_date', 'status', 'po_supplier', 'created_at']) as $r) {
    echo "PO{$r->po_id} {$r->po_number} date={$r->po_date} status={$r->status} supplier={$r->po_supplier} created={$r->created_at}\n";
}

echo "\n=== Latest 15 POs overall ===\n";
foreach (DB::table('purchase_orders as po')
    ->leftJoin('suppliers as s', 's.supplier_id', '=', 'po.po_supplier')
    ->orderByDesc('po.po_id')->limit(15)
    ->get(['po.po_id', 'po.po_number', 'po.po_date', 'po.status', 'po.created_at', 's.supplier_name']) as $r) {
    echo "PO{$r->po_id} {$r->po_number} date={$r->po_date} status={$r->status} supplier={$r->supplier_name} created={$r->created_at}\n";
}

echo "\n=== Logs on 2026-09-28 pembelian ===\n";
$logs = DB::table('log_stocks')
    ->whereDate('created_at', '2026-09-28')
    ->where('log_notes', 'like', '%Pembelian%')
    ->orderBy('log_id')
    ->get(['log_id', 'log_kode', 'log_item_id', 'log_jumlah', 'unit_id', 'warehouse_id', 'log_notes', 'created_at']);
echo 'count=' . count($logs) . "\n";
foreach ($logs as $l) {
    echo "L{$l->log_id} {$l->log_kode} item={$l->log_item_id} qty={$l->log_jumlah} wh={$l->warehouse_id} {$l->log_notes}\n";
}

echo "\n=== Logs MM around Sep 28 week ===\n";
$logs2 = DB::table('log_stocks')
    ->whereBetween('created_at', ['2026-09-25', '2026-09-30'])
    ->where('log_notes', 'like', '%MM%')
    ->orderBy('log_id')
    ->get(['log_id', 'log_kode', 'log_item_id', 'log_jumlah', 'warehouse_id', 'log_notes', 'created_at']);
echo 'count=' . count($logs2) . "\n";
foreach ($logs2 as $l) {
    echo "L{$l->log_id} {$l->log_kode} item={$l->log_item_id} qty={$l->log_jumlah} wh={$l->warehouse_id} @{$l->created_at} {$l->log_notes}\n";
}
