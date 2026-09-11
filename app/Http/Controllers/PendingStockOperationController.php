<?php

namespace App\Http\Controllers;

use App\Models\PendingStockOperation;
use App\Models\Warehouse;
use App\Support\DataTableParams;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PendingStockOperationController extends Controller
{
    public function index()
    {
        $this->lazyFlushActiveWarehouse();

        return view('Backoffice.Inventory.Pending_Stock_Operation');
    }

    public function getPendingStockOperation(Request $req)
    {
        $this->lazyFlushActiveWarehouse();

        $dt = DataTableParams::from($req->all());
        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        $warehouseId = (int) ($req->warehouse_id ?? $activeWh);
        $domain = trim((string) ($req->domain ?? ''));
        $sourceType = trim((string) ($req->source_type ?? ''));
        $dateFrom = trim((string) ($req->date_from ?? ''));
        $dateTo = trim((string) ($req->date_to ?? ''));
        // status kosong = semua (kecuali soft-delete); default list = pending
        $statusRaw = $req->input('status', (string) PendingStockOperation::STATUS_PENDING);
        $filterByStatus = $statusRaw !== null && $statusRaw !== '';

        $base = PendingStockOperation::query()->where('status', '!=', PendingStockOperation::STATUS_DELETED);
        if ($warehouseId > 0) {
            $base->where('warehouse_id', $warehouseId);
        }
        if ($domain !== '') {
            $base->where('domain', $domain);
        }
        if ($sourceType !== '') {
            $base->where('source_type', $sourceType);
        }
        if ($filterByStatus) {
            $base->where('status', (int) $statusRaw);
        }
        if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $base->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $base->whereDate('created_at', '<=', $dateTo);
        }

        $recordsTotal = (clone $base)->count();
        $search = $dt['search'];
        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('source_code', 'like', '%'.$search.'%')
                    ->orWhere('source_type', 'like', '%'.$search.'%')
                    ->orWhere('error_message', 'like', '%'.$search.'%');
            });
        }
        $recordsFiltered = (clone $base)->count();

        $rows = (clone $base)
            ->orderByDesc('pso_id')
            ->skip($dt['start'])
            ->take($dt['length'])
            ->get();

        $whNames = Warehouse::query()
            ->whereIn('id', $rows->pluck('warehouse_id')->unique()->filter())
            ->pluck('warehouse_name', 'id');

        $data = $rows->map(function (PendingStockOperation $row) use ($whNames) {
            return [
                'pso_id' => (int) $row->pso_id,
                'created_at' => optional($row->created_at)?->format('d M Y H:i') ?? '-',
                'source_type' => $row->source_type,
                'source_type_label' => $this->sourceTypeLabel($row->source_type),
                'source_code' => $row->source_code ?: '-',
                'warehouse_id' => (int) $row->warehouse_id,
                'warehouse_name' => $whNames[$row->warehouse_id] ?? ('#'.$row->warehouse_id),
                'domain' => $row->domain,
                'domain_label' => $row->domain === PendingStockOperation::DOMAIN_SUPPLIES ? 'Bahan' : 'Produk',
                'opname' => trim(($row->blocked_by_opname_type ?: '').' #'.($row->blocked_by_opname_id ?: '-')),
                'status' => (int) $row->status,
                'status_label' => $this->statusLabel((int) $row->status),
                'error_message' => $row->error_message,
            ];
        })->values()->all();

        return response()->json([
            'draw' => $dt['draw'],
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function getPendingStockOperationDetail(Request $req)
    {
        $id = (int) ($req->id ?? $req->pso_id ?? 0);
        $row = PendingStockOperation::query()->find($id);
        if (! $row) {
            return response()->json(['status' => -1, 'message' => 'Data tidak ditemukan']);
        }
        $wh = Warehouse::query()->find($row->warehouse_id);

        return response()->json([
            'status' => 1,
            'data' => [
                'pso_id' => (int) $row->pso_id,
                'created_at' => optional($row->created_at)?->format('d M Y H:i'),
                'source_type' => $row->source_type,
                'source_type_label' => $this->sourceTypeLabel($row->source_type),
                'source_id' => (int) $row->source_id,
                'source_code' => $row->source_code,
                'warehouse_name' => $wh->warehouse_name ?? ('#'.$row->warehouse_id),
                'domain_label' => $row->domain === PendingStockOperation::DOMAIN_SUPPLIES ? 'Bahan' : 'Produk',
                'opname_type' => $row->blocked_by_opname_type,
                'opname_id' => $row->blocked_by_opname_id,
                'pso_status' => (int) $row->status,
                'status_label' => $this->statusLabel((int) $row->status),
                'payload' => $row->payload,
                'error_message' => $row->error_message,
                'applied_at' => optional($row->applied_at)?->format('d M Y H:i'),
            ],
        ]);
    }

    public function getPendingStockOperationCount(Request $req)
    {
        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        $warehouseId = (int) ($req->warehouse_id ?? $activeWh);
        $count = app(\App\Support\PendingStockOperationService::class)
            ->countPending($warehouseId > 0 ? $warehouseId : null);

        return response()->json([
            'status' => 1,
            'count' => $count,
        ]);
    }

    private function sourceTypeLabel(string $type): string
    {
        return match ($type) {
            PendingStockOperation::SOURCE_PRODUCTION_ACC => 'Produksi ACC',
            PendingStockOperation::SOURCE_STOCK_TRANSFER_SHIP => 'Kirim ST',
            PendingStockOperation::SOURCE_STOCK_TRANSFER_ACCEPT => 'Terima ST',
            PendingStockOperation::SOURCE_PURCHASE_ORDER_ACC => 'ACC Pembelian',
            default => $type,
        };
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            PendingStockOperation::STATUS_PENDING => 'Menunggu',
            PendingStockOperation::STATUS_APPLIED => 'Applied',
            PendingStockOperation::STATUS_CANCELLED => 'Dibatalkan',
            default => (string) $status,
        };
    }

    /** Buka list / reload DT → flush antrian usang di gudang aktif (tanpa cron). */
    private function lazyFlushActiveWarehouse(): void
    {
        $whId = (int) (Session::get('active_warehouse_id') ?? 0);
        if ($whId <= 0) {
            return;
        }
        $svc = app(\App\Support\PendingStockOperationService::class);
        $user = Session::get('user');
        $staffId = (int) (is_object($user) ? ($user->staff_id ?? 0) : 0) ?: null;
        try {
            $svc->flushStaleIfUnblocked($whId, PendingStockOperation::DOMAIN_PRODUCT, $staffId);
            $svc->flushStaleIfUnblocked($whId, PendingStockOperation::DOMAIN_SUPPLIES, $staffId);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
