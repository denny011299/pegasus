<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Staff;
use App\Models\StockTransfer;
use App\Models\StockTransferDetail;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Support\ProductUnitStock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Throwable;

/**
 * Stock Transfer
 * Status: 0=deleted, 1=pending, 2=success (ACC), 3=rejected
 *
 * Create  → potong stok gudang asal (bongkar satuan atas jika perlu), status=1
 * ACC     → tambah stok gudang tujuan (qty_received), status=2
 * Delete  → kembalikan stok asal (pending saja), status=0
 * Reject  → kembalikan stok asal, status=3
 * Update  → restore item lama + potong item baru (pending saja)
 */
class StockTransferController extends Controller
{
    public function index()
    {
        return view('Backoffice.Inventory.Stock_Transfer');
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
            ->orderByRaw('CASE WHEN status = 1 THEN 0 ELSE 1 END')
            ->orderByDesc('transfer_date')
            ->orderByDesc('st_id')
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([]);
        }

        $staffIds = $rows->pluck('sender_id')
            ->merge($rows->pluck('receiver_id'))
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

        $data = $rows->map(function ($row) use ($staffMap, $whMap, $staffId, $activeWh, $assignedWh) {
            $canAcc = $this->canAccTransferRow(
                (int) $row->status,
                (int) $row->sender_id,
                (int) $row->from_warehouse_id,
                (int) $row->to_warehouse_id,
                $staffId,
                $activeWh,
                $assignedWh
            );

            return [
                'id' => (int) $row->st_id,
                'st_id' => (int) $row->st_id,
                'transfer_code' => $row->transfer_code,
                'transfer_date' => Carbon::parse($row->transfer_date)->format('d-m-Y'),
                'sender_id' => (int) $row->sender_id,
                'sender_name' => $staffMap[$row->sender_id] ?? '-',
                'receiver_id' => $row->receiver_id ? (int) $row->receiver_id : null,
                'receiver_name' => $row->receiver_id ? ($staffMap[$row->receiver_id] ?? '-') : '-',
                'from_warehouse_id' => (int) $row->from_warehouse_id,
                'from_warehouse_name' => $whMap[$row->from_warehouse_id] ?? '-',
                'to_warehouse_id' => (int) $row->to_warehouse_id,
                'to_warehouse_name' => $whMap[$row->to_warehouse_id] ?? '-',
                'note' => $row->note,
                'status' => (int) $row->status,
                'can_acc' => $canAcc,
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
        $unitIds = $details->pluck('unit_id')->unique()->all();

        $variants = ProductVariant::query()
            ->whereIn('product_variant_id', $variantIds)
            ->get()
            ->keyBy('product_variant_id');
        $products = Product::query()
            ->whereIn('product_id', $details->pluck('product_id')->unique()->all())
            ->get()
            ->keyBy('product_id');
        $units = Unit::query()
            ->whereIn('unit_id', $unitIds)
            ->get()
            ->keyBy('unit_id');

        $sender = Staff::query()->find($header->sender_id);
        $receiver = $header->receiver_id ? Staff::query()->find($header->receiver_id) : null;
        $fromWh = Warehouse::query()->find($header->from_warehouse_id);
        $toWh = Warehouse::query()->find($header->to_warehouse_id);

        $items = $details->map(function ($d) use ($variants, $products, $units, $header) {
            $pv = $variants[$d->product_variant_id] ?? null;
            $pr = $products[$d->product_id] ?? null;
            $un = $units[$d->unit_id] ?? null;
            $snap = ProductUnitStock::snapshot(
                (int) $header->from_warehouse_id,
                (int) $d->product_variant_id
            );

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
                'qty_received' => $d->qty_received !== null ? (float) $d->qty_received : (float) $d->qty,
                'stock_text' => $snap['stock_text'] ?? '-',
                'units' => $snap['units'] ?? [],
            ];
        })->values();

        return response()->json([
            'id' => (int) $header->st_id,
            'st_id' => (int) $header->st_id,
            'transfer_code' => $header->transfer_code,
            'transfer_date' => Carbon::parse($header->transfer_date)->format('d-m-Y'),
            'sender_id' => (int) $header->sender_id,
            'sender_name' => $sender->staff_name ?? '-',
            'receiver_id' => $header->receiver_id ? (int) $header->receiver_id : null,
            'receiver_name' => $receiver->staff_name ?? '-',
            'from_warehouse_id' => (int) $header->from_warehouse_id,
            'from_warehouse_name' => $fromWh->warehouse_name ?? '-',
            'to_warehouse_id' => (int) $header->to_warehouse_id,
            'to_warehouse_name' => $toWh->warehouse_name ?? '-',
            'note' => $header->note,
            'accept_note' => $header->accept_note,
            'status' => (int) $header->status,
            'items' => $items,
        ]);
    }

    public function getTransferSourceStock(Request $req)
    {
        $warehouseId = (int) ($req->warehouse_id ?? 0);
        $variantId = (int) ($req->product_variant_id ?? 0);

        if ($warehouseId <= 0 || $variantId <= 0) {
            return response()->json([
                'stock_text' => '-',
                'units' => [],
                'unit_order' => [],
            ]);
        }

        ProductUnitStock::clearCache();

        return response()->json(ProductUnitStock::snapshot($warehouseId, $variantId));
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
        $result = ProductUnitStock::checkItems($warehouseId, $normalized);

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

        $items = $this->normalizeItems($req->input('items', []));
        if ($items === []) {
            return response()->json(['status' => -1, 'message' => 'Tambahkan minimal 1 produk']);
        }

        ProductUnitStock::clearCache();
        $check = ProductUnitStock::checkItems($payload['from_warehouse_id'], $items);
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
                $header = StockTransfer::query()->findOrFail($stId);
                $code = $header->transfer_code;

                foreach ($items as $item) {
                    $pv = ProductVariant::query()->find($item['product_variant_id']);
                    $productId = (int) ($pv->product_id ?? 0);

                    $cut = ProductUnitStock::deductQty(
                        $payload['from_warehouse_id'],
                        $item['product_variant_id'],
                        $item['unit_id'],
                        $item['qty'],
                        $code,
                        'Stock Transfer ' . $code . ' - keluar gudang asal'
                    );
                    if (! $cut['ok']) {
                        throw new \RuntimeException($cut['message'] ?? 'Gagal potong stok');
                    }

                    StockTransferDetail::query()->create([
                        'st_id' => $stId,
                        'product_id' => $productId,
                        'product_variant_id' => $item['product_variant_id'],
                        'unit_id' => $item['unit_id'],
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

        return response()->json([
            'status' => 1,
            'id' => $stId,
            'message' => 'Stock transfer berhasil disimpan',
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

        $items = $this->normalizeItems($req->input('items', []));
        if ($items === []) {
            return response()->json(['status' => -1, 'message' => 'Tambahkan minimal 1 produk']);
        }

        try {
            DB::transaction(function () use ($header, $payload, $items) {
                $code = $header->transfer_code;
                $oldDetails = StockTransferDetail::query()
                    ->where('st_id', $header->st_id)
                    ->where('status', 1)
                    ->get();

                // Kembalikan stok asal dari item lama
                foreach ($oldDetails as $old) {
                    $add = ProductUnitStock::addQty(
                        (int) $header->from_warehouse_id,
                        (int) $old->product_id,
                        (int) $old->product_variant_id,
                        (int) $old->unit_id,
                        (float) $old->qty,
                        $code,
                        'Stock Transfer ' . $code . ' - koreksi edit (kembali)'
                    );
                    if (! $add['ok']) {
                        throw new \RuntimeException($add['message'] ?? 'Gagal restore stok');
                    }
                    $old->status = 0;
                    $old->save();
                }

                ProductUnitStock::clearCache();
                $check = ProductUnitStock::checkItems($payload['from_warehouse_id'], $items);
                if (! $check['ok']) {
                    $names = array_map(fn ($s) => $s['label'], $check['shortages']);
                    throw new \RuntimeException('Stok tidak mencukupi: ' . implode(', ', $names));
                }

                $header->transfer_date = $payload['transfer_date'];
                $header->sender_id = $payload['sender_id'];
                $header->receiver_id = $payload['receiver_id'];
                $header->from_warehouse_id = $payload['from_warehouse_id'];
                $header->to_warehouse_id = $payload['to_warehouse_id'];
                $header->note = $payload['note'];
                $header->save();

                foreach ($items as $item) {
                    $pv = ProductVariant::query()->find($item['product_variant_id']);
                    $productId = (int) ($pv->product_id ?? 0);

                    $cut = ProductUnitStock::deductQty(
                        $payload['from_warehouse_id'],
                        $item['product_variant_id'],
                        $item['unit_id'],
                        $item['qty'],
                        $code,
                        'Stock Transfer ' . $code . ' - keluar gudang asal (edit)'
                    );
                    if (! $cut['ok']) {
                        throw new \RuntimeException($cut['message'] ?? 'Gagal potong stok');
                    }

                    StockTransferDetail::query()->create([
                        'st_id' => $header->st_id,
                        'product_id' => $productId,
                        'product_variant_id' => $item['product_variant_id'],
                        'unit_id' => $item['unit_id'],
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

        return response()->json([
            'status' => 1,
            'id' => $stId,
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

        $gate = $this->assertCanEditSource($header);
        if ($gate !== true) {
            return response()->json(['status' => -1, 'message' => $gate]);
        }

        try {
            DB::transaction(function () use ($header) {
                $this->restoreSourceStock($header, 'hapus');
                $header->status = 0;
                $header->save();
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => -1,
                'message' => $e->getMessage() ?: 'Gagal hapus stock transfer',
            ]);
        }

        return response()->json(['status' => 1, 'message' => 'Stock transfer dihapus, stok dikembalikan']);
    }

    public function accStockTransfer(Request $req)
    {
        $stId = (int) ($req->id ?? $req->st_id ?? 0);
        $header = StockTransfer::query()->where('st_id', $stId)->where('status', 1)->first();
        if (! $header) {
            return response()->json(['status' => -1, 'message' => 'Data tidak ditemukan / sudah di-ACC']);
        }

        $gate = $this->assertCanAcc($header);
        if ($gate !== true) {
            return response()->json(['status' => -1, 'message' => $gate]);
        }

        $user = Session::get('user');
        // Penerima bisa diganti di modal; acc_by = siapa yang klik ACC
        $receiverId = (int) ($req->receiver_id ?? $header->receiver_id ?? 0);
        if ($receiverId <= 0) {
            $receiverId = (int) ($user->staff_id ?? 0);
        }
        if ($receiverId <= 0) {
            return response()->json(['status' => -1, 'message' => 'Penerima wajib diisi']);
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
            $receivedMap[$stdId] = (float) ($item['qty_received'] ?? $item['qty'] ?? 0);
        }

        $accBy = (int) ($user->staff_id ?? 0);

        try {
            DB::transaction(function () use ($header, $receiverId, $acceptNote, $receivedMap, $accBy) {
                $details = StockTransferDetail::query()
                    ->where('st_id', $header->st_id)
                    ->where('status', 1)
                    ->get();

                if ($details->isEmpty()) {
                    throw new \RuntimeException('Detail transfer kosong');
                }

                $code = $header->transfer_code;
                foreach ($details as $d) {
                    $qtyReceived = $receivedMap[$d->std_id] ?? (float) $d->qty;
                    if ($qtyReceived < 0) {
                        throw new \RuntimeException('Qty diterima tidak valid');
                    }

                    if ($qtyReceived > 0) {
                        $add = ProductUnitStock::addQty(
                            (int) $header->to_warehouse_id,
                            (int) $d->product_id,
                            (int) $d->product_variant_id,
                            (int) $d->unit_id,
                            $qtyReceived,
                            $code,
                            'Stock Transfer ' . $code . ' - masuk gudang tujuan'
                        );
                        if (! $add['ok']) {
                            throw new \RuntimeException($add['message'] ?? 'Gagal tambah stok tujuan');
                        }
                    }

                    $d->qty_received = $qtyReceived;
                    $d->save();
                }

                $header->receiver_id = $receiverId;
                $header->accept_note = $acceptNote;
                $header->status = 2;
                $header->acc_by = $accBy > 0 ? $accBy : $receiverId;
                $header->save();
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => -1,
                'message' => $e->getMessage() ?: 'Gagal ACC stock transfer',
            ]);
        }

        return response()->json(['status' => 1, 'message' => 'Stock transfer berhasil di-ACC']);
    }

    public function rejectStockTransfer(Request $req)
    {
        $stId = (int) ($req->id ?? $req->st_id ?? 0);
        $header = StockTransfer::query()->where('st_id', $stId)->where('status', 1)->first();
        if (! $header) {
            return response()->json(['status' => -1, 'message' => 'Hanya transfer pending yang bisa ditolak']);
        }

        try {
            DB::transaction(function () use ($header, $req) {
                $this->restoreSourceStock($header, 'tolak');
                $header->status = 3;
                $header->accept_note = $req->accept_note ?? $req->note ?? $header->accept_note;
                $header->acc_by = Session::get('user')->staff_id ?? null;
                $header->save();
            });
        } catch (Throwable $e) {
            return response()->json([
                'status' => -1,
                'message' => $e->getMessage() ?: 'Gagal tolak stock transfer',
            ]);
        }

        return response()->json(['status' => 1, 'message' => 'Stock transfer ditolak, stok dikembalikan']);
    }

    protected function restoreSourceStock(StockTransfer $header, string $reason): void
    {
        $details = StockTransferDetail::query()
            ->where('st_id', $header->st_id)
            ->where('status', 1)
            ->get();

        $code = $header->transfer_code;
        foreach ($details as $d) {
            $add = ProductUnitStock::addQty(
                (int) $header->from_warehouse_id,
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
    }

    /**
     * @return array{error:?string, transfer_date?:string, sender_id?:int, receiver_id?:int, from_warehouse_id?:int, to_warehouse_id?:int, note?:?string}
     */
    protected function parseHeaderPayload(Request $req): array
    {
        $senderId = (int) ($req->sender_id ?? 0);
        $receiverId = (int) ($req->receiver_id ?? 0);
        $fromId = (int) ($req->from_warehouse_id ?? 0);
        $toId = (int) ($req->to_warehouse_id ?? 0);
        $dateRaw = trim((string) ($req->transfer_date ?? ''));
        $note = $req->note ?? null;

        if ($senderId <= 0 || $receiverId <= 0 || $fromId <= 0 || $toId <= 0 || $dateRaw === '') {
            return ['error' => 'Lengkapi pengirim, penerima, gudang, dan tanggal'];
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
            'receiver_id' => $receiverId,
            'from_warehouse_id' => $fromId,
            'to_warehouse_id' => $toId,
            'note' => $note,
        ];
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

            $normalized[] = [
                'product_variant_id' => $variantId,
                'unit_id' => $unitId,
                'qty' => $qty,
                'label' => $label,
            ];
        }

        return $normalized;
    }

    /**
     * ACC hanya gudang tujuan: gudang aktif = tujuan (bukan asal).
     * Siapa pun staff yang login di gudang tujuan boleh ACC.
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
        array $assignedWh
    ): bool {
        if ($status !== 1 || $toWarehouseId <= 0 || $staffId <= 0 || $activeWarehouseId <= 0) {
            return false;
        }
        // Gudang aktif wajib = tujuan
        if ($activeWarehouseId !== $toWarehouseId) {
            return false;
        }
        // Tolak jika gudang aktif masih gudang asal
        if ($fromWarehouseId > 0 && $activeWarehouseId === $fromWarehouseId) {
            return false;
        }
        // Harus assigned ke gudang tujuan (kalau ada daftar assign)
        if ($assignedWh !== [] && ! in_array($toWarehouseId, $assignedWh, true)) {
            return false;
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

        if ((int) $header->status !== 1) {
            return 'Transfer sudah diproses';
        }
        if ($activeWh <= 0 || $activeWh !== $toWh) {
            return 'ACC hanya bisa dilakukan di gudang tujuan. Ganti gudang aktif ke gudang tujuan.';
        }
        if ($fromWh > 0 && $activeWh === $fromWh) {
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

    /** Edit/hapus hanya di gudang asal. @return true|string */
    protected function assertCanEditSource(StockTransfer $header)
    {
        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        $fromWh = (int) $header->from_warehouse_id;

        if ((int) $header->status !== 1) {
            return 'Transfer sudah diproses';
        }
        if ($activeWh <= 0 || $activeWh !== $fromWh) {
            return 'Edit/hapus hanya bisa dilakukan di gudang asal.';
        }

        return true;
    }
}
