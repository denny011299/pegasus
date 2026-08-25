<?php

namespace App\Http\Controllers;

use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Staff;
use App\Models\StockTransfer;
use App\Models\StockTransferDetail;
use App\Models\DashboardChangeLog;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Support\ProductUnitStock;
use App\Support\RoleAccess;
use App\Support\StockTransferApproval;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Throwable;

/**
 * Stock Transfer
 * Status: 0=deleted, 1=pending, 2=kirim, 3=cancel, 4=terkirim, 5=cancel_kirim
 *
 * Create       → status=1 (Pending), stok belum dipotong
 * Approve      → hanya request eceran (source_type=retail_request): QC → Kepala Ops sebelum Kirim
 * Reject/Tolak → pending langsung Cancel (status=3); QC/Kepala Ops di gudang asal boleh tolak
 * Transfer lain → Acc/Tolak Kirim & Acc/Tolak Terima (tanpa QC/Ops, tanpa edit qty terima)
 * Ship (ACC)   → Pending→Kirim: potong stok sumber, status=2
 * Accept (ACC) → Kirim→Terkirim: konversi + tambah stok tujuan, status=4
 * Cancel       → Pending→Cancel (status=3); stok tetap di sumber (produksi: tidak hangus)
 * Cancel Kirim → Kirim→Cancel Kirim (status=5), restore stok sumber
 * Delete       → pending saja, status=0
 * Update       → pending saja, ganti item (tanpa mutasi stok)
 *
 * ST produksi & eceran↔eceran: tanpa approval QC/Ops.
 */
class StockTransferController extends Controller
{
    private const TRANSFER_LOG_MODULE_KEY = 'stock_transfer_action';

    public function index()
    {
        return view('Backoffice.Inventory.Stock_Transfer');
    }

    public function logsPage()
    {
        return view('Backoffice.Reports.ReportStockTransfer');
    }

    public function getStockTransferLogs(Request $req)
    {
        $limit = (int) ($req->input('length', 10));
        if ($limit <= 0) $limit = 10;
        $start_limit = (int) ($req->input('start', 0));
        $search = trim((string) ($req->input('search.value', '')));

        $start = trim((string) ($req->start_date ?? ''));
        $end = trim((string) ($req->end_date ?? ''));

        $query = DashboardChangeLog::query()
            ->where('module_key', self::TRANSFER_LOG_MODULE_KEY);

        if ($start !== '' && $end !== '') {
            $query->whereBetween(DB::raw('DATE(created_at)'), [$start, $end]);
        }

        $recordsTotal = $query->count();

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('what_changed', 'like', "%{$search}%")
                  ->orWhere('summary', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = $query->count();

        $rows = $query->orderByDesc('id')
            ->skip($start_limit)
            ->take($limit)
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'draw' => intval($req->input('draw', 1)),
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => []
            ]);
        }

        $staffIds = $rows->pluck('created_by')->filter()->map(fn ($id) => (int) $id)->all();
        $warehouseIds = [];
        $variantIds = [];
        $unitIds = [];
        foreach ($rows as $row) {
            $meta = is_array($row->meta) ? $row->meta : [];
            foreach (['before', 'after'] as $side) {
                $snapshot = is_array($meta[$side] ?? null) ? $meta[$side] : [];
                $header = is_array($snapshot['header'] ?? null) ? $snapshot['header'] : [];
                foreach (['sender_id', 'receiver_id', 'acc_by'] as $key) {
                    if (! empty($header[$key])) {
                        $staffIds[] = (int) $header[$key];
                    }
                }
                foreach (['from_warehouse_id', 'to_warehouse_id'] as $key) {
                    if (! empty($header[$key])) {
                        $warehouseIds[] = (int) $header[$key];
                    }
                }
                foreach ((array) ($snapshot['items'] ?? []) as $item) {
                    if (! empty($item['product_variant_id'])) {
                        $variantIds[] = (int) $item['product_variant_id'];
                    }
                    if (! empty($item['unit_id'])) {
                        $unitIds[] = (int) $item['unit_id'];
                    }
                    if (! empty($item['received_unit_id'])) {
                        $unitIds[] = (int) $item['received_unit_id'];
                    }
                }
            }
        }

        $staffIds = array_values(array_unique(array_filter($staffIds)));
        $staffMap = $staffIds !== []
            ? Staff::query()->whereIn('staff_id', $staffIds)->pluck('staff_name', 'staff_id')->all()
            : [];
        $warehouseMap = $warehouseIds !== []
            ? Warehouse::query()->whereIn('id', array_unique($warehouseIds))->pluck('warehouse_name', 'id')->all()
            : [];
        $variantMap = $variantIds !== []
            ? ProductVariant::query()
                ->whereIn('product_variant_id', array_unique($variantIds))
                ->get(['product_variant_id', 'product_id', 'product_variant_name', 'product_variant_sku'])
                ->keyBy('product_variant_id')
            : collect();
        $productIds = $variantMap->pluck('product_id')->filter()->unique()->all();
        $productMap = $productIds !== []
            ? Product::query()->whereIn('product_id', $productIds)->pluck('product_name', 'product_id')->all()
            : [];
        $unitMap = $unitIds !== []
            ? Unit::query()->whereIn('unit_id', array_unique($unitIds))->get()
                ->mapWithKeys(fn ($unit) => [
                    $unit->unit_id => $unit->unit_short_name ?: $unit->unit_name,
                ])->all()
            : [];

        $data = $rows->map(function ($row) use (
            $staffMap,
            $warehouseMap,
            $variantMap,
            $productMap,
            $unitMap
        ) {
            $meta = is_array($row->meta) ? $row->meta : [];
            foreach (['before', 'after'] as $side) {
                if (is_array($meta[$side] ?? null)) {
                    $meta[$side] = $this->enrichLogSnapshot(
                        $meta[$side],
                        $staffMap,
                        $warehouseMap,
                        $variantMap,
                        $productMap,
                        $unitMap
                    );
                }
            }
            $action = (string) ($meta['action'] ?? '');
            $snapshot = is_array($meta['after'] ?? null)
                ? $meta['after']
                : (is_array($meta['before'] ?? null) ? $meta['before'] : []);
            return [
                'id' => (int) $row->id,
                'module_key' => (string) ($row->module_key ?? ''),
                'module_label' => (string) ($row->module_label ?? ''),
                'reference' => (string) ($row->reference ?? ''),
                'action' => $action,
                'transfer_id' => (int) ($meta['transfer_id'] ?? 0),
                'transfer_code' => (string) ($meta['transfer_code'] ?? ''),
                'what_changed' => (string) ($row->what_changed ?? ''),
                'summary' => (string) ($row->summary ?? ''),
                'summary_human' => $this->humanTransferLogSummary($action, $snapshot),
                'created_by' => $row->created_by ? (int) $row->created_by : null,
                'created_by_name' => $row->created_by ? ($staffMap[$row->created_by] ?? '-') : '-',
                'created_at' => (string) ($row->created_at ?? ''),
                'meta' => $meta,
            ];
        })->values();

        return response()->json([
            'draw' => intval($req->input('draw', 1)),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }

    private function enrichLogSnapshot(
        array $snapshot,
        array $staffMap,
        array $warehouseMap,
        $variantMap,
        array $productMap,
        array $unitMap
    ): array {
        $header = is_array($snapshot['header'] ?? null) ? $snapshot['header'] : [];
        $header['sender_name'] = ! empty($header['sender_id'])
            ? ($staffMap[$header['sender_id']] ?? '-')
            : '-';
        $header['receiver_name'] = ! empty($header['receiver_id'])
            ? ($staffMap[$header['receiver_id']] ?? '-')
            : '-';
        $header['acc_by_name'] = ! empty($header['acc_by'])
            ? ($staffMap[$header['acc_by']] ?? '-')
            : '-';
        $header['from_warehouse_name'] = ! empty($header['from_warehouse_id'])
            ? ($warehouseMap[$header['from_warehouse_id']] ?? '-')
            : '-';
        $header['to_warehouse_name'] = ! empty($header['to_warehouse_id'])
            ? ($warehouseMap[$header['to_warehouse_id']] ?? '-')
            : '-';
        $snapshot['header'] = $header;

        $snapshot['items'] = collect((array) ($snapshot['items'] ?? []))
            ->map(function ($item) use ($variantMap, $productMap, $unitMap) {
                $variant = $variantMap->get((int) ($item['product_variant_id'] ?? 0));
                $item['product_name'] = $variant
                    ? ($productMap[$variant->product_id] ?? '-')
                    : '-';
                $item['variant_name'] = $variant->product_variant_name ?? '-';
                $item['sku'] = $variant->product_variant_sku ?? '-';
                $item['unit_name'] = $unitMap[$item['unit_id'] ?? 0] ?? '-';
                $receivedUnitId = (int) ($item['received_unit_id'] ?? 0);
                $sentUnitId = (int) ($item['unit_id'] ?? 0);
                $item['received_unit_name'] = $receivedUnitId > 0
                    ? ($unitMap[$receivedUnitId] ?? '-')
                    : '-';
                $qtyReceived = $item['qty_received'] ?? null;
                $convertedSent = null;
                $selisih = null;
                if ($qtyReceived !== null && $receivedUnitId > 0 && $sentUnitId > 0) {
                    $convertedSent = ProductUnitStock::canConvertUnits(
                        $sentUnitId,
                        $receivedUnitId,
                        (int) ($item['product_variant_id'] ?? 0)
                    )
                        ? ProductUnitStock::convertQty(
                            (float) ($item['qty'] ?? 0),
                            $sentUnitId,
                            $receivedUnitId,
                            (int) ($item['product_variant_id'] ?? 0)
                        )
                        : ((float) ($item['qty'] ?? 0));
                    $selisih = (float) $qtyReceived - (float) $convertedSent;
                }
                $item['converted_sent_qty'] = $convertedSent;
                $item['selisih'] = $selisih;
                return $item;
            })->values()->all();

        return $snapshot;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $details
     * @return array{selisih: float|null, has_selisih: bool, lines: int}
     */
    private function aggregateTransferSelisih($details): array
    {
        $sum = 0.0;
        $lines = 0;
        $hasValue = false;

        foreach ($details as $d) {
            if ($d->qty_received === null) {
                continue;
            }
            $hasValue = true;
            $targetUnitId = (int) ($d->received_unit_id ?: $d->unit_id);
            $sentUnitId = (int) $d->unit_id;
            $convertedSent = $targetUnitId === $sentUnitId
                ? (float) $d->qty
                : (ProductUnitStock::canConvertUnits(
                    $sentUnitId,
                    $targetUnitId,
                    (int) $d->product_variant_id
                )
                    ? ProductUnitStock::convertQty(
                        (float) $d->qty,
                        $sentUnitId,
                        $targetUnitId,
                        (int) $d->product_variant_id
                    )
                    : (float) $d->qty);
            $diff = (float) $d->qty_received - $convertedSent;
            $sum += $diff;
            if (abs($diff) > 1e-9) {
                $lines++;
            }
        }

        return [
            'selisih' => $hasValue ? $sum : null,
            'has_selisih' => $lines > 0,
            'lines' => $lines,
        ];
    }

    private function humanTransferLogSummary(string $action, array $snapshot): string
    {
        $header = is_array($snapshot['header'] ?? null) ? $snapshot['header'] : [];
        $from = (string) ($header['from_warehouse_name'] ?? '-');
        $to = (string) ($header['to_warehouse_name'] ?? '-');
        $count = count((array) ($snapshot['items'] ?? []));
        $suffix = $count > 0 ? (' • ' . $count . ' produk') : '';

        return match ($action) {
            'create' => 'Membuat Stock Transfer ke ' . $to . $suffix,
            'update' => 'Mengubah Stock Transfer ke ' . $to . $suffix,
            'delete' => 'Menghapus Stock Transfer dari ' . $from,
            'ship' => 'Mengirim Stock Transfer ke ' . $to . $suffix,
            'accept' => 'Menerima Stock Transfer dari ' . $from . $suffix,
            'reject' => 'Cancel Stock Transfer dari ' . $from,
            'cancel_kirim' => 'Cancel Kirim Stock Transfer ke ' . $to,
            'approve_qc' => 'Approve QC Stock Transfer ke ' . $to,
            'approve_ops' => 'Approve Kepala Operasional Stock Transfer ke ' . $to,
            default => 'Aktivitas Stock Transfer',
        };
    }

    public function getStockTransfer(Request $req)
    {
        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);

        $query = StockTransfer::query()->where('status', '>=', 1);

        // Hanya transfer yang melibatkan gudang aktif (asal ATAU tujuan)
        if ($activeWh > 0) {
            $query->where(function ($q) use ($activeWh) {
                $q->where('from_warehouse_id', $activeWh)
                    ->orWhere('to_warehouse_id', $activeWh);
            });
        } else {
            // Tanpa gudang aktif → jangan tampilkan data
            return response()->json([]);
        }

        $rows = $query
            ->orderByDesc('transfer_date')
            ->orderByDesc('st_id')
            ->orderByRaw('CASE WHEN status = 1 THEN 0 WHEN status = 2 THEN 1 WHEN status = 4 THEN 2 ELSE 3 END')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([]);
        }

        $staffIds = $rows->pluck('sender_id')
            ->merge($rows->pluck('receiver_id'))
            ->merge($rows->pluck('acc_by'))
            ->merge($rows->pluck('qc_approved_by'))
            ->merge($rows->pluck('ops_approved_by'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $staffMap = Staff::query()
            ->whereIn('staff_id', $staffIds)
            ->pluck('staff_name', 'staff_id')
            ->all();

        $whIds = $rows->pluck('from_warehouse_id')
            ->merge($rows->pluck('to_warehouse_id'))
            ->unique()
            ->values()
            ->all();
        $whMap = Warehouse::query()
            ->whereIn('id', $whIds)
            ->pluck('warehouse_name', 'id')
            ->all();

        $user = Session::get('user');
        $staffId = (int) ($user->staff_id ?? 0);
        $assignedWh = $user ? Staff::assignedWarehouseIds($user) : [];
        $canEditAccess = RoleAccess::can($user, 'Stock Transfer', 'edit');
        $canDeleteAccess = RoleAccess::can($user, 'Stock Transfer', 'delete');
        $canOthersAccess = RoleAccess::can($user, 'Stock Transfer', 'others');

        $detailGroups = StockTransferDetail::query()
            ->whereIn('st_id', $rows->pluck('st_id'))
            ->where('status', 1)
            ->get(['st_id', 'product_variant_id', 'unit_id', 'received_unit_id', 'qty', 'qty_received'])
            ->groupBy('st_id');

        $whTypeMap = $this->warehouseMainFlags(
            $rows->pluck('from_warehouse_id')->merge($rows->pluck('to_warehouse_id'))->unique()->values()->all()
        );

        $data = $rows->map(function ($row) use (
            $staffMap,
            $whMap,
            $whTypeMap,
            $staffId,
            $activeWh,
            $assignedWh,
            $canEditAccess,
            $canDeleteAccess,
            $canOthersAccess,
            $detailGroups,
            $user
        ) {
            $status = (int) $row->status;
            $fromWh = (int) $row->from_warehouse_id;
            $toWh = (int) $row->to_warehouse_id;
            $isProduction = $row->source_type === 'production';
            $isRetailRequest = StockTransferApproval::isRetailRequestRoute(
                $row->source_type,
                $whTypeMap[$fromWh] ?? null,
                $whTypeMap[$toWh] ?? null
            );
            $requiresApproval = StockTransferApproval::requiresApproval(
                $row->source_type,
                $whTypeMap[$fromWh] ?? null,
                $whTypeMap[$toWh] ?? null,
                $fromWh
            );
            $approvalsComplete = ! $requiresApproval || StockTransferApproval::isFullyApproved($row, $fromWh);
            $selisihMeta = $this->aggregateTransferSelisih(
                $detailGroups->get($row->st_id, collect())
            );

            $canShipByWarehouse = $this->canShipTransferRow(
                $status,
                $fromWh,
                $staffId,
                $activeWh,
                $assignedWh
            );
            $canAccByWarehouse = $this->canAccTransferRow(
                $status,
                (int) $row->sender_id,
                $fromWh,
                $toWh,
                $staffId,
                $activeWh,
                $assignedWh,
                $isProduction
            );
            $canEditByWarehouse = $this->canEditTransferRow(
                $status,
                $fromWh,
                $toWh,
                $activeWh,
                $isRetailRequest,
                $row
            );
            $canCancelKirimByWarehouse = $this->canCancelKirimTransferRow(
                $status,
                $fromWh,
                $toWh,
                $staffId,
                $activeWh,
                $assignedWh,
                $isRetailRequest
            );
            $actorRole = $canOthersAccess
                ? StockTransferApproval::resolveActorRole($user, $fromWh)
                : null;

            return [
                'id' => (int) $row->st_id,
                'st_id' => (int) $row->st_id,
                'transfer_code' => $row->transfer_code,
                'transfer_date' => Carbon::parse($row->transfer_date)->format('d-m-Y'),
                'sender_id' => (int) $row->sender_id,
                'sender_name' => $staffMap[$row->sender_id] ?? '-',
                'receiver_id' => $row->receiver_id ? (int) $row->receiver_id : null,
                'receiver_name' => $row->receiver_id ? ($staffMap[$row->receiver_id] ?? '-') : '-',
                'ship_acc_by' => $row->acc_by ? (int) $row->acc_by : null,
                'ship_acc_by_name' => $row->acc_by ? ($staffMap[$row->acc_by] ?? '-') : '-',
                'from_warehouse_id' => $fromWh,
                'from_warehouse_name' => $whMap[$row->from_warehouse_id] ?? '-',
                'to_warehouse_id' => $toWh,
                'to_warehouse_name' => $whMap[$row->to_warehouse_id] ?? '-',
                'note' => $row->note,
                'source_type' => $row->source_type,
                'source_id' => $row->source_id ? (int) $row->source_id : null,
                'disposition' => $row->disposition,
                'status' => $status,
                'selisih' => $status === 4 ? $selisihMeta['selisih'] : null,
                'has_selisih' => $status === 4 ? $selisihMeta['has_selisih'] : false,
                'selisih_lines' => $status === 4 ? $selisihMeta['lines'] : 0,
                'is_retail_request' => $isRetailRequest ? 1 : 0,
                'requires_approval' => $requiresApproval ? 1 : 0,
                'qc_required' => StockTransferApproval::qcRequiredAtWarehouse($fromWh) ? 1 : 0,
                'ops_required' => StockTransferApproval::opsRequiredAtWarehouse($fromWh) ? 1 : 0,
                'qc_approved' => StockTransferApproval::isQcApproved($row) ? 1 : 0,
                'ops_approved' => StockTransferApproval::isOpsApproved($row) ? 1 : 0,
                'qc_approved_by' => $row->qc_approved_by ? (int) $row->qc_approved_by : null,
                'qc_approved_by_name' => $row->qc_approved_by ? ($staffMap[$row->qc_approved_by] ?? '-') : null,
                'ops_approved_by' => $row->ops_approved_by ? (int) $row->ops_approved_by : null,
                'ops_approved_by_name' => $row->ops_approved_by ? ($staffMap[$row->ops_approved_by] ?? '-') : null,
                'can_ship' => $canOthersAccess && $canShipByWarehouse && $approvalsComplete,
                'can_acc' => $canOthersAccess && $canAccByWarehouse,
                'can_edit' => $canEditAccess && $canEditByWarehouse,
                'can_delete' => ! $isProduction && $canDeleteAccess && $canEditByWarehouse,
                'can_reject' => $canOthersAccess
                    && $status === 1
                    && (
                        ($isRetailRequest && $activeWh === $fromWh
                            && ($assignedWh === [] || in_array($fromWh, $assignedWh, true))
                            && StockTransferApproval::canRejectAtOrigin($user, $row, $fromWh))
                        || ($isRetailRequest && $activeWh === $toWh
                            && ($assignedWh === [] || in_array($toWh, $assignedWh, true))
                            && StockTransferApproval::canCancelRetailRequestAtDestination($user, $row, $toWh, $fromWh))
                        || (! $isRetailRequest && $activeWh === $fromWh
                            && ($assignedWh === [] || in_array($fromWh, $assignedWh, true)))
                    ),
                'can_cancel_kirim' => $canOthersAccess && $canCancelKirimByWarehouse,
                'can_approve_qc' => $canOthersAccess
                    && $isRetailRequest
                    && StockTransferApproval::canApproveQc($row, $fromWh)
                    && $status === 1
                    && $activeWh === $fromWh
                    && $actorRole === 'qc',
                'can_approve_ops' => $canOthersAccess
                    && $isRetailRequest
                    && StockTransferApproval::canApproveOps($row, $fromWh)
                    && $status === 1
                    && $activeWh === $fromWh
                    && $actorRole === 'ops',
            ];
        })->values();

        return response()->json($data);
    }

    public function getStockTransferDetail(Request $req)
    {
        $id = (int) ($req->id ?? $req->st_id ?? 0);
        if ($id <= 0) {
            return response()->json(null);
        }

        $header = StockTransfer::query()->where('st_id', $id)->where('status', '>=', 1)->first();
        if (! $header) {
            return response()->json(null);
        }

        $details = StockTransferDetail::query()
            ->where('st_id', $id)
            ->where('status', 1)
            ->get();

        $variantIds = $details->pluck('product_variant_id')->unique()->all();
        $products = Product::query()
            ->whereIn('product_id', $details->pluck('product_id')->unique()->all())
            ->get()
            ->keyBy('product_id');
        $unitIds = $details->pluck('unit_id')
            ->merge($details->pluck('received_unit_id'))
            ->merge($products->pluck('unit_id'))
            ->filter()
            ->unique()
            ->all();

        $variants = ProductVariant::query()
            ->whereIn('product_variant_id', $variantIds)
            ->get()
            ->keyBy('product_variant_id');
        $unitIds = collect($unitIds)
            ->merge($variants->pluck('retail_unit'))
            ->filter()
            ->unique()
            ->all();
        $units = Unit::query()
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->keyBy('unit_id');

        $sender = Staff::query()->find($header->sender_id);
        $receiver = $header->receiver_id ? Staff::query()->find($header->receiver_id) : null;
        $fromWhModel = Warehouse::query()->find($header->from_warehouse_id);
        $toWhModel = Warehouse::query()->find($header->to_warehouse_id);

        $sourceIsMain = $this->warehouseIsMain((int) $header->from_warehouse_id);
        $destinationIsMain = $this->warehouseIsMain((int) $header->to_warehouse_id);
        $isProductionTransfer = $header->source_type === 'production';

        $items = $details->map(function ($d) use (
            $variants,
            $products,
            $units,
            $header,
            $sourceIsMain,
            $destinationIsMain,
            $isProductionTransfer
        ) {
            $pv = $variants[$d->product_variant_id] ?? null;
            $pr = $products[$d->product_id] ?? null;
            $un = $units[$d->unit_id] ?? null;
            $defaultUnitId = (int) ($pr->unit_id ?? 0);
            $resolution = $this->resolveTransferUnits(
                $sourceIsMain,
                $destinationIsMain,
                $pv,
                $pr,
                (int) $d->unit_id,
                $isProductionTransfer
            );
            $targetUnitId = (int) ($resolution['target_unit_id'] ?? $d->unit_id);
            $receivedUnitId = (int) ($d->received_unit_id ?: $targetUnitId);
            $displayTargetUnitId = $d->qty_received !== null ? $receivedUnitId : $targetUnitId;
            $targetUnit = $units[$displayTargetUnitId] ?? Unit::query()->find($displayTargetUnitId);
            $defaultUnit = $defaultUnitId > 0
                ? ($units[$defaultUnitId] ?? Unit::query()->find($defaultUnitId))
                : null;
            $convertedSent = ProductUnitStock::canConvertUnits(
                (int) $d->unit_id,
                $displayTargetUnitId,
                (int) $d->product_variant_id
            )
                ? ProductUnitStock::convertQty(
                    (float) $d->qty,
                    (int) $d->unit_id,
                    $displayTargetUnitId,
                    (int) $d->product_variant_id
                )
                : null;
            $qtyReceivedTarget = $d->qty_received !== null ? (float) $d->qty_received : null;
            $qtyReceivedSent = null;
            if ($qtyReceivedTarget !== null) {
                if ((int) $d->unit_id === $displayTargetUnitId) {
                    $qtyReceivedSent = $qtyReceivedTarget;
                } elseif (ProductUnitStock::canConvertUnits(
                    $displayTargetUnitId,
                    (int) $d->unit_id,
                    (int) $d->product_variant_id
                )) {
                    $qtyReceivedSent = ProductUnitStock::convertQty(
                        $qtyReceivedTarget,
                        $displayTargetUnitId,
                        (int) $d->unit_id,
                        (int) $d->product_variant_id
                    );
                }
            }
            $snap = ProductUnitStock::sourceSnapshot(
                (int) $header->from_warehouse_id,
                (int) $d->product_variant_id,
                (bool) $sourceIsMain,
                (int) ($pr->unit_id ?? 0),
                (int) ($pv->retail_unit ?? 0)
            );

            $targetUnitRow = $units[$displayTargetUnitId] ?? $targetUnit;
            $stockTargetUnitRow = $units[$targetUnitId] ?? $targetUnitRow;

            return [
                'std_id' => (int) $d->std_id,
                'product_id' => (int) $d->product_id,
                'product_variant_id' => (int) $d->product_variant_id,
                'product_name' => $pr->product_name ?? '-',
                'product_variant_name' => $pv->product_variant_name ?? '-',
                'sku' => $pv->product_variant_sku ?? ($pv->sku ?? '-'),
                'unit_id' => (int) $d->unit_id,
                'unit_name' => $un->unit_name ?? ($un->unit_short_name ?? '-'),
                'qty' => (float) $d->qty,
                'qty_received' => $qtyReceivedTarget,
                'qty_received_sent_unit' => $qtyReceivedSent,
                'received_unit_id' => $displayTargetUnitId,
                'received_unit_name' => $targetUnitRow
                    ? ($targetUnitRow->unit_name ?? ($targetUnitRow->unit_short_name ?? '-'))
                    : '-',
                'default_unit_id' => $defaultUnitId > 0 ? $defaultUnitId : null,
                'default_unit_name' => $defaultUnit
                    ? ($defaultUnit->unit_name ?? ($defaultUnit->unit_short_name ?? '-'))
                    : '-',
                'target_unit_id' => $targetUnitId,
                'target_unit_name' => $stockTargetUnitRow
                    ? ($stockTargetUnitRow->unit_name ?? ($stockTargetUnitRow->unit_short_name ?? '-'))
                    : '-',
                'converted_sent_qty' => $convertedSent,
                'conversion_factor' => (float) $d->qty > 0 && $convertedSent !== null
                    ? $convertedSent / (float) $d->qty
                    : null,
                'selisih' => $qtyReceivedTarget !== null && $convertedSent !== null
                    ? ($qtyReceivedTarget - $convertedSent)
                    : null,
                'stock_text' => $snap['stock_text'] ?? '-',
                'units' => $snap['units'] ?? [],
            ];
        })->values();

        $user = Session::get('user');
        $staffId = (int) ($user->staff_id ?? 0);
        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        $assignedWh = $user ? Staff::assignedWarehouseIds($user) : [];
        $status = (int) $header->status;
        $fromWh = (int) $header->from_warehouse_id;
        $toWh = (int) $header->to_warehouse_id;
        $isProduction = $header->source_type === 'production';
        $canOthersAccess = RoleAccess::can($user, 'Stock Transfer', 'others');
        $canEditAccess = RoleAccess::can($user, 'Stock Transfer', 'edit');
        $isRetailRequest = StockTransferApproval::isRetailRequestRoute(
            $header->source_type,
            $this->warehouseIsMain($fromWh),
            $this->warehouseIsMain($toWh)
        );
        $requiresApproval = StockTransferApproval::requiresApproval(
            $header->source_type,
            $this->warehouseIsMain($fromWh),
            $this->warehouseIsMain($toWh),
            $fromWh
        );
        $approvalsComplete = ! $requiresApproval || StockTransferApproval::isFullyApproved($header, $fromWh);
        $actorRole = $canOthersAccess
            ? StockTransferApproval::resolveActorRole($user, $fromWh)
            : null;
        $qcBy = $header->qc_approved_by ? (int) $header->qc_approved_by : null;
        $opsBy = $header->ops_approved_by ? (int) $header->ops_approved_by : null;
        $approverNames = [];
        if ($qcBy || $opsBy) {
            $approverNames = Staff::query()
                ->whereIn('staff_id', array_filter([$qcBy, $opsBy]))
                ->pluck('staff_name', 'staff_id')
                ->all();
        }

        return response()->json([
            'id' => (int) $header->st_id,
            'st_id' => (int) $header->st_id,
            'transfer_code' => $header->transfer_code,
            'transfer_date' => Carbon::parse($header->transfer_date)->format('d-m-Y'),
            'sender_id' => (int) $header->sender_id,
            'sender_name' => $sender->staff_name ?? '-',
            'receiver_id' => $header->receiver_id ? (int) $header->receiver_id : null,
            'receiver_name' => $receiver->staff_name ?? '-',
            'from_warehouse_id' => $fromWh,
            'from_warehouse_name' => $fromWhModel->warehouse_name ?? '-',
            'to_warehouse_id' => $toWh,
            'to_warehouse_name' => $toWhModel->warehouse_name ?? '-',
            'note' => $header->note,
            'accept_note' => $header->accept_note,
            'source_type' => $header->source_type,
            'source_id' => $header->source_id ? (int) $header->source_id : null,
            'disposition' => $header->disposition,
            'status' => $status,
            'is_retail_request' => $isRetailRequest ? 1 : 0,
            'requires_approval' => $requiresApproval ? 1 : 0,
            'qc_required' => StockTransferApproval::qcRequiredAtWarehouse($fromWh) ? 1 : 0,
            'ops_required' => StockTransferApproval::opsRequiredAtWarehouse($fromWh) ? 1 : 0,
            'qc_approved' => StockTransferApproval::isQcApproved($header) ? 1 : 0,
            'ops_approved' => StockTransferApproval::isOpsApproved($header) ? 1 : 0,
            'qc_approved_by' => $qcBy,
            'qc_approved_by_name' => $qcBy ? ($approverNames[$qcBy] ?? '-') : null,
            'ops_approved_by' => $opsBy,
            'ops_approved_by_name' => $opsBy ? ($approverNames[$opsBy] ?? '-') : null,
            'can_ship' => $canOthersAccess
                && $approvalsComplete
                && $this->canShipTransferRow(
                    $status,
                    $fromWh,
                    $staffId,
                    $activeWh,
                    $assignedWh
                ),
            'can_acc' => $canOthersAccess && $this->canAccTransferRow(
                $status,
                (int) $header->sender_id,
                $fromWh,
                $toWh,
                $staffId,
                $activeWh,
                $assignedWh,
                $isProduction
            ),
            'can_edit' => $canEditAccess
                && $this->canEditTransferRow(
                    $status,
                    $fromWh,
                    $toWh,
                    $activeWh,
                    $isRetailRequest,
                    $header
                ),
            'can_reject' => $canOthersAccess
                && $status === 1
                && (
                    ($isRetailRequest && $activeWh === $fromWh
                        && ($assignedWh === [] || in_array($fromWh, $assignedWh, true))
                        && StockTransferApproval::canRejectAtOrigin($user, $header, $fromWh))
                    || ($isRetailRequest && $activeWh === $toWh
                        && ($assignedWh === [] || in_array($toWh, $assignedWh, true))
                        && StockTransferApproval::canCancelRetailRequestAtDestination($user, $header, $toWh, $fromWh))
                    || (! $isRetailRequest && $activeWh === $fromWh
                        && ($assignedWh === [] || in_array($fromWh, $assignedWh, true)))
                ),
            'can_cancel_kirim' => $canOthersAccess && $this->canCancelKirimTransferRow(
                $status,
                $fromWh,
                $toWh,
                $staffId,
                $activeWh,
                $assignedWh,
                $isRetailRequest
            ),
            'can_approve_qc' => $canOthersAccess
                && $isRetailRequest
                && StockTransferApproval::canApproveQc($header, $fromWh)
                && $status === 1
                && $activeWh === $fromWh
                && $actorRole === 'qc',
            'can_approve_ops' => $canOthersAccess
                && $isRetailRequest
                && StockTransferApproval::canApproveOps($header, $fromWh)
                && $status === 1
                && $activeWh === $fromWh
                && $actorRole === 'ops',
            'items' => $items,
        ]);
    }

    public function getTransferSourceStock(Request $req)
    {
        $warehouseId = (int) ($req->warehouse_id ?? 0);
        $variantId = (int) ($req->product_variant_id ?? 0);
        $toWarehouseId = (int) ($req->to_warehouse_id ?? 0);

        if ($warehouseId <= 0 || $variantId <= 0) {
            return response()->json([
                'stock_text' => '-',
                'units' => [],
                'unit_order' => [],
            ]);
        }

        ProductUnitStock::clearCache();
        $sourceIsMain = $this->warehouseIsMain($warehouseId);
        $variant = ProductVariant::query()->find($variantId);
        if ($sourceIsMain === null || ! $variant) {
            return response()->json([
                'stock_text' => '-',
                'units' => [],
                'unit_order' => [],
                'message' => 'Gudang atau varian produk tidak ditemukan',
            ]);
        }

        $retailUnitId = (int) ($variant->retail_unit ?? 0);
        $product = Product::query()->find($variant->product_id);
        if (! $sourceIsMain && $retailUnitId <= 0) {
            return response()->json([
                'stock_text' => '0',
                'units' => [],
                'unit_order' => [],
                'warehouse_is_main' => false,
                'retail_unit_id' => null,
                'message' => 'Produk tidak dapat ditambahkan: satuan eceran belum diatur sebagai basis chain gudang eceran',
            ]);
        }

        $snapshot = ProductUnitStock::sourceSnapshot(
            $warehouseId,
            $variantId,
            $sourceIsMain,
            (int) ($product->unit_id ?? 0),
            $retailUnitId
        );

        // Filter lagi berdasarkan kombinasi gudang tujuan agar pilihan invalid
        // ditolak sejak frontend, sebelum user menekan Simpan.
        if ($toWarehouseId > 0) {
            $destinationIsMain = $this->warehouseIsMain($toWarehouseId);
            $units = collect((array) ($snapshot['units'] ?? []));
            $unitErrors = [];

            if ($destinationIsMain === null || ! $product) {
                $units = collect();
                $snapshot['message'] = 'Gudang tujuan atau produk tidak valid';
            } else {
                $units = $units->filter(function ($unit) use (
                    $sourceIsMain,
                    $destinationIsMain,
                    $variant,
                    $product,
                    &$unitErrors
                ) {
                    $resolution = $this->resolveTransferUnits(
                        $sourceIsMain,
                        $destinationIsMain,
                        $variant,
                        $product,
                        (int) ($unit['unit_id'] ?? 0)
                    );

                    if ($resolution['error']) {
                        $unitErrors[] = $resolution['error'];
                    }

                    return ! $resolution['error'];
                })->values();

                if ($units->isEmpty()) {
                    if ($retailUnitId <= 0) {
                        $snapshot['message'] = 'Produk tidak dapat ditambahkan: satuan eceran belum diatur';
                    } elseif (collect($unitErrors)->contains(
                        fn ($error) => str_contains((string) $error, 'rantai konversi')
                    )) {
                        $snapshot['message'] = 'Produk tidak dapat ditambahkan: satuan belum memiliki relasi konversi ke satuan tujuan';
                    } else {
                        $snapshot['message'] = 'Produk tidak dapat ditambahkan: tidak ada satuan yang valid untuk rute gudang ini';
                    }
                }
            }

            $snapshot['units'] = $units->all();
            $snapshot['unit_order'] = $units->pluck('unit_id')->map(fn ($id) => (int) $id)->all();
            // Tampilkan stok fisik per satuan (ps_stock), sama dengan batas Kirim.
            $snapshot['stock_text'] = $units->isEmpty()
                ? '0'
                : $units->map(fn ($unit) => number_format(
                    (float) ($unit['ps_stock'] ?? 0),
                    0,
                    ',',
                    '.'
                )
                    . ' ' . ($unit['unit_name'] ?? $unit['unit_short_name'] ?? '-'))->implode(', ');
        }

        $snapshot['warehouse_is_main'] = $sourceIsMain;
        $snapshot['retail_unit_id'] = $retailUnitId ?: null;
        $snapshot['default_unit_id'] = (int) ($product->unit_id ?? 0) ?: null;

        return response()->json($snapshot);
    }

    public function getTransferRetailUnitSetup(Request $req)
    {
        $this->authorizeRetailUnitSetup();
        $variantId = (int) ($req->product_variant_id ?? 0);
        $fromWarehouseId = (int) ($req->from_warehouse_id ?? 0);
        $toWarehouseId = (int) ($req->to_warehouse_id ?? 0);
        $variant = ProductVariant::query()
            ->where('product_variant_id', $variantId)
            ->where('status', 1)
            ->first();
        $sourceIsMain = $this->warehouseIsMain($fromWarehouseId);
        $destinationIsMain = $this->warehouseIsMain($toWarehouseId);

        if (! $variant || $sourceIsMain === null || $destinationIsMain === null) {
            return response()->json([
                'status' => -1,
                'message' => 'Gudang atau varian produk tidak ditemukan',
            ], 422);
        }

        if ($destinationIsMain) {
            return response()->json([
                'status' => 1,
                'requires_setup' => false,
                'destination_is_retail' => false,
                'retail_unit_id' => $variant?->retail_unit ? (int) $variant->retail_unit : null,
            ]);
        }

        $retailUnitId = (int) ($variant->retail_unit ?? 0);
        if ($retailUnitId > 0) {
            return response()->json([
                'status' => 1,
                'requires_setup' => false,
                'destination_is_retail' => true,
                'retail_unit_id' => $retailUnitId,
            ]);
        }

        $units = $this->validRetailUnitsForVariant($variant);

        return response()->json([
            'status' => 1,
            'requires_setup' => true,
            'destination_is_retail' => true,
            'retail_unit_id' => null,
            'units' => $units->map(fn ($unit) => [
                'unit_id' => (int) $unit->unit_id,
                'unit_name' => $unit->unit_name ?: ($unit->unit_short_name ?: '-'),
                'unit_short_name' => $unit->unit_short_name ?: ($unit->unit_name ?: '-'),
            ])->values()->all(),
            'message' => $units->isEmpty()
                ? 'Produk ini belum memiliki satuan produk yang valid untuk dijadikan satuan eceran'
                : null,
        ]);
    }

    public function saveTransferRetailUnit(Request $req)
    {
        $this->authorizeRetailUnitSetup();
        $variantId = (int) ($req->product_variant_id ?? 0);
        $unitId = (int) ($req->unit_id ?? 0);
        $fromWarehouseId = (int) ($req->from_warehouse_id ?? 0);
        $toWarehouseId = (int) ($req->to_warehouse_id ?? 0);

        if ($this->warehouseIsMain($fromWarehouseId) === null
            || $this->warehouseIsMain($toWarehouseId) !== false) {
            return response()->json([
                'status' => -1,
                'message' => 'Pengaturan satuan eceran hanya tersedia untuk transfer ke Gudang Eceran',
            ], 422);
        }

        try {
            $retailUnitId = DB::transaction(function () use ($variantId, $unitId) {
                $variant = ProductVariant::query()
                    ->where('product_variant_id', $variantId)
                    ->where('status', 1)
                    ->lockForUpdate()
                    ->first();
                if (! $variant) {
                    throw new \RuntimeException('Varian produk tidak ditemukan');
                }

                $currentRetailUnitId = (int) ($variant->retail_unit ?? 0);
                if ($currentRetailUnitId > 0) {
                    if ($currentRetailUnitId !== $unitId) {
                        throw new \RuntimeException('Satuan eceran sudah diatur oleh proses lain');
                    }
                    return $currentRetailUnitId;
                }

                $validUnitIds = $this->validRetailUnitsForVariant($variant)
                    ->pluck('unit_id')
                    ->map(fn ($id) => (int) $id);
                if ($unitId <= 0 || ! $validUnitIds->contains($unitId)) {
                    throw new \RuntimeException('Satuan eceran tidak terdaftar pada produk ini');
                }

                $variant->retail_unit = $unitId;
                $variant->save();

                return $unitId;
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => -1,
                'message' => $e->getMessage() ?: 'Gagal menyimpan satuan eceran',
            ], 422);
        }

        ProductUnitStock::clearCache();

        return response()->json([
            'status' => 1,
            'retail_unit_id' => $retailUnitId,
            'message' => 'Satuan eceran berhasil disimpan',
        ]);
    }

    public function checkTransferStock(Request $req)
    {
        $warehouseId = (int) ($req->from_warehouse_id ?? $req->warehouse_id ?? 0);
        $items = $req->input('items', []);
        if (! is_array($items)) {
            $items = [];
        }

        if ($warehouseId <= 0) {
            return response()->json([
                'ok' => false,
                'shortages' => [],
                'message' => 'Gudang asal wajib diisi',
            ]);
        }

        ProductUnitStock::clearCache();
        $normalized = $this->normalizeItems($items);
        $toWarehouseId = (int) ($req->to_warehouse_id ?? 0);
        $matrix = $this->validateTransferItems(
            $warehouseId,
            $toWarehouseId > 0 ? $toWarehouseId : null,
            $normalized
        );
        if (! $matrix['ok']) {
            return response()->json([
                'ok' => false,
                'shortages' => [],
                'message' => $matrix['message'],
                'matrix_error' => true,
                'invalid_variant_ids' => $matrix['invalid_variant_ids'],
            ]);
        }
        $sourceIsMain = $this->warehouseIsMain($warehouseId);
        $result = ProductUnitStock::checkItems(
            $warehouseId,
            $this->applySourceAvailabilityMode($normalized, $sourceIsMain)
        );

        if (! $result['ok']) {
            $names = array_map(fn ($s) => $s['label'], $result['shortages']);
            $result['message'] = 'Stok tidak mencukupi: ' . implode(', ', $names);
        } else {
            $result['message'] = 'Stok mencukupi';
        }

        return response()->json($result);
    }

    public function insertStockTransfer(Request $req)
    {
        $payload = $this->parseHeaderPayload($req);
        if ($payload['error']) {
            return response()->json(['status' => -1, 'message' => $payload['error']]);
        }

        $createGate = $this->assertAndNormalizeCreatePayload($payload);
        if ($createGate !== true) {
            return response()->json(['status' => -1, 'message' => $createGate]);
        }

        $items = $this->normalizeItems($req->input('items', []));
        if ($items === []) {
            return response()->json(['status' => -1, 'message' => 'Tambahkan minimal 1 produk']);
        }
        $matrix = $this->validateTransferItems(
            $payload['from_warehouse_id'],
            $payload['to_warehouse_id'],
            $items
        );
        if (! $matrix['ok']) {
            return response()->json(['status' => -1, 'message' => $matrix['message']]);
        }

        ProductUnitStock::clearCache();
        $check = ProductUnitStock::checkItems(
            $payload['from_warehouse_id'],
            $this->applySourceAvailabilityMode(
                $items,
                $this->warehouseIsMain($payload['from_warehouse_id'])
            )
        );
        if (! $check['ok']) {
            $names = array_map(fn ($s) => $s['label'], $check['shortages']);

            return response()->json([
                'status' => -1,
                'message' => 'Stok tidak mencukupi: ' . implode(', ', $names),
            ]);
        }

        try {
            $stId = DB::transaction(function () use ($payload, $items) {
                $stId = (new StockTransfer())->createHeader($payload);

                foreach ($items as $item) {
                    $pv = ProductVariant::query()->find($item['product_variant_id']);
                    $productId = (int) ($pv->product_id ?? 0);

                    StockTransferDetail::query()->create([
                        'st_id' => $stId,
                        'product_id' => $productId,
                        'product_variant_id' => $item['product_variant_id'],
                        'unit_id' => $item['unit_id'],
                        'received_unit_id' => null,
                        'qty' => $item['qty'],
                        'qty_received' => null,
                        'status' => 1,
                    ]);
                }

                return $stId;
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => -1,
                'message' => $e->getMessage() ?: 'Gagal menyimpan stock transfer',
            ]);
        }

        $snapshot = $this->snapshotTransfer($stId);
        $this->logTransferAction('create', $snapshot['header'], [
            'items_count' => count($snapshot['items']),
        ], null, $snapshot);

        return response()->json([
            'status' => 1,
            'id' => $stId,
            'message' => 'Stock transfer berhasil disimpan (Pending)',
        ]);
    }

    public function updateStockTransfer(Request $req)
    {
        $stId = (int) ($req->id ?? $req->st_id ?? 0);
        $header = StockTransfer::query()->where('st_id', $stId)->where('status', 1)->first();
        if (! $header) {
            return response()->json(['status' => -1, 'message' => 'Data tidak ditemukan / sudah tidak pending']);
        }

        $gate = $this->assertCanEditSource($header);
        if ($gate !== true) {
            return response()->json(['status' => -1, 'message' => $gate]);
        }

        $payload = $this->parseHeaderPayload($req);
        if ($payload['error']) {
            return response()->json(['status' => -1, 'message' => $payload['error']]);
        }

        // Produksi: gudang asal tetap gudang produksi (hasil sudah diinventori di situ).
        if ($header->source_type === 'production') {
            $payload['from_warehouse_id'] = (int) $header->from_warehouse_id;
            if ((int) $payload['to_warehouse_id'] === (int) $header->from_warehouse_id) {
                return response()->json([
                    'status' => -1,
                    'message' => 'Transfer hasil produksi harus menuju gudang lain (bukan gudang asal).',
                ]);
            }
        }

        // Request eceran: kunci rute utama → eceran + source_type.
        if ($header->source_type === 'retail_request') {
            $payload['from_warehouse_id'] = (int) $header->from_warehouse_id;
            $payload['to_warehouse_id'] = (int) $header->to_warehouse_id;
            $payload['source_type'] = 'retail_request';
        }

        $items = $this->normalizeItems($req->input('items', []));
        if ($items === []) {
            return response()->json(['status' => -1, 'message' => 'Tambahkan minimal 1 produk']);
        }
        $isProductionTransfer = $header->source_type === 'production';
        $matrix = $this->validateTransferItems(
            $payload['from_warehouse_id'],
            $payload['to_warehouse_id'],
            $items,
            $isProductionTransfer
        );
        if (! $matrix['ok']) {
            return response()->json(['status' => -1, 'message' => $matrix['message']]);
        }

        $before = $this->snapshotTransfer((int) $header->st_id);
        try {
            DB::transaction(function () use ($header, $payload, $items) {
                $oldDetails = StockTransferDetail::query()
                    ->where('st_id', $header->st_id)
                    ->where('status', 1)
                    ->get();

                foreach ($oldDetails as $old) {
                    $old->status = 0;
                    $old->save();
                }

                ProductUnitStock::clearCache();
                $isProductionTransfer = $header->source_type === 'production';
                $check = ProductUnitStock::checkItems(
                    $payload['from_warehouse_id'],
                    $this->applySourceAvailabilityMode(
                        $items,
                        $this->warehouseIsMain($payload['from_warehouse_id']),
                        $isProductionTransfer
                    )
                );
                if (! $check['ok']) {
                    $names = array_map(fn ($s) => $s['label'], $check['shortages']);
                    throw new \RuntimeException('Stok tidak mencukupi: ' . implode(', ', $names));
                }

                $header->transfer_date = $payload['transfer_date'];
                $header->sender_id = $payload['sender_id'];
                $header->receiver_id = null;
                $header->from_warehouse_id = $payload['from_warehouse_id'];
                $header->to_warehouse_id = $payload['to_warehouse_id'];
                $header->note = $payload['note'];
                $header->save();

                foreach ($items as $item) {
                    $pv = ProductVariant::query()->find($item['product_variant_id']);
                    $productId = (int) ($pv->product_id ?? 0);

                    StockTransferDetail::query()->create([
                        'st_id' => $header->st_id,
                        'product_id' => $productId,
                        'product_variant_id' => $item['product_variant_id'],
                        'unit_id' => $item['unit_id'],
                        'received_unit_id' => null,
                        'qty' => $item['qty'],
                        'qty_received' => null,
                        'status' => 1,
                    ]);
                }
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => -1,
                'message' => $e->getMessage() ?: 'Gagal update stock transfer',
            ]);
        }

        $after = $this->snapshotTransfer((int) $header->st_id);
        $this->logTransferAction('update', $after['header'] ?: $before['header'], [
            'items_count_before' => count($before['items']),
            'items_count_after' => count($after['items']),
        ], $before, $after);

        return response()->json([
            'status' => 1,
            'id' => (int) $header->st_id,
            'message' => 'Stock transfer berhasil diupdate',
        ]);
    }

    public function deleteStockTransfer(Request $req)
    {
        $stId = (int) ($req->id ?? $req->st_id ?? 0);
        $header = StockTransfer::query()->where('st_id', $stId)->where('status', 1)->first();
        if (! $header) {
            return response()->json(['status' => -1, 'message' => 'Hanya transfer pending yang bisa dihapus']);
        }
        if ($header->source_type === 'production') {
            return response()->json([
                'status' => -1,
                'message' => 'Transfer hasil produksi harus ditolak (stok tetap di gudang asal), bukan dihapus.',
            ]);
        }

        $gate = $this->assertCanEditSource($header);
        if ($gate !== true) {
            return response()->json(['status' => -1, 'message' => $gate]);
        }

        $before = $this->snapshotTransfer((int) $header->st_id);
        try {
            DB::transaction(function () use ($header) {
                $header->status = 0;
                $header->save();
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => -1,
                'message' => $e->getMessage() ?: 'Gagal hapus stock transfer',
            ]);
        }

        $after = $this->snapshotTransfer((int) $header->st_id);
        $this->logTransferAction('delete', $before['header'], [], $before, $after);

        return response()->json(['status' => 1, 'message' => 'Stock transfer dihapus']);
    }

    /**
     * Approve QC atau Kepala Operasional (berurut) untuk ST utama→eceran.
     * Body: id/st_id, type=qc|ops
     */
    public function approveStockTransfer(Request $req)
    {
        $stId = (int) ($req->id ?? $req->st_id ?? 0);
        $type = strtolower(trim((string) ($req->type ?? '')));
        if (! in_array($type, ['qc', 'ops'], true)) {
            return response()->json(['status' => -1, 'message' => 'Tipe approval tidak valid']);
        }

        $header = StockTransfer::query()->where('st_id', $stId)->where('status', 1)->first();
        if (! $header) {
            return response()->json(['status' => -1, 'message' => 'Data tidak ditemukan / sudah tidak pending']);
        }

        $fromWh = (int) $header->from_warehouse_id;
        $toWh = (int) $header->to_warehouse_id;
        $isRetailRequest = StockTransferApproval::isRetailRequestRoute(
            $header->source_type,
            $this->warehouseIsMain($fromWh),
            $this->warehouseIsMain($toWh)
        );
        if (! $isRetailRequest) {
            return response()->json(['status' => -1, 'message' => 'Transfer ini tidak membutuhkan approval']);
        }

        if ($type === 'qc' && ! StockTransferApproval::qcRequiredAtWarehouse($fromWh)) {
            return response()->json(['status' => -1, 'message' => 'Tidak ada Staf QC & Gudang di gudang asal']);
        }
        if ($type === 'ops' && ! StockTransferApproval::opsRequiredAtWarehouse($fromWh)) {
            return response()->json(['status' => -1, 'message' => 'Tidak ada Kepala Operasional di gudang asal']);
        }
        if ($type === 'ops'
            && StockTransferApproval::qcRequiredAtWarehouse($fromWh)
            && ! StockTransferApproval::isQcApproved($header)) {
            return response()->json([
                'status' => -1,
                'message' => 'Approve QC terlebih dahulu sebelum Kepala Operasional',
            ]);
        }

        $user = Session::get('user');
        $staffId = (int) ($user->staff_id ?? 0);
        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        if ($staffId <= 0) {
            return response()->json(['status' => -1, 'message' => 'User login tidak valid']);
        }
        if ($activeWh <= 0 || $activeWh !== $fromWh) {
            return response()->json([
                'status' => -1,
                'message' => 'Approval hanya di gudang asal (Gudang Besar). Ganti gudang aktif.',
            ]);
        }

        $actorRole = StockTransferApproval::resolveActorRole($user, $fromWh);
        if ($actorRole !== $type) {
            return response()->json([
                'status' => -1,
                'message' => $type === 'qc'
                    ? 'Hanya Staf QC & Gudang (assigned gudang asal) yang boleh approve QC'
                    : 'Hanya Kepala Operasional gudang asal yang boleh approve',
            ]);
        }

        if ($type === 'qc' && StockTransferApproval::isQcApproved($header)) {
            return response()->json(['status' => -1, 'message' => 'QC sudah approve']);
        }
        if ($type === 'ops' && StockTransferApproval::isOpsApproved($header)) {
            return response()->json(['status' => -1, 'message' => 'Kepala Operasional sudah approve']);
        }

        $before = $this->snapshotTransfer($stId);
        try {
            DB::transaction(function () use ($stId, $type, $staffId, $fromWh) {
                $locked = StockTransfer::query()
                    ->where('st_id', $stId)
                    ->where('status', 1)
                    ->lockForUpdate()
                    ->first();
                if (! $locked) {
                    throw new \RuntimeException('Transfer sudah diproses');
                }
                if ($type === 'qc') {
                    if (StockTransferApproval::isQcApproved($locked)) {
                        throw new \RuntimeException('QC sudah approve');
                    }
                    $locked->qc_approved_by = $staffId;
                    $locked->qc_approved_at = now();
                } else {
                    if (StockTransferApproval::isOpsApproved($locked)) {
                        throw new \RuntimeException('Kepala Operasional sudah approve');
                    }
                    if (StockTransferApproval::qcRequiredAtWarehouse($fromWh)
                        && ! StockTransferApproval::isQcApproved($locked)) {
                        throw new \RuntimeException('Approve QC terlebih dahulu sebelum Kepala Operasional');
                    }
                    $locked->ops_approved_by = $staffId;
                    $locked->ops_approved_at = now();
                }
                $locked->save();
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => -1,
                'message' => $e->getMessage() ?: 'Gagal approve stock transfer',
            ]);
        }

        $after = $this->snapshotTransfer($stId);
        $this->logTransferAction('approve_' . $type, $after['header'] ?: $before['header'], [
            'type' => $type,
        ], $before, $after);

        $label = $type === 'qc' ? 'QC' : 'Kepala Operasional';
        $afterHeader = $after['header'] ?? [];

        return response()->json([
            'status' => 1,
            'message' => 'Approval ' . $label . ' berhasil',
            'qc_approved' => (int) ($afterHeader['qc_approved_by'] ?? 0) > 0 ? 1 : 0,
            'ops_approved' => (int) ($afterHeader['ops_approved_by'] ?? 0) > 0 ? 1 : 0,
        ]);
    }

    /**
     * ACC gudang asal: Pending → Kirim (potong stok sumber).
     */
    public function shipStockTransfer(Request $req)
    {
        $stId = (int) ($req->id ?? $req->st_id ?? 0);
        $header = StockTransfer::query()->where('st_id', $stId)->where('status', 1)->first();
        if (! $header) {
            return response()->json(['status' => -1, 'message' => 'Data tidak ditemukan / sudah tidak pending']);
        }

        $gate = $this->assertCanShip($header);
        if ($gate !== true) {
            return response()->json(['status' => -1, 'message' => $gate]);
        }

        $user = Session::get('user');
        $accBy = (int) ($user->staff_id ?? 0);

        $before = $this->snapshotTransfer((int) $header->st_id);
        try {
            DB::transaction(function () use ($stId, $accBy) {
                $lockedHeader = StockTransfer::query()
                    ->where('st_id', $stId)
                    ->where('status', 1)
                    ->lockForUpdate()
                    ->first();
                if (! $lockedHeader) {
                    throw new \RuntimeException('Transfer sudah diproses');
                }

                $details = StockTransferDetail::query()
                    ->where('st_id', $stId)
                    ->where('status', 1)
                    ->lockForUpdate()
                    ->get();
                if ($details->isEmpty()) {
                    throw new \RuntimeException('Detail transfer kosong');
                }

                $items = $this->normalizeItems($details->map(fn ($d) => [
                    'product_variant_id' => (int) $d->product_variant_id,
                    'unit_id' => (int) $d->unit_id,
                    'qty' => (float) $d->qty,
                ])->values()->all());
                $isProduction = $lockedHeader->source_type === 'production';
                $matrix = $this->validateTransferItems(
                    (int) $lockedHeader->from_warehouse_id,
                    (int) $lockedHeader->to_warehouse_id,
                    $items,
                    $isProduction
                );
                if (! $matrix['ok']) {
                    throw new \RuntimeException($matrix['message']);
                }

                ProductUnitStock::clearCache();
                $sourceIsMain = $this->warehouseIsMain((int) $lockedHeader->from_warehouse_id);
                // Kirim: packing/rapikan OFF. Gudang utama boleh unpack ancestor
                // (DOS→Piece) agar stok ekuivalen cukup; eceran tetap exact unit.
                // Konversi satuan tujuan hanya di Terima (eceran → retail_unit).
                $check = ProductUnitStock::checkItems(
                    (int) $lockedHeader->from_warehouse_id,
                    $this->applySourceAvailabilityMode($items, $sourceIsMain, $isProduction)
                );
                if (! $check['ok']) {
                    $names = array_map(fn ($s) => $s['label'], $check['shortages']);
                    throw new \RuntimeException('Stok tidak mencukupi: ' . implode(', ', $names));
                }

                $code = $lockedHeader->transfer_code;
                $allowUnpack = $sourceIsMain === true;
                foreach ($items as $item) {
                    $cut = ProductUnitStock::deductQty(
                        (int) $lockedHeader->from_warehouse_id,
                        (int) $item['product_variant_id'],
                        (int) $item['unit_id'],
                        (float) $item['qty'],
                        $code,
                        'Stock Transfer ' . $code . ' - keluar gudang asal',
                        false,
                        $allowUnpack
                    );
                    if (! $cut['ok']) {
                        throw new \RuntimeException($cut['message'] ?? 'Gagal potong stok');
                    }
                }

                $lockedHeader->status = 2; // Kirim
                if ($accBy > 0) {
                    $lockedHeader->acc_by = $accBy;
                }
                $lockedHeader->save();
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => -1,
                'message' => $e->getMessage() ?: 'Gagal kirim stock transfer',
            ]);
        }

        $after = $this->snapshotTransfer((int) $header->st_id);
        $this->logTransferAction('ship', $after['header'] ?: $before['header'], [
            'items_count' => count($after['items']),
        ], $before, $after);

        return response()->json([
            'status' => 1,
            'message' => 'Stock transfer dikirim, stok gudang asal dipotong',
        ]);
    }

    public function accStockTransfer(Request $req)
    {
        $stId = (int) ($req->id ?? $req->st_id ?? 0);
        $header = StockTransfer::query()->where('st_id', $stId)->where('status', 2)->first();
        if (! $header) {
            return response()->json(['status' => -1, 'message' => 'Data tidak ditemukan / belum berstatus Kirim']);
        }

        $gate = $this->assertCanAcc($header);
        if ($gate !== true) {
            return response()->json(['status' => -1, 'message' => $gate]);
        }

        $user = Session::get('user');
        // Penerima = user yang ACC (dikunci, tidak bisa diganti dari request)
        $receiverId = (int) ($user->staff_id ?? 0);
        if ($receiverId <= 0) {
            return response()->json(['status' => -1, 'message' => 'User login tidak ditemukan']);
        }

        $acceptNote = $req->accept_note ?? $req->note ?? null;
        $itemsInput = $req->input('items', []);
        if (! is_array($itemsInput)) {
            $itemsInput = [];
        }

        $receivedMap = [];
        foreach ($itemsInput as $item) {
            $stdId = (int) ($item['std_id'] ?? 0);
            if ($stdId <= 0) {
                continue;
            }
            $receivedMap[$stdId] = (float) (
                $item['qty_received_sent_unit']
                ?? $item['qty_received']
                ?? $item['qty']
                ?? 0
            );
        }

        $accBy = (int) ($user->staff_id ?? 0);

        $before = $this->snapshotTransfer((int) $header->st_id);
        try {
            DB::transaction(function () use (
                $stId,
                $receiverId,
                $acceptNote,
                $receivedMap,
                $accBy
            ) {
                $lockedHeader = StockTransfer::query()
                    ->where('st_id', $stId)
                    ->where('status', 2)
                    ->lockForUpdate()
                    ->first();
                if (! $lockedHeader) {
                    throw new \RuntimeException('Transfer sudah diproses atau belum berstatus Kirim');
                }

                $details = StockTransferDetail::query()
                    ->where('st_id', $stId)
                    ->where('status', 1)
                    ->lockForUpdate()
                    ->get();

                if ($details->isEmpty()) {
                    throw new \RuntimeException('Detail transfer kosong');
                }

                $items = $details->map(fn ($d) => [
                    'product_variant_id' => (int) $d->product_variant_id,
                    'unit_id' => (int) $d->unit_id,
                    'qty' => (float) $d->qty,
                ])->values()->all();
                if ($lockedHeader->source_type !== 'production') {
                    $matrix = $this->validateTransferItems(
                        (int) $lockedHeader->from_warehouse_id,
                        (int) $lockedHeader->to_warehouse_id,
                        $items
                    );
                    if (! $matrix['ok']) {
                        throw new \RuntimeException($matrix['message']);
                    }
                }

                $code = $lockedHeader->transfer_code;
                $sourceIsMain = $this->warehouseIsMain((int) $lockedHeader->from_warehouse_id);
                $destinationIsMain = $this->warehouseIsMain((int) $lockedHeader->to_warehouse_id);
                $variants = ProductVariant::query()
                    ->whereIn('product_variant_id', $details->pluck('product_variant_id')->unique())
                    ->get()
                    ->keyBy('product_variant_id');
                $products = Product::query()
                    ->whereIn('product_id', $details->pluck('product_id')->unique())
                    ->get()
                    ->keyBy('product_id');
                foreach ($details as $d) {
                    $isRetailRequestAccept = $lockedHeader->source_type === 'retail_request';
                    $qtyReceivedInSentUnit = $isRetailRequestAccept
                        ? (float) $d->qty
                        : ($receivedMap[$d->std_id] ?? (float) $d->qty);
                    if ($qtyReceivedInSentUnit < 0) {
                        throw new \RuntimeException('Qty diterima tidak valid');
                    }
                    if (abs($qtyReceivedInSentUnit - round($qtyReceivedInSentUnit)) > 1e-9) {
                        throw new \RuntimeException(
                            'Qty diterima harus bilangan bulat (tanpa desimal/koma).'
                        );
                    }
                    $qtyReceivedInSentUnit = (float) round($qtyReceivedInSentUnit);
                    // Qty terima boleh > qty kirim (selisih lebih tercatat di log/selisih).

                    $resolution = $this->resolveTransferUnits(
                        $sourceIsMain,
                        $destinationIsMain,
                        $variants->get($d->product_variant_id),
                        $products->get($d->product_id),
                        (int) $d->unit_id,
                        $lockedHeader->source_type === 'production'
                    );
                    if ($resolution['error']) {
                        throw new \RuntimeException($resolution['error']);
                    }
                    $targetUnitId = (int) $resolution['target_unit_id'];
                    $qtyReceived = ProductUnitStock::convertQty(
                        $qtyReceivedInSentUnit,
                        (int) $d->unit_id,
                        $targetUnitId,
                        (int) $d->product_variant_id
                    );
                    if ($qtyReceivedInSentUnit > 0 && $qtyReceived <= 0) {
                        throw new \RuntimeException('Konversi satuan gagal untuk detail #' . $d->std_id);
                    }

                    if ($qtyReceived > 0) {
                        // Real case pergudangan: stok masuk tujuan dalam satuan yang
                        // diputuskan resolveTransferUnits — ke gudang utama = satuan kirim
                        // apa adanya (Piece tetap Piece, Jerigen tetap Jerigen), tanpa
                        // di-repack ke default unit. Ke eceran = retail_unit (konversi
                        // hanya di sini, karena eceran cuma pegang retail_unit).
                        $add = ProductUnitStock::addQty(
                            (int) $lockedHeader->to_warehouse_id,
                            (int) $d->product_id,
                            (int) $d->product_variant_id,
                            $targetUnitId,
                            $qtyReceived,
                            $code,
                            'Stock Transfer ' . $code . ' - masuk gudang tujuan'
                        );
                        if (! $add['ok']) {
                            throw new \RuntimeException($add['message'] ?? 'Gagal tambah stok tujuan');
                        }
                    }

                    $d->received_unit_id = $targetUnitId;
                    $d->qty_received = $qtyReceived;
                    $d->save();
                }

                $lockedHeader->receiver_id = $receiverId;
                $lockedHeader->accept_note = $acceptNote;
                $lockedHeader->status = 4; // Terkirim
                // acc_by dipakai untuk pencatat siapa yang klik "Kirim" (gudang asal)
                // saat Terima jangan overwrite, supaya jejak pengirim tetap ada.
                if (! $lockedHeader->acc_by) {
                    $lockedHeader->acc_by = $accBy > 0 ? $accBy : $receiverId;
                }
                $lockedHeader->save();
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => -1,
                'message' => $e->getMessage() ?: 'Gagal ACC stock transfer',
            ]);
        }

        $after = $this->snapshotTransfer((int) $header->st_id);
        $this->logTransferAction('accept', $after['header'] ?: $before['header'], [
            'items_count' => count($after['items']),
        ], $before, $after);

        return response()->json(['status' => 1, 'message' => 'Stock transfer berhasil diterima (Terkirim)']);
    }

    public function rejectStockTransfer(Request $req)
    {
        $stId = (int) ($req->id ?? $req->st_id ?? 0);
        $header = StockTransfer::query()->where('st_id', $stId)->where('status', 1)->first();
        if (! $header) {
            return response()->json(['status' => -1, 'message' => 'Hanya transfer pending yang bisa di-cancel']);
        }

        $gate = $this->assertCanReject($header);
        if ($gate !== true) {
            return response()->json(['status' => -1, 'message' => $gate]);
        }

        $isProduction = $header->source_type === 'production';
        // ST produksi: tolak pending = batalkan ST, stok tetap di gudang asal.
        // Tidak ada lagi hangus / bermasalah di alur ini.
        $disposition = $isProduction
            ? 'return_warehouse'
            : strtolower(trim((string) $req->input('disposition', '')));

        $before = $this->snapshotTransfer((int) $header->st_id);
        try {
            DB::transaction(function () use (
                $stId,
                $req,
                $isProduction,
                $disposition
            ) {
                $lockedHeader = StockTransfer::query()
                    ->where('st_id', $stId)
                    ->where('status', 1)
                    ->lockForUpdate()
                    ->first();
                if (! $lockedHeader) {
                    throw new \RuntimeException('Transfer sudah diproses.');
                }

                $notes = trim((string) ($req->input('notes') ?? $req->input('note') ?? ''));
                if ($isProduction) {
                    $details = StockTransferDetail::query()
                        ->where('st_id', $stId)
                        ->where('status', 1)
                        ->lockForUpdate()
                        ->get();
                    if ($details->isEmpty()) {
                        throw new \RuntimeException('Detail transfer kosong.');
                    }
                    $lockedHeader->disposition = $disposition;
                }

                $lockedHeader->status = 3; // Cancel
                $lockedHeader->accept_note = $isProduction
                    ? ($notes !== '' ? $notes : $lockedHeader->accept_note)
                    : ($req->accept_note ?? $req->note ?? $lockedHeader->accept_note);
                $lockedHeader->acc_by = Session::get('user')->staff_id ?? null;
                $lockedHeader->save();
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => -1,
                'message' => $e->getMessage() ?: 'Gagal cancel stock transfer',
            ]);
        }

        $after = $this->snapshotTransfer((int) $header->st_id);
        $this->logTransferAction('reject', $after['header'] ?: $before['header'], [
            'disposition' => $isProduction ? $disposition : null,
        ], $before, $after);

        return response()->json([
            'status' => 1,
            'message' => $isProduction
                ? 'Stock transfer produksi dibatalkan; stok tetap di gudang asal'
                : 'Stock transfer ditolak (Cancel). Stok belum dipotong.',
        ]);
    }

    /**
     * Cancel Kirim: Kirim → Cancel Kirim, restore stok sumber.
     */
    public function cancelKirimStockTransfer(Request $req)
    {
        $stId = (int) ($req->id ?? $req->st_id ?? 0);
        $header = StockTransfer::query()->where('st_id', $stId)->where('status', 2)->first();
        if (! $header) {
            return response()->json(['status' => -1, 'message' => 'Hanya transfer berstatus Kirim yang bisa di-cancel kirim']);
        }

        $gate = $this->assertCanCancelKirim($header);
        if ($gate !== true) {
            return response()->json(['status' => -1, 'message' => $gate]);
        }

        $before = $this->snapshotTransfer((int) $header->st_id);
        try {
            DB::transaction(function () use ($stId, $req) {
                $lockedHeader = StockTransfer::query()
                    ->where('st_id', $stId)
                    ->where('status', 2)
                    ->lockForUpdate()
                    ->first();
                if (! $lockedHeader) {
                    throw new \RuntimeException('Transfer sudah diproses');
                }

                $this->restoreSourceStock($lockedHeader, 'cancel kirim');

                $lockedHeader->status = 5; // Cancel Kirim
                $lockedHeader->accept_note = $req->input('accept_note')
                    ?? $req->input('note')
                    ?? $lockedHeader->accept_note;
                $lockedHeader->save();
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => -1,
                'message' => $e->getMessage() ?: 'Gagal cancel kirim stock transfer',
            ]);
        }

        $after = $this->snapshotTransfer((int) $header->st_id);
        $this->logTransferAction('cancel_kirim', $after['header'] ?: $before['header'], [
            'items_count' => count($after['items']),
        ], $before, $after);

        return response()->json([
            'status' => 1,
            'message' => 'Cancel Kirim berhasil, stok dikembalikan ke gudang asal',
        ]);
    }

    /**
     * Cancel Kirim: kembalikan stok gudang asal ke komposisi satuan PERSIS sebelum Kirim
     * ("anggapannya DOS belum dibuka") — bukan cuma addQty ke satuan yang dikirim, karena
     * Kirim bisa saja merepacking seluruh chain (bahan/hasil packing, bongkar/hasil bongkar).
     * Caranya: reverse net delta per (varian, satuan) dari log Kirim (`log_kode` = kode ST ini,
     * gudang = gudang asal) — ini menangkap semua baris log yang ditulis saat Kirim sekaligus,
     * apapun bentuknya (packing atau non-packing).
     */
    protected function restoreSourceStock(StockTransfer $header, string $reason): void
    {
        $details = StockTransferDetail::query()
            ->where('st_id', $header->st_id)
            ->where('status', 1)
            ->get();
        if ($details->isEmpty()) {
            return;
        }

        $code = $header->transfer_code;
        $warehouseId = (int) $header->from_warehouse_id;
        $variantIds = $details->pluck('product_variant_id')->map(fn ($id) => (int) $id)->unique()->values();
        $productIdByVariant = $details->pluck('product_id', 'product_variant_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $shipLogs = LogStock::query()
            ->where('status', 1)
            ->where('log_type', 1)
            ->where('log_kode', $code)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('log_item_id', $variantIds)
            ->get(['log_item_id', 'unit_id', 'log_category', 'log_jumlah']);

        // Net delta per (varian, satuan) dari log Kirim: kategori 1 (masuk/hasil bongkar/hasil
        // packing) = +, kategori 2 (keluar/bahan packing) = -.
        $netDeltas = [];
        foreach ($shipLogs as $log) {
            $key = ((int) $log->log_item_id) . ':' . ((int) $log->unit_id);
            $sign = (int) $log->log_category === 1 ? 1 : -1;
            $netDeltas[$key] = ($netDeltas[$key] ?? 0.0) + $sign * (float) $log->log_jumlah;
        }

        if ($netDeltas === []) {
            // Data lama / tanpa log Kirim (harusnya tidak terjadi) — fallback ke satuan yang dikirim saja.
            foreach ($details as $d) {
                $add = ProductUnitStock::addQty(
                    $warehouseId,
                    (int) $d->product_id,
                    (int) $d->product_variant_id,
                    (int) $d->unit_id,
                    (float) $d->qty,
                    $code,
                    'Stock Transfer ' . $code . ' - kembalikan stok (' . $reason . ')'
                );
                if (! $add['ok']) {
                    throw new \RuntimeException($add['message'] ?? 'Gagal kembalikan stok');
                }
            }

            return;
        }

        foreach ($netDeltas as $key => $delta) {
            if (abs($delta) < 1e-9) {
                continue;
            }
            [$variantId, $unitId] = array_map('intval', explode(':', $key));
            // Kirim mengurangi stok sumber secara agregat (delta bersih negatif); restore =
            // kebalikannya. Satuan yang justru NAIK saat Kirim (hasil bongkar/hasil packing)
            // di-restore dengan mengurangi lagi (persis reverse).
            $restoreQty = round(-$delta, 4);
            $productId = (int) ($productIdByVariant[$variantId] ?? 0);
            $note = 'Stock Transfer ' . $code . ' - kembalikan stok (' . $reason . ', komposisi asli)';

            if ($restoreQty > 0) {
                $result = ProductUnitStock::addQty(
                    $warehouseId,
                    $productId,
                    $variantId,
                    $unitId,
                    $restoreQty,
                    $code,
                    $note
                );
            } else {
                $result = ProductUnitStock::deductQty(
                    $warehouseId,
                    $variantId,
                    $unitId,
                    abs($restoreQty),
                    $code,
                    $note,
                    false,
                    false
                );
            }

            if (! ($result['ok'] ?? false)) {
                throw new \RuntimeException($result['message'] ?? 'Gagal kembalikan stok (komposisi asli)');
            }
        }
    }

    /**
     * @return array{error:?string, transfer_date?:string, sender_id?:int, receiver_id?:?int, from_warehouse_id?:int, to_warehouse_id?:int, note?:?string}
     */
    protected function parseHeaderPayload(Request $req): array
    {
        $user = Session::get('user');
        $senderId = (int) ($user->staff_id ?? 0);
        $fromId = (int) ($req->from_warehouse_id ?? 0);
        $toId = (int) ($req->to_warehouse_id ?? 0);
        $dateRaw = trim((string) ($req->transfer_date ?? ''));
        $note = $req->note ?? null;

        if ($senderId <= 0 || $fromId <= 0 || $toId <= 0 || $dateRaw === '') {
            return ['error' => 'Lengkapi pengirim, gudang, dan tanggal'];
        }
        if ($fromId === $toId) {
            return ['error' => 'Gudang asal dan tujuan tidak boleh sama'];
        }

        try {
            if (Carbon::hasFormat($dateRaw, 'Y-m-d')) {
                $date = Carbon::createFromFormat('Y-m-d', $dateRaw)->format('Y-m-d');
            } else {
                $date = Carbon::createFromFormat('d-m-Y', $dateRaw)->format('Y-m-d');
            }
        } catch (Throwable $e) {
            return ['error' => 'Format tanggal tidak valid'];
        }

        return [
            'error' => null,
            'transfer_date' => $date,
            'sender_id' => $senderId,
            'receiver_id' => null,
            'from_warehouse_id' => $fromId,
            'to_warehouse_id' => $toId,
            'note' => $note,
        ];
    }

    /**
     * Validasi create + tandai request eceran (source_type=retail_request).
     * Mutates $payload.
     *
     * @param  array<string, mixed>  $payload
     * @return true|string
     */
    protected function assertAndNormalizeCreatePayload(array &$payload)
    {
        $user = Session::get('user');
        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        $assignedWh = $user ? Staff::assignedWarehouseIds($user) : [];
        $fromId = (int) ($payload['from_warehouse_id'] ?? 0);
        $toId = (int) ($payload['to_warehouse_id'] ?? 0);

        if ($activeWh <= 0) {
            return 'Pilih gudang aktif terlebih dahulu';
        }

        $fromIsMain = $this->warehouseIsMain($fromId);
        $toIsMain = $this->warehouseIsMain($toId);
        $activeIsMain = $this->warehouseIsMain($activeWh);

        // Request eceran: aktif = eceran = penerima, request = gudang utama
        if ($activeIsMain === false && $fromIsMain === true && $toIsMain === false) {
            if ($toId !== $activeWh) {
                return 'Gudang yang menerima harus sama dengan gudang aktif';
            }
            if ($assignedWh !== [] && ! in_array($activeWh, $assignedWh, true)) {
                return 'Anda tidak punya akses ke gudang penerima (gudang aktif)';
            }
            $payload['source_type'] = 'retail_request';

            return true;
        }

        // Transfer biasa: asal wajib = gudang aktif
        if ($fromId !== $activeWh) {
            return 'Gudang asal harus sama dengan gudang aktif (kecuali request stok dari gudang utama)';
        }
        if ($assignedWh !== [] && ! in_array($fromId, $assignedWh, true)) {
            return 'Anda tidak punya akses ke gudang asal transfer ini';
        }

        return true;
    }

    /**
     * @param  mixed  $items
     * @return array<int, array{product_variant_id:int, unit_id:int, qty:float, label:string}>
     */
    protected function normalizeItems($items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $normalized = [];
        foreach ($items as $item) {
            $variantId = (int) ($item['product_variant_id'] ?? 0);
            $unitId = (int) ($item['unit_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            if ($variantId <= 0 || $unitId <= 0 || $qty <= 0) {
                continue;
            }

            $label = $item['label'] ?? null;
            if (! $label) {
                $pv = ProductVariant::query()->find($variantId);
                $pr = $pv ? Product::query()->find($pv->product_id) : null;
                $label = trim(
                    ($pr->product_name ?? '') . ' ' . ($pv->product_variant_name ?? '')
                ) ?: ('Variant #' . $variantId);
            }

            $key = $variantId . ':' . $unitId;
            if (isset($normalized[$key])) {
                $normalized[$key]['qty'] += $qty;
                continue;
            }

            $normalized[$key] = [
                'product_variant_id' => $variantId,
                'unit_id' => $unitId,
                'qty' => $qty,
                'label' => $label,
            ];
        }

        return array_values($normalized);
    }

    protected function warehouseIsMain(int $warehouseId): ?bool
    {
        $warehouse = Warehouse::query()
            ->with('type:id,is_main_warehouse')
            ->find($warehouseId);

        return $warehouse && $warehouse->type
            ? (int) $warehouse->type->is_main_warehouse === 1
            : null;
    }

    /**
     * @return array{error:?string,target_unit_id:?int}
     */
    protected function resolveTransferUnits(
        ?bool $sourceIsMain,
        ?bool $destinationIsMain,
        ?ProductVariant $variant,
        ?Product $product,
        int $sentUnitId,
        bool $isProduction = false
    ): array {
        if ($sourceIsMain === null) {
            return ['error' => 'Tipe gudang transfer tidak valid', 'target_unit_id' => null];
        }
        if (! $variant || ! $product) {
            return ['error' => 'Produk transfer tidak ditemukan', 'target_unit_id' => null];
        }

        $defaultUnitId = (int) ($product->unit_id ?? 0);
        $retailUnitId = (int) ($variant->retail_unit ?? 0);

        if ($defaultUnitId <= 0) {
            return [
                'error' => 'Satuan default produk belum diatur untuk produk #' . $product->product_id,
                'target_unit_id' => null,
            ];
        }
        if (! Unit::query()->where('unit_id', $sentUnitId)->exists()) {
            return ['error' => 'Satuan kirim tidak ditemukan', 'target_unit_id' => null];
        }

        // ST produksi: simpan satuan input (mis. piece) apa adanya.
        if ($isProduction) {
            if ($destinationIsMain === null) {
                return ['error' => null, 'target_unit_id' => $sentUnitId];
            }
            if ($destinationIsMain) {
                return ['error' => null, 'target_unit_id' => $sentUnitId];
            }
            if ($retailUnitId <= 0) {
                return [
                    'error' => 'Satuan retail belum diatur untuk varian #' . $variant->product_variant_id,
                    'target_unit_id' => null,
                ];
            }
            if ($sentUnitId !== $retailUnitId
                && ! ProductUnitStock::canConvertUnits(
                    $sentUnitId,
                    $retailUnitId,
                    (int) $variant->product_variant_id
                )) {
                return [
                    'error' => 'Satuan hasil produksi tidak dapat dikonversi ke satuan eceran',
                    'target_unit_id' => null,
                ];
            }

            return [
                'error' => null,
                'target_unit_id' => $sentUnitId === $retailUnitId ? $sentUnitId : $retailUnitId,
            ];
        }

        // Gudang utama: multi satuan (harus bisa dikonversi ke default).
        // Gudang eceran: hanya satuan eceran.
        if ($sourceIsMain) {
            if ($sentUnitId !== $defaultUnitId
                && ! ProductUnitStock::canConvertUnits(
                    $sentUnitId,
                    $defaultUnitId,
                    (int) $variant->product_variant_id
                )) {
                return [
                    'error' => 'Satuan kirim tidak berada dalam rantai konversi satuan default',
                    'target_unit_id' => null,
                ];
            }
        } else {
            if ($retailUnitId <= 0) {
                return [
                    'error' => 'Satuan retail belum diatur untuk varian #'
                        . $variant->product_variant_id,
                    'target_unit_id' => null,
                ];
            }
            if ($sentUnitId !== $retailUnitId) {
                return [
                    'error' => 'Gudang eceran hanya boleh mengirim dalam satuan eceran',
                    'target_unit_id' => null,
                ];
            }
        }

        // Tujuan belum dipilih: aturan sumber sudah cukup.
        if ($destinationIsMain === null) {
            return ['error' => null, 'target_unit_id' => $sentUnitId];
        }

        // Tujuan gudang utama: terima apa adanya sesuai satuan kirim (eceran→utama /
        // utama→utama). Real case: barang tidak di-kardusin ulang / tidak diubah packaging
        // saat masuk — kirim Piece tetap Piece, kirim Jerigen tetap Jerigen.
        // Tujuan eceran: wajib retail_unit (eceran cuma pegang satuan eceran).
        if ($destinationIsMain) {
            $targetUnitId = $sentUnitId;
        } else {
            if ($retailUnitId <= 0) {
                return [
                    'error' => 'Satuan retail belum diatur untuk varian #' . $variant->product_variant_id,
                    'target_unit_id' => null,
                ];
            }
            $targetUnitId = $retailUnitId;
        }

        if (! Unit::query()->where('unit_id', $targetUnitId)->exists()) {
            return ['error' => 'Satuan tujuan tidak ditemukan', 'target_unit_id' => null];
        }

        if (! ProductUnitStock::canConvertUnits(
            $sentUnitId,
            $targetUnitId,
            (int) $variant->product_variant_id
        )) {
            return [
                'error' => 'Satuan kirim dan satuan tujuan tidak berada dalam rantai konversi yang sama',
                'target_unit_id' => null,
            ];
        }

        return ['error' => null, 'target_unit_id' => $targetUnitId];
    }

    protected function validRetailUnitsForVariant(ProductVariant $variant)
    {
        $product = Product::query()
            ->where('product_id', $variant->product_id)
            ->where('status', 1)
            ->first(['product_id', 'product_unit']);
        $unitIds = collect(json_decode($product?->product_unit ?? '[]', true) ?: [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        return $unitIds->isEmpty()
            ? collect()
            : Unit::query()
                ->whereIn('unit_id', $unitIds)
                ->get(['unit_id', 'unit_name', 'unit_short_name'])
                ->sortBy(fn ($unit) => $unitIds->search((int) $unit->unit_id))
                ->values();
    }

    protected function authorizeRetailUnitSetup(): void
    {
        $user = Session::get('user');
        if (! RoleAccess::can($user, 'Stock Transfer', 'create')
            && ! RoleAccess::can($user, 'Stock Transfer', 'edit')) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * @param  array<int, array{product_variant_id:int,unit_id:int,qty:float,label?:string}>  $items
     * @return array{ok:bool,message:string,invalid_variant_ids:array<int,int>}
     */
    protected function validateTransferItems(
        int $fromWarehouseId,
        ?int $toWarehouseId,
        array $items,
        bool $isProduction = false
    ): array {
        $sourceIsMain = $this->warehouseIsMain($fromWarehouseId);
        $destinationIsMain = $toWarehouseId ? $this->warehouseIsMain($toWarehouseId) : null;
        if ($sourceIsMain === null || ($toWarehouseId && $destinationIsMain === null)) {
            return [
                'ok' => false,
                'message' => 'Gudang atau tipe gudang transfer tidak valid',
                'invalid_variant_ids' => [],
            ];
        }

        $variantIds = collect($items)->pluck('product_variant_id')->map(fn ($id) => (int) $id)->unique();
        $variants = ProductVariant::query()
            ->whereIn('product_variant_id', $variantIds)
            ->get()
            ->keyBy('product_variant_id');
        $products = Product::query()
            ->whereIn('product_id', $variants->pluck('product_id')->unique())
            ->get()
            ->keyBy('product_id');
        $errors = [];
        $invalidVariantIds = [];

        foreach ($items as $item) {
            $variantId = (int) ($item['product_variant_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            if ($qty <= 0 || abs($qty - round($qty)) > 1e-9) {
                $label = trim((string) ($item['label'] ?? '')) ?: ('Varian #' . $variantId);
                $errors[] = $label . ': qty kirim harus berupa bilangan bulat positif';
                $invalidVariantIds[] = $variantId;
                continue;
            }
            $variant = $variants->get($variantId);
            $product = $variant ? $products->get($variant->product_id) : null;
            $resolution = $this->resolveTransferUnits(
                $sourceIsMain,
                $toWarehouseId ? $destinationIsMain : null,
                $variant,
                $product,
                (int) ($item['unit_id'] ?? 0),
                $isProduction
            );
            if (! $resolution['error']) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? '')) ?: ('Varian #' . $variantId);
            $errors[] = $label . ': ' . $resolution['error'];
            $invalidVariantIds[] = $variantId;
        }

        return [
            'ok' => $errors === [],
            'message' => $errors === [] ? 'Matriks satuan valid' : implode('; ', array_unique($errors)),
            'invalid_variant_ids' => array_values(array_unique($invalidVariantIds)),
        ];
    }

    /**
     * Mode cek/potong stok untuk gudang asal.
     *
     * Packing/rapikan tetap OFF di Kirim. Gudang utama: allow_unpack=true
     * (boleh bongkar ancestor agar available ekuivalen). Gudang eceran: unpack
     * OFF. Konversi antar-satuan tujuan hanya di Terima ke eceran.
     *
     * @param  array<int, array>  $items
     * @return array<int, array>
     */
    protected function applySourceAvailabilityMode(
        array $items,
        ?bool $sourceIsMain,
        bool $isProduction = false
    ): array {
        return array_map(function ($item) use ($sourceIsMain) {
            $item['allow_packing'] = false;
            $item['allow_unpack'] = $sourceIsMain === true;
            return $item;
        }, $items);
    }

    protected function filterSourceSnapshot(
        array $snapshot,
        ?bool $sourceIsMain,
        int $defaultUnitId
    ): array
    {
        $units = collect((array) ($snapshot['units'] ?? []));
        if ($sourceIsMain === null) {
            $units = collect();
        }
        // Gudang utama: multi satuan. Gudang eceran: hanya retail (dari sourceSnapshot).
        $units = $units->values();

        $snapshot['units'] = $units->all();
        $snapshot['unit_order'] = $units->pluck('unit_id')->map(fn ($id) => (int) $id)->all();
        $snapshot['stock_text'] = $units->isEmpty()
            ? '0'
            : $units->map(fn ($unit) => ($unit['ps_stock_text'] ?? $unit['ps_stock'] ?? 0)
                . ' ' . ($unit['unit_name'] ?? $unit['unit_short_name'] ?? '-'))->implode(', ');

        return $snapshot;
    }

    /**
     * Kirim (Pending→Kirim) hanya di gudang asal.
     *
     * @param  array<int, int>  $assignedWh
     */
    protected function canShipTransferRow(
        int $status,
        int $fromWarehouseId,
        int $staffId,
        int $activeWarehouseId,
        array $assignedWh
    ): bool {
        if ($status !== 1 || $fromWarehouseId <= 0 || $staffId <= 0 || $activeWarehouseId <= 0) {
            return false;
        }
        if ($activeWarehouseId !== $fromWarehouseId) {
            return false;
        }
        if ($assignedWh !== [] && ! in_array($fromWarehouseId, $assignedWh, true)) {
            return false;
        }

        return true;
    }

    /**
     * Terima (Kirim→Terkirim) hanya gudang tujuan.
     *
     * @param  array<int, int>  $assignedWh
     */
    protected function canAccTransferRow(
        int $status,
        int $senderId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $staffId,
        int $activeWarehouseId,
        array $assignedWh,
        bool $allowSameWarehouse = false
    ): bool {
        if ($status !== 2 || $toWarehouseId <= 0 || $staffId <= 0 || $activeWarehouseId <= 0) {
            return false;
        }
        // Gudang aktif wajib = tujuan
        if ($activeWarehouseId !== $toWarehouseId) {
            return false;
        }
        // Tolak jika gudang aktif masih gudang asal
        if (! $allowSameWarehouse && $fromWarehouseId > 0 && $activeWarehouseId === $fromWarehouseId) {
            return false;
        }
        // Harus assigned ke gudang tujuan (kalau ada daftar assign)
        if ($assignedWh !== [] && ! in_array($toWarehouseId, $assignedWh, true)) {
            return false;
        }

        return true;
    }

    protected function canEditTransferRow(
        int $status,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $activeWarehouseId,
        bool $isRetailRequest,
        $header
    ): bool {
        if ($status !== 1 || $activeWarehouseId <= 0) {
            return false;
        }
        // Request eceran→besar: eceran (tujuan) boleh edit selama belum ada approval.
        if ($isRetailRequest
            && $toWarehouseId > 0
            && $activeWarehouseId === $toWarehouseId
            && ! StockTransferApproval::isQcApproved($header)
            && ! StockTransferApproval::isOpsApproved($header)) {
            return true;
        }
        if ($fromWarehouseId <= 0) {
            return false;
        }

        return $activeWarehouseId === $fromWarehouseId;
    }

    /**
     * Cancel Kirim di gudang asal; untuk utama→eceran juga boleh di gudang tujuan (Tolak terima).
     *
     * @param  array<int, int>  $assignedWh
     */
    protected function canCancelKirimTransferRow(
        int $status,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $staffId,
        int $activeWarehouseId,
        array $assignedWh,
        bool $isRetailRequest = false
    ): bool {
        if ($status !== 2 || $staffId <= 0 || $activeWarehouseId <= 0) {
            return false;
        }
        if ($activeWarehouseId === $fromWarehouseId && $fromWarehouseId > 0) {
            return $assignedWh === [] || in_array($fromWarehouseId, $assignedWh, true);
        }
        if ($isRetailRequest
            && $toWarehouseId > 0
            && $activeWarehouseId === $toWarehouseId) {
            return $assignedWh === [] || in_array($toWarehouseId, $assignedWh, true);
        }

        return false;
    }

    /** @return true|string */
    protected function assertCanShip(StockTransfer $header)
    {
        $user = Session::get('user');
        $staffId = (int) ($user->staff_id ?? 0);
        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        $assignedWh = $user ? Staff::assignedWarehouseIds($user) : [];
        $fromWh = (int) $header->from_warehouse_id;
        $toWh = (int) $header->to_warehouse_id;

        if ((int) $header->status !== 1) {
            return 'Transfer sudah diproses';
        }
        if ($activeWh <= 0 || $activeWh !== $fromWh) {
            return 'Kirim hanya bisa dilakukan di gudang asal. Ganti gudang aktif ke gudang asal.';
        }
        if ($staffId <= 0) {
            return 'User login tidak valid';
        }
        if ($assignedWh !== [] && ! in_array($fromWh, $assignedWh, true)) {
            return 'Anda tidak punya akses ke gudang asal transfer ini';
        }

        $requiresApproval = StockTransferApproval::requiresApproval(
            $header->source_type,
            $this->warehouseIsMain($fromWh),
            $this->warehouseIsMain($toWh),
            $fromWh
        );
        if ($requiresApproval && ! StockTransferApproval::isFullyApproved($header, $fromWh)) {
            $missing = [];
            if (StockTransferApproval::qcRequiredAtWarehouse($fromWh)
                && ! StockTransferApproval::isQcApproved($header)) {
                $missing[] = 'QC';
            }
            if (StockTransferApproval::opsRequiredAtWarehouse($fromWh)
                && ! StockTransferApproval::isOpsApproved($header)) {
                $missing[] = 'Kepala Operasional';
            }

            return 'Belum lengkap approval: ' . implode(' → ', $missing);
        }

        return true;
    }

    /** @return true|string */
    protected function assertCanAcc(StockTransfer $header)
    {
        $user = Session::get('user');
        $staffId = (int) ($user->staff_id ?? 0);
        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        $assignedWh = $user ? Staff::assignedWarehouseIds($user) : [];
        $fromWh = (int) $header->from_warehouse_id;
        $toWh = (int) $header->to_warehouse_id;

        if ((int) $header->status !== 2) {
            return 'Transfer harus berstatus Kirim sebelum diterima (Terkirim)';
        }
        if ($activeWh <= 0 || $activeWh !== $toWh) {
            return 'ACC hanya bisa dilakukan di gudang tujuan. Ganti gudang aktif ke gudang tujuan.';
        }
        if ($header->source_type !== 'production' && $fromWh > 0 && $activeWh === $fromWh) {
            return 'Gudang asal tidak bisa ACC. ACC hanya di gudang tujuan.';
        }
        if ($staffId <= 0) {
            return 'User login tidak valid';
        }
        if ($assignedWh !== [] && ! in_array($toWh, $assignedWh, true)) {
            return 'Anda tidak punya akses ke gudang tujuan transfer ini';
        }

        return true;
    }

    /** Edit/hapus: gudang asal, atau eceran (tujuan) untuk request utama→eceran sebelum approval. @return true|string */
    protected function assertCanEditSource(StockTransfer $header)
    {
        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        $fromWh = (int) $header->from_warehouse_id;
        $toWh = (int) $header->to_warehouse_id;

        if ((int) $header->status !== 1) {
            return 'Transfer sudah diproses';
        }
        if ($activeWh <= 0) {
            return 'Edit/hapus membutuhkan gudang aktif.';
        }

        $isRetailRequest = StockTransferApproval::isRetailRequestRoute(
            $header->source_type,
            $this->warehouseIsMain($fromWh),
            $this->warehouseIsMain($toWh)
        );
        if ($isRetailRequest
            && $activeWh === $toWh
            && ! StockTransferApproval::isQcApproved($header)
            && ! StockTransferApproval::isOpsApproved($header)) {
            return true;
        }
        if ($activeWh !== $fromWh) {
            return 'Edit/hapus hanya bisa dilakukan di gudang asal'
                . ($isRetailRequest ? ' (atau gudang eceran sebelum approval)' : '')
                . '.';
        }

        return true;
    }

    /** Cancel Pending: gudang asal, atau eceran (requester) untuk utama→eceran. @return true|string */
    protected function assertCanReject(StockTransfer $header)
    {
        $user = Session::get('user');
        $staffId = (int) ($user->staff_id ?? 0);
        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        $assignedWh = $user ? Staff::assignedWarehouseIds($user) : [];
        $fromWh = (int) $header->from_warehouse_id;
        $toWh = (int) $header->to_warehouse_id;

        if ((int) $header->status !== 1) {
            return 'Hanya transfer pending yang bisa di-cancel';
        }
        if ($staffId <= 0) {
            return 'User login tidak valid';
        }

        $isRetailRequest = StockTransferApproval::isRetailRequestRoute(
            $header->source_type,
            $this->warehouseIsMain($fromWh),
            $this->warehouseIsMain($toWh)
        );
        if ($activeWh === $fromWh && $fromWh > 0) {
            if ($assignedWh !== [] && ! in_array($fromWh, $assignedWh, true)) {
                return 'Anda tidak punya akses ke gudang asal transfer ini';
            }
            if ($isRetailRequest && ! StockTransferApproval::canRejectAtOrigin($user, $header, $fromWh)) {
                return 'Tolak request di gudang besar hanya oleh Staf QC atau Kepala Operasional (Ops setelah QC approve)';
            }

            return true;
        }
        if ($isRetailRequest && $activeWh === $toWh && $toWh > 0) {
            if ($assignedWh !== [] && ! in_array($toWh, $assignedWh, true)) {
                return 'Anda tidak punya akses ke gudang eceran transfer ini';
            }
            if (! StockTransferApproval::canCancelRetailRequestAtDestination($user, $header, $toWh, $fromWh)) {
                return 'Cancel hanya oleh pemohon request. Kepala Operasional gudang asal menunggu approval QC terlebih dahulu';
            }

            return true;
        }

        return 'Cancel hanya bisa dilakukan di gudang asal'
            . ($isRetailRequest ? ' atau gudang eceran pemohon' : '')
            . '.';
    }

    /** Cancel Kirim: gudang asal, atau tujuan (Tolak terima) untuk utama→eceran. @return true|string */
    protected function assertCanCancelKirim(StockTransfer $header)
    {
        $user = Session::get('user');
        $staffId = (int) ($user->staff_id ?? 0);
        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        $assignedWh = $user ? Staff::assignedWarehouseIds($user) : [];
        $fromWh = (int) $header->from_warehouse_id;
        $toWh = (int) $header->to_warehouse_id;

        if ((int) $header->status !== 2) {
            return 'Hanya transfer berstatus Kirim yang bisa di-cancel kirim';
        }
        if ($staffId <= 0) {
            return 'User login tidak valid';
        }

        $isRetailRequest = StockTransferApproval::isRetailRequestRoute(
            $header->source_type,
            $this->warehouseIsMain($fromWh),
            $this->warehouseIsMain($toWh)
        );
        if ($activeWh === $fromWh && $fromWh > 0) {
            if ($assignedWh !== [] && ! in_array($fromWh, $assignedWh, true)) {
                return 'Anda tidak punya akses ke gudang asal transfer ini';
            }

            return true;
        }
        if ($isRetailRequest && $activeWh === $toWh && $toWh > 0) {
            if ($assignedWh !== [] && ! in_array($toWh, $assignedWh, true)) {
                return 'Anda tidak punya akses ke gudang tujuan transfer ini';
            }

            return true;
        }

        return 'Cancel Kirim hanya di gudang asal'
            . ($isRetailRequest ? ' atau Tolak di gudang eceran tujuan' : '')
            . '.';
    }

    /**
     * @param  array<int, int>  $warehouseIds
     * @return array<int, bool|null>
     */
    protected function warehouseMainFlags(array $warehouseIds): array
    {
        $warehouseIds = array_values(array_unique(array_filter(array_map('intval', $warehouseIds))));
        if ($warehouseIds === []) {
            return [];
        }

        $rows = Warehouse::query()
            ->with(['type' => fn ($q) => $q->select('id', 'is_main_warehouse')])
            ->whereIn('id', $warehouseIds)
            ->get(['id', 'warehouse_type_id']);

        $map = [];
        foreach ($rows as $wh) {
            $map[(int) $wh->id] = $wh->type
                ? ((int) ($wh->type->is_main_warehouse ?? 0) === 1)
                : null;
        }

        return $map;
    }

    private function snapshotTransfer(int $stId): array
    {
        $header = StockTransfer::query()->where('st_id', $stId)->first();
        if (! $header) {
            return ['header' => null, 'items' => []];
        }

        $details = StockTransferDetail::query()
            ->where('st_id', $stId)
            ->where('status', 1)
            ->orderBy('std_id')
            ->get([
                'std_id',
                'product_variant_id',
                'unit_id',
                'received_unit_id',
                'qty',
                'qty_received',
                'status',
            ]);

        return [
            'header' => [
                'st_id' => (int) $header->st_id,
                'transfer_code' => (string) ($header->transfer_code ?? ''),
                'transfer_date' => $header->transfer_date ? (string) $header->transfer_date : null,
                'sender_id' => $header->sender_id ? (int) $header->sender_id : null,
                'receiver_id' => $header->receiver_id ? (int) $header->receiver_id : null,
                'from_warehouse_id' => $header->from_warehouse_id ? (int) $header->from_warehouse_id : null,
                'to_warehouse_id' => $header->to_warehouse_id ? (int) $header->to_warehouse_id : null,
                'note' => $header->note,
                'accept_note' => $header->accept_note,
                'source_type' => $header->source_type,
                'source_id' => $header->source_id ? (int) $header->source_id : null,
                'disposition' => $header->disposition,
                'status' => (int) $header->status,
                'acc_by' => $header->acc_by ? (int) $header->acc_by : null,
                'qc_approved_by' => $header->qc_approved_by ? (int) $header->qc_approved_by : null,
                'qc_approved_at' => $header->qc_approved_at ? (string) $header->qc_approved_at : null,
                'ops_approved_by' => $header->ops_approved_by ? (int) $header->ops_approved_by : null,
                'ops_approved_at' => $header->ops_approved_at ? (string) $header->ops_approved_at : null,
            ],
            'items' => $details->map(function ($d) use ($header) {
                return [
                    'std_id' => (int) $d->std_id,
                    'product_variant_id' => (int) $d->product_variant_id,
                    'unit_id' => (int) $d->unit_id,
                    'received_unit_id' => $d->received_unit_id
                        ? (int) $d->received_unit_id
                        : ((int) $header->status === 4 ? (int) $d->unit_id : null),
                    'qty' => (float) $d->qty,
                    'qty_received' => $d->qty_received !== null ? (float) $d->qty_received : null,
                ];
            })->values()->all(),
        ];
    }

    private function logTransferAction(
        string $action,
        ?array $headerSnapshot,
        array $extraMeta = [],
        ?array $before = null,
        ?array $after = null
    ): void {
        if (! $headerSnapshot) {
            return;
        }

        try {
            $user = Session::get('user');
            $staffId = (int) ($user->staff_id ?? 0);
            $transferCode = (string) ($headerSnapshot['transfer_code'] ?? '');
            $transferId = (int) ($headerSnapshot['st_id'] ?? 0);

            $whatChangedMap = [
                'create' => 'Buat stock transfer',
                'update' => 'Edit stock transfer',
                'delete' => 'Hapus stock transfer',
                'ship' => 'ACC kirim stock transfer',
                'accept' => 'ACC terkirim stock transfer',
                'reject' => 'Cancel stock transfer',
                'cancel_kirim' => 'Cancel kirim stock transfer',
            ];

            DashboardChangeLog::create([
                'module_key' => self::TRANSFER_LOG_MODULE_KEY,
                'module_label' => 'Stock Transfer',
                'reference' => $transferCode !== '' ? $transferCode : ('ST #' . $transferId),
                'what_changed' => $whatChangedMap[$action] ?? ('Aksi stock transfer: ' . $action),
                'summary' => 'Action: ' . $action
                    . ' | Status: ' . ((int) ($headerSnapshot['status'] ?? 0))
                    . (! empty($headerSnapshot['source_type'])
                        ? ' | Source: ' . $headerSnapshot['source_type'] . '#' . ($headerSnapshot['source_id'] ?? '-')
                        : '')
                    . (! empty($headerSnapshot['disposition'])
                        ? ' | Disposition: ' . $headerSnapshot['disposition']
                        : ''),
                'url' => url('stockTransfer') . ($transferId > 0 ? ('?st_id=' . $transferId) : ''),
                'url_label' => 'Buka Stock Transfer',
                'created_by' => $staffId > 0 ? $staffId : null,
                'meta' => array_merge([
                    'action' => $action,
                    'transfer_id' => $transferId,
                    'transfer_code' => $transferCode,
                    'before' => $before,
                    'after' => $after,
                ], $extraMeta),
            ]);
        } catch (Throwable $e) {
            // Logging tidak boleh memblokir flow utama.
        }
    }

    public function logProductionTransferCreated(int $stId, string $origin = 'production'): void
    {
        $snapshot = $this->snapshotTransfer($stId);
        $this->logTransferAction('create', $snapshot['header'], [
            'items_count' => count($snapshot['items']),
            'automatic' => true,
            'origin' => $origin,
        ], null, $snapshot);
    }
}
