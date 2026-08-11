<?php

namespace App\Support;

use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Confirm/ACC satu Sales Order (Pengiriman): potong stok + set status Confirmed (2).
 *
 * Diekstrak dari CustomerController::accSO() supaya bisa dipakai ulang tanpa lewat HTTP layer -
 * dipakai juga oleh App\Http\Controllers\ExternalApi\V1\ShipmentController::shipped(). Isinya
 * PERSIS logika yang sama (buildPlan -> executeDeduct -> set status 2), bukan tulis ulang.
 * CustomerController::accSO() sendiri sekarang memanggil confirm() ini juga, jadi hanya ada SATU
 * tempat yang perlu diubah kalau alur konfirmasi berubah.
 *
 * Beda dengan accSO() controller aslinya:
 * - Precondition status "boleh dikonfirmasi" diperluas: 1 (Created, dibuat manual lewat halaman
 *   admin) ATAU 4 (Dijadwalkan, dibuat lewat External API POST /shipments/scheduled atau
 *   /shipments/shipped saat ref_shipment_id belum ada). accSO() controller sendiri tetap hanya
 *   memanggil confirm() setelah memastikan status === 1 di lapisannya sendiri (supaya pesan
 *   "sudah diterima/ditolak oleh {staff}" yang menyebut nama tetap ada di sana) - jadi guard 1/4
 *   di sini murni untuk pemanggil lain (External API) yang tidak melakukan pengecekan itu sendiri.
 * - $staffId eksplisit sebagai parameter, bukan membaca Session::get('user') sendiri - supaya
 *   jelas nilainya dari mana (null utuh untuk panggilan dari External API, sama seperti
 *   created_by pada insertSalesOrder/insertProduct dst.).
 * - Seluruh kegagalan (status tidak valid, stok tidak cukup, potong stok gagal) selalu
 *   dikembalikan sebagai array terstruktur, tidak pernah melempar exception ke pemanggil -
 *   supaya baik controller admin maupun External API bisa memakai bentuk yang sama tanpa
 *   try/catch masing-masing.
 */
class SalesOrderApproval
{
    /** Status internal yang boleh dikonfirmasi: 1 = Created (admin), 4 = Dijadwalkan (API). */
    private const CONFIRMABLE_STATUSES = [1, 4];

    /**
     * @return array{
     *     ok: bool, status?: int, header?: string, message?: string,
     *     products?: array<int, string>, recommendations?: array<int, array>,
     * }
     */
    public static function confirm(SalesOrder $so, ?int $staffId): array
    {
        if (! in_array((int) $so->status, self::CONFIRMABLE_STATUSES, true)) {
            return [
                'ok' => false,
                'status' => -2,
                'header' => 'Gagal ACC',
                'message' => 'Pengajuan sudah diterima/ditolak sebelumnya.',
            ];
        }

        $lines = SalesOrderDetail::where('so_id', $so->so_id)->where('status', 1)->get()
            ->map(static fn ($row) => [
                'product_variant_id' => $row->product_variant_id,
                'unit_id' => $row->unit_id,
                'warehouse_id' => $row->warehouse_id ?? null,
                'qty' => (float) $row->sod_qty,
            ])->all();

        $retailWh = (int) ($so->retail_warehouse_id ?? 0);
        $plan = SalesOrderStock::buildPlan($lines, $retailWh > 0 ? $retailWh : null);
        if (! ($plan['ok'] ?? false)) {
            return $plan;
        }

        try {
            DB::transaction(function () use ($plan, $so, $staffId) {
                $deduct = SalesOrderStock::executeDeduct(
                    $plan['plan'],
                    $so->so_invoice_no ?: $so->so_number,
                    'Pengiriman produk'
                );
                if (! ($deduct['ok'] ?? false)) {
                    throw new \RuntimeException($deduct['message'] ?? 'Gagal potong stok');
                }

                $so->status = 2;
                if (Schema::hasColumn($so->getTable(), 'acc_by')) {
                    $so->acc_by = $staffId;
                }
                $so->save();
            });
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => 0,
                'header' => 'Gagal ACC',
                'message' => $e->getMessage(),
            ];
        }

        return ['ok' => true];
    }
}
