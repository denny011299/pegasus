<?php

namespace App\Support;

use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\SupplierController;
use App\Models\PendingStockOperation;
use App\Models\Production;
use App\Models\PurchaseOrder;
use App\Models\StockTransfer;
use App\Support\StockOpname\OpenOpnameGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use RuntimeException;
use Throwable;

/**
 * Antrian mutasi stok saat opname open.
 * Apply ST lewat path existing controller; production_acc lewat re-dispatch ACC.
 */
class PendingStockOperationService
{
    public function __construct(
        private readonly OpenOpnameGuard $guard = new OpenOpnameGuard(),
    ) {
    }

    public function hasPendingForSource(string $sourceType, int $sourceId): bool
    {
        return PendingStockOperation::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', PendingStockOperation::STATUS_PENDING)
            ->exists();
    }

    /** Counter sidebar / badge: antrian menunggu (opsional per gudang aktif). */
    public function countPending(?int $warehouseId = null): int
    {
        $q = PendingStockOperation::query()
            ->where('status', PendingStockOperation::STATUS_PENDING);
        if ($warehouseId !== null && $warehouseId > 0) {
            $q->where('warehouse_id', $warehouseId);
        }

        return (int) $q->count();
    }

    public function pendingMessageForSource(string $sourceType): string
    {
        return match ($sourceType) {
            PendingStockOperation::SOURCE_PRODUCTION_ACC =>
                'Masih ada progress di Antrian Mutasi Stok (Produksi ACC). Belum bisa dilanjutkan sampai opname selesai.',
            PendingStockOperation::SOURCE_STOCK_TRANSFER_SHIP =>
                'Masih ada progress di Antrian Mutasi Stok (Kirim). Belum bisa dilanjutkan sampai opname selesai.',
            PendingStockOperation::SOURCE_STOCK_TRANSFER_ACCEPT =>
                'Masih ada progress di Antrian Mutasi Stok (Terima). Belum bisa dilanjutkan sampai opname selesai.',
            PendingStockOperation::SOURCE_PURCHASE_ORDER_ACC =>
                'Masih ada progress di Antrian Mutasi Stok (ACC Pembelian). Belum bisa dilanjutkan sampai opname selesai.',
            default =>
                'Masih ada progress di Antrian Mutasi Stok. Belum bisa dilanjutkan sampai opname selesai.',
        };
    }

    public function softBlockMessage(int $warehouseId, string $domain): string
    {
        $blocker = $this->guard->firstBlocker($warehouseId, $domain);
        if ($blocker && $blocker['code'] !== '') {
            return 'Gudang sedang Stock Opname ('.$blocker['code'].'). Mutasi stok ditolak sampai opname selesai.';
        }

        return 'Gudang sedang Stock Opname. Mutasi stok ditolak sampai opname selesai.';
    }

    /**
     * Lazy flush tanpa cron: kalau WH+domain sudah tidak di-block (mis. opname kemarin
     * masih status=1 tapi tanggalnya bukan hari ini), apply semua pending di situ.
     *
     * @return array{applied: int, cancelled: int, errors: list<string>}|null null = tidak flush
     */
    public function flushStaleIfUnblocked(int $warehouseId, string $domain, ?int $appliedBy = null): ?array
    {
        if ($warehouseId <= 0) {
            return null;
        }
        if ($this->guard->isBlocked($warehouseId, $domain)) {
            return null;
        }

        $hasPending = PendingStockOperation::query()
            ->where('warehouse_id', $warehouseId)
            ->where('domain', $domain)
            ->where('status', PendingStockOperation::STATUS_PENDING)
            ->exists();
        if (! $hasPending) {
            return null;
        }

        return $this->applyAllForWarehouse($warehouseId, $domain, $appliedBy);
    }

    public function enqueueInfoMessage(string $sourceType): string
    {
        return match ($sourceType) {
            PendingStockOperation::SOURCE_PRODUCTION_ACC =>
                'Masuk Antrian Mutasi Stok (Produksi ACC). Stok belum berubah sampai opname selesai.',
            PendingStockOperation::SOURCE_STOCK_TRANSFER_SHIP =>
                'Masuk Antrian Mutasi Stok (Kirim). Stok asal belum dipotong sampai opname selesai.',
            PendingStockOperation::SOURCE_STOCK_TRANSFER_ACCEPT =>
                'Masuk Antrian Mutasi Stok (Terima). Stok tujuan belum bertambah sampai opname selesai.',
            PendingStockOperation::SOURCE_PURCHASE_ORDER_ACC =>
                'Masuk Antrian Mutasi Stok (ACC Pembelian). Stok belum bertambah sampai opname selesai.',
            default =>
                'Masuk Antrian Mutasi Stok. Mutasi menunggu opname selesai.',
        };
    }

    /**
     * @param  array{
     *     warehouse_id: int,
     *     domain: string,
     *     source_type: string,
     *     source_id: int,
     *     source_code?: string|null,
     *     payload?: array|null,
     *     created_by?: int|null
     * }  $attrs
     */
    public function enqueue(array $attrs): PendingStockOperation
    {
        $warehouseId = (int) $attrs['warehouse_id'];
        $domain = (string) $attrs['domain'];
        $sourceType = (string) $attrs['source_type'];
        $sourceId = (int) $attrs['source_id'];

        return DB::transaction(function () use ($attrs, $warehouseId, $domain, $sourceType, $sourceId) {
            $open = $this->guard->lockOpenOpnames($warehouseId, $domain);
            if ($open->isEmpty()) {
                throw new RuntimeException(
                    'Sedang diproses opname / antrian. Coba lagi sebentar.'
                );
            }

            $existing = PendingStockOperation::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->where('status', PendingStockOperation::STATUS_PENDING)
                ->lockForUpdate()
                ->first();
            if ($existing) {
                return $existing;
            }

            $first = $open->first();
            $blockerType = $domain === OpenOpnameGuard::DOMAIN_SUPPLIES ? 'bahan' : 'produk';
            $blockerId = $domain === OpenOpnameGuard::DOMAIN_SUPPLIES
                ? (int) $first->stob_id
                : (int) $first->sto_id;

            $row = new PendingStockOperation();
            $row->warehouse_id = $warehouseId;
            $row->domain = $domain;
            $row->source_type = $sourceType;
            $row->source_id = $sourceId;
            $row->source_code = $attrs['source_code'] ?? null;
            $row->payload = $attrs['payload'] ?? null;
            $row->status = PendingStockOperation::STATUS_PENDING;
            $row->blocked_by_opname_type = $blockerType;
            $row->blocked_by_opname_id = $blockerId;
            $row->created_by = $attrs['created_by'] ?? null;
            $row->save();

            return $row;
        });
    }

    public function applyOne(int $psoId, ?int $appliedBy = null): void
    {
        DB::transaction(function () use ($psoId, $appliedBy) {
            $row = PendingStockOperation::query()
                ->where('pso_id', $psoId)
                ->lockForUpdate()
                ->first();
            if (! $row || (int) $row->status !== PendingStockOperation::STATUS_PENDING) {
                return;
            }

            try {
                $this->replay($row);
                $row->refresh();
                if ((int) $row->status === PendingStockOperation::STATUS_CANCELLED) {
                    return;
                }
                if ((int) $row->status !== PendingStockOperation::STATUS_PENDING) {
                    return;
                }
                $row->status = PendingStockOperation::STATUS_APPLIED;
                $row->applied_by = $appliedBy;
                $row->applied_at = now();
                $row->error_message = null;
                $row->save();
            } catch (Throwable $e) {
                $row->refresh();
                if ((int) $row->status === PendingStockOperation::STATUS_PENDING) {
                    $row->error_message = $e->getMessage() ?: 'Gagal apply antrian';
                    $row->save();
                }
                throw $e;
            }
        });
    }

    /**
     * Urutan: ship ST → accept ST → production_acc. Idempotent skip bila dokumen sudah bergerak.
     *
     * @return array{applied: int, cancelled: int, errors: list<string>}
     */
    public function applyAllForWarehouse(int $warehouseId, string $domain, ?int $appliedBy = null): array
    {
        $summary = ['applied' => 0, 'cancelled' => 0, 'errors' => []];

        $order = [
            PendingStockOperation::SOURCE_STOCK_TRANSFER_SHIP,
            PendingStockOperation::SOURCE_STOCK_TRANSFER_ACCEPT,
            PendingStockOperation::SOURCE_PRODUCTION_ACC,
            PendingStockOperation::SOURCE_PURCHASE_ORDER_ACC,
        ];

        foreach ($order as $sourceType) {
            $ids = PendingStockOperation::query()
                ->where('warehouse_id', $warehouseId)
                ->where('domain', $domain)
                ->where('source_type', $sourceType)
                ->where('status', PendingStockOperation::STATUS_PENDING)
                ->orderBy('pso_id')
                ->pluck('pso_id');

            foreach ($ids as $psoId) {
                try {
                    $this->applyOne((int) $psoId, $appliedBy);
                    $summary['applied']++;
                } catch (Throwable $e) {
                    $summary['errors'][] = 'PSO#'.$psoId.': '.$e->getMessage();
                }
            }
        }

        return $summary;
    }

    protected function replay(PendingStockOperation $row): void
    {
        $payload = is_array($row->payload) ? $row->payload : [];
        $staffId = (int) ($payload['staff_id'] ?? $row->created_by ?? Session::get('user')->staff_id ?? 0);

        if ($row->source_type === PendingStockOperation::SOURCE_STOCK_TRANSFER_SHIP) {
            $st = StockTransfer::query()->find((int) $row->source_id);
            if (! $st) {
                $row->status = PendingStockOperation::STATUS_CANCELLED;
                $row->error_message = 'Stock transfer tidak ditemukan';
                $row->save();

                return;
            }
            if ((int) $st->status !== 1) {
                // Sudah kirim / batal — anggap selesai
                return;
            }
            /** @var StockTransferController $ctl */
            $ctl = app(StockTransferController::class);
            $ctl->runShipLockedForQueue(
                (int) $st->st_id,
                $staffId > 0 ? $staffId : (int) ($st->acc_by ?? $st->sender_id ?? 0),
                $payload['ship_proof_path'] ?? null
            );

            return;
        }

        if ($row->source_type === PendingStockOperation::SOURCE_STOCK_TRANSFER_ACCEPT) {
            $st = StockTransfer::query()->find((int) $row->source_id);
            if (! $st) {
                $row->status = PendingStockOperation::STATUS_CANCELLED;
                $row->error_message = 'Stock transfer tidak ditemukan';
                $row->save();

                return;
            }
            if ((int) $st->status === 4) {
                return;
            }
            if ((int) $st->status !== 2) {
                throw new RuntimeException('Stock transfer belum berstatus Kirim (tunggu apply Kirim dulu)');
            }
            /** @var StockTransferController $ctl */
            $ctl = app(StockTransferController::class);
            $ctl->runAcceptLockedForQueue(
                (int) $st->st_id,
                $staffId > 0 ? $staffId : (int) ($st->receiver_id ?? $st->sender_id ?? 0),
                $payload['accept_note'] ?? null,
                is_array($payload['received_map'] ?? null) ? $payload['received_map'] : []
            );

            return;
        }

        if ($row->source_type === PendingStockOperation::SOURCE_PRODUCTION_ACC) {
            $production = Production::query()->find((int) $row->source_id);
            if (! $production) {
                $row->status = PendingStockOperation::STATUS_CANCELLED;
                $row->error_message = 'Produksi tidak ditemukan';
                $row->save();

                return;
            }
            if ((int) $production->status !== 1) {
                return;
            }
            // Tandai applied dulu di caller; di sini jalankan ACC via request internal.
            // Hindari re-enqueue: payload flag from_queue.
            $whId = (int) ($payload['request']['warehouse_id'] ?? $row->warehouse_id ?? 0);
            if ($whId > 0) {
                Session::put('active_warehouse_id', $whId);
            }
            $req = Request::create('/accProduction', 'POST', array_merge(
                is_array($payload['request'] ?? null) ? $payload['request'] : ['production_id' => (int) $production->production_id],
                ['from_pending_stock_queue' => 1]
            ));
            $req->setLaravelSession(app('session')->driver());
            /** @var \App\Http\Controllers\ProductionController $ctl */
            $ctl = app(\App\Http\Controllers\ProductionController::class);
            $response = $ctl->accProduction($req);
            $data = method_exists($response, 'getData') ? (array) $response->getData(true) : [];
            $status = (int) ($data['status'] ?? 0);
            if ($status === -2) {
                return;
            }
            if ($status !== 1) {
                throw new RuntimeException((string) ($data['message'] ?? 'Gagal apply Produksi ACC dari antrian'));
            }

            return;
        }

        if ($row->source_type === PendingStockOperation::SOURCE_PURCHASE_ORDER_ACC) {
            $po = PurchaseOrder::query()->find((int) $row->source_id);
            if (! $po) {
                $row->status = PendingStockOperation::STATUS_CANCELLED;
                $row->error_message = 'Pesanan pembelian tidak ditemukan';
                $row->save();

                return;
            }
            if ((int) $po->status !== 1) {
                return;
            }

            $whId = (int) ($payload['request']['warehouse_id'] ?? $row->warehouse_id ?? 0);
            if ($whId > 0) {
                Session::put('active_warehouse_id', $whId);
            }

            $accData = is_array($payload['request']['data'] ?? null)
                ? $payload['request']['data']
                : ['po_id' => (int) $po->po_id];

            $req = Request::create('/accPO', 'POST', [
                'data' => $accData,
                'from_pending_stock_queue' => 1,
            ]);
            $req->setLaravelSession(app('session')->driver());

            /** @var SupplierController $ctl */
            $ctl = app(SupplierController::class);
            $response = $ctl->accPO($req);

            if ($response instanceof JsonResponse) {
                $data = (array) $response->getData(true);
                $status = (int) ($data['status'] ?? 0);
                if ($status === -2) {
                    return;
                }
                if (! empty($data['queued'])) {
                    throw new RuntimeException('Replay ACC Pembelian masih masuk antrian');
                }
                if ($status === -1 || (isset($data['status']) && $status !== 1)) {
                    throw new RuntimeException((string) ($data['message'] ?? 'Gagal apply ACC Pembelian dari antrian'));
                }

                return;
            }

            // Legacy success: due date string / plain response
            return;
        }

        throw new RuntimeException('source_type antrian tidak dikenali: '.$row->source_type);
    }
}
