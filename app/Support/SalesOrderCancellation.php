<?php

namespace App\Support;

use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Batalkan satu Sales Order (Pengiriman): kembalikan stok KALAU sebelumnya sudah Confirmed
 * (stoknya sudah dipotong lewat SalesOrderApproval::confirm()), lalu set status Dibatalkan (7).
 *
 * Tidak ada alur admin yang setara untuk dicontek/diekstrak (BEDA dengan SalesOrderApproval yang
 * diekstrak dari accSO() yang sudah ada) - CustomerController::declineSO()/deleteSalesOrder()
 * hanya berlaku SEBELUM Confirmed (status 1, stok belum dipotong, jadi tidak pernah perlu
 * mengembalikan stok). Dipakai App\Http\Controllers\ExternalApi\V1\ShipmentController::cancel(),
 * ditulis di sini (bukan langsung di controller) supaya kalau nanti admin butuh tombol "Batalkan"
 * untuk Pengiriman yang sudah Confirmed, tinggal panggil fungsi yang sama.
 *
 * IDEMPOTEN lewat pengecekan status di awal: baris yang statusnya SUDAH 7 dijawab sukses apa
 * adanya TANPA mengembalikan stok lagi - penting, karena mengembalikan stok dua kali untuk baris
 * yang sama akan menggelembungkan stok gudang.
 *
 * Seluruh kegagalan selalu dikembalikan sebagai array terstruktur, tidak pernah melempar
 * exception ke pemanggil - sama seperti SalesOrderApproval::confirm().
 */
class SalesOrderCancellation
{
    /** sales_orders.status, lihat migrasi 2026_08_12_120000_*. */
    public const STATUS_CANCELLED = 7;

    /**
     * Status internal yang berarti "stok sudah dipotong dan perlu dikembalikan" kalau dibatalkan.
     * Cuma 2 (Confirmed) - status lain (1/4/5/6) tidak pernah lewat SalesOrderApproval::confirm(),
     * jadi stoknya tidak pernah dipotong untuk baris itu.
     */
    private const STOCK_DEDUCTED_STATUSES = [2];

    /**
     * @return array{ok: bool, message?: string, already_cancelled?: bool, stock_restored?: bool}
     */
    public static function cancel(SalesOrder $so, ?string $reason): array
    {
        if ((int) $so->status === self::STATUS_CANCELLED) {
            return ['ok' => true, 'already_cancelled' => true, 'stock_restored' => false];
        }

        $shouldRestoreStock = in_array((int) $so->status, self::STOCK_DEDUCTED_STATUSES, true);

        $lines = $shouldRestoreStock
            ? SalesOrderDetail::where('so_id', $so->so_id)->where('status', 1)->get()
                ->map(static fn ($row) => [
                    'product_variant_id' => $row->product_variant_id,
                    'unit_id' => $row->unit_id,
                    'warehouse_id' => $row->warehouse_id ?? null,
                    'qty' => (float) $row->sod_qty,
                ])->all()
            : [];

        try {
            DB::transaction(function () use ($so, $lines, $shouldRestoreStock, $reason) {
                if ($shouldRestoreStock && $lines !== []) {
                    $retailWh = (int) ($so->retail_warehouse_id ?? 0);
                    $restore = SalesOrderStock::executeRestore(
                        $lines,
                        $retailWh > 0 ? $retailWh : null,
                        $so->so_invoice_no ?: $so->so_number,
                        'Pembatalan Pengiriman'
                    );
                    if (! ($restore['ok'] ?? false)) {
                        throw new \RuntimeException($restore['message'] ?? 'Gagal kembalikan stok');
                    }
                }

                $so->status = self::STATUS_CANCELLED;
                if (Schema::hasColumn($so->getTable(), 'cancel_reason')) {
                    $so->cancel_reason = $reason;
                }
                $so->save();
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }

        return ['ok' => true, 'already_cancelled' => false, 'stock_restored' => $shouldRestoreStock];
    }
}
