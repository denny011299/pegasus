<?php

namespace App\Synchronization\Steps\ShipmentFlow;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\Unit;
use App\Support\SalesOrderStock;
use App\Support\ShipmentApproval;
use App\Synchronization\SyncStepResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Langkah 2 — Sinkronisasi Pengiriman. REKONSILIASI DUA ARAH sejak Spec B (2026-09-23, lihat
 * cdocs/docs/specs/shipment-pmo-sync-flow.md) — beda dari versi sebelumnya yang murni report-only
 * terhadap sales_orders yang sudah ada:
 *   - ref_shipment_id BELUM ada di Pegasus -> baris sales_orders + sales_order_details BARU
 *     dibuat langsung (backfill gap, GitHub #190) — TANPA mutasi stok, TANPA alur approval 2
 *     tahap (itu channel push real-time yang tujuannya mencegat pemotongan stok SEBELUM
 *     terjadi; Sync murni mencerminkan kenyataan yang PMO SUDAH putuskan).
 *   - ref_shipment_id SUDAH ada DAN masih bisa ditulis ulang dari sumber eksternal
 *     (App\Support\ShipmentApproval::isEditableFromExternalSource() — status 1 Pending, belum
 *     ada approval/reject sama sekali; aturan yang SAMA dengan POST /shipments/shipped) -> baris
 *     yang sama ditimpa (header + detail + status), TANPA mutasi stok, + `pmo_synced_at` (SELALU
 *     ditulis, insert maupun update) dan `pmo_sync_note` (diisi kalau ADA sesuatu untuk
 *     dicatat — ringkasan perubahan pada update, dan/atau keterangan tidak ada bukti foto,
 *     lihat catatan `bukti_foto` di bawah).
 *   - ref_shipment_id SUDAH ada TAPI approval sudah mulai berjalan / status sudah maju/Ditolak ->
 *     DILEWATI (SyncStepResult::$skipped), TIDAK ditimpa — dicatat sebagai notice, bukan gagal.
 *
 * Contoh response GET /getShipments dikonfirmasi 2026-08-21 (percakapan langsung dengan pemilik
 * produk, BUKAN dari dokumen PMO):
 *
 *   { "ref_shipment_id": 930032026150631, "armada_id": 9818022026044148,
 *     "date": "2026-03-31", "bukti_foto": null, "status": "Sudah Dikirim",
 *     "items": [ { "variant_sku": "PGAZ1500ML", "qty": 2, "unit_id": 9506012026014611 }, ... ] }
 *
 * `status` DIKONFIRMASI ULANG 2026-09-23 (investigasi langsung ke database PMO, lihat
 * cdocs/docs/specs/shipment-pmo-sync-flow.md §3 dan issue dokumentasi
 * https://github.com/denny011299/PMO/issues/24): field ini SEBENARNYA mengirim nilai ENUM MENTAH
 * kolom `oms_delivery.status` milik PMO (`onschedule`/`onprocess`/`pending`/`success`/`canceled`),
 * BUKAN label UI seperti contoh "Sudah Dikirim" di atas (yang ternyata anomali/tidak mewakili
 * kontrak sebenarnya) — lihat STATUS_MAP di bawah untuk pemetaannya ke sales_orders.status.
 *
 * `armada_id` di-resolve lewat customers.ref_armada_id (Sinkronisasi Armada, SyncArmadaStep,
 * sudah dibangun sejak spec ini pertama ditulis) — dicocokkan sebagai STRING (id PMO 16 digit,
 * presisi float/JS int overflow, lihat GitHub #64), bukan lagi dilaporkan notice-only.
 *
 * `bukti_foto` DIKONFIRMASI 2026-09-23 (langsung dari pemilik produk): PMO memang TIDAK PERNAH
 * punya input bukti foto sama sekali di sisi mereka — bukan soal bentuknya belum diketahui,
 * field ini SECARA STRUKTURAL akan selalu kosong. Tetap disimpan APA ADANYA (raw, opsional) ke
 * sales_orders.pmo_bukti_foto kalau-kalau suatu saat terisi, tapi TIDAK ditunggu/tidak
 * memblokir apa pun — dan setiap kali kosong, satu baris catatan ditambahkan ("Tidak ada bukti
 * foto — pengiriman ini disinkronkan dari PMO.") supaya operator yang membuka dokumen ini tahu
 * KENAPA tidak ada bukti foto, bukan mengira datanya hilang/gagal tersimpan.
 *
 * `sales_delivery_orders`/`sales_delivery_orders_details` (dibuat migrasi 2025-12-03) TIDAK LAGI
 * ditulis langkah ini (DIPUTUSKAN 2026-09-23) — tabel itu milik modul "Sales Order Delivery" yang
 * sudah deprecated sejak 2026-08-04, tidak dibaca modul mana pun.
 */
class SyncShipmentsStep extends ShipmentFlowStep
{
    /**
     * Enum PMO (`oms_delivery.status`) -> sales_orders.status internal. Pemetaan LAMA yang sudah
     * ada (kebalikan App\ExternalApi\Support\ShipmentStatusMap::fromInternal() untuk status
     * legacy 4/5/6/7 — TIDAK diubah Spec A, yang hanya mengubah arti status 1 dan 3), bukan
     * pemetaan baru, cuma baru sekarang benar-benar dipakai di sini.
     *
     * @var array<string, int>
     */
    private const STATUS_MAP = [
        'onschedule' => 4, // Dijadwalkan (legacy, sama seperti hasil lama PUT /shipments/scheduled)
        'onprocess' => 2,  // Diterima/Confirmed — TANPA stok pernah dipotong di sini, lihat docblock kelas
        'pending' => 5,    // Belum Terkirim
        'success' => 6,    // Sudah Terkirim
        'canceled' => 7,   // Dibatalkan
    ];

    public function handle(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $snapshot = $this->shipments();
            $result->withDetails($snapshot->details());

            $now = Carbon::now();

            foreach ($snapshot->rows as $index => $row) {
                $result->processed++;
                $this->syncShipment($row, $index, $result, $now);
            }

            if ($result->processed === 0) {
                $result->succeed('Tidak ada data pengiriman yang diambil pada langkah sebelumnya.');

                return;
            }

            $result->finish('Sinkronisasi pengiriman selesai.');
        });
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function syncShipment(array $row, int $index, SyncStepResult $result, Carbon $now): void
    {
        $refShipmentId = $this->pickString($row, ['ref_shipment_id']);
        $label = 'Pengiriman '.($refShipmentId !== '' ? '#'.$refShipmentId : 'baris ke-'.($index + 1));

        if ($refShipmentId === '') {
            $result->failed++;
            $result->addError($label.': tidak ada ref_shipment_id dari PMO.');

            return;
        }

        $statusEnum = $this->pickString($row, ['status']);
        $internalStatus = self::STATUS_MAP[$statusEnum] ?? null;
        if ($internalStatus === null) {
            $result->failed++;
            $result->addError(
                $label.': status PMO "'.$statusEnum.'" tidak dikenal (yang dikenal: '
                .implode(', ', array_keys(self::STATUS_MAP)).') — kemungkinan kontrak PMO berubah, '
                .'lihat https://github.com/denny011299/PMO/issues/24. Baris dilewati, tidak ada yang ditulis.'
            );

            return;
        }

        $so = SalesOrder::where('ref_shipment_id', $refShipmentId)->first();
        $isInsert = $so === null;

        if (! $isInsert && ! ShipmentApproval::isEditableFromExternalSource($so)) {
            $result->skipped++;
            $result->addNotice(
                $label.': sudah tersentuh approval (atau statusnya sudah maju/Ditolak) di sisi IPM — '
                .'dilewati, TIDAK ditimpa dari Sync. Hanya shipment yang masih Pending dan belum ada '
                .'approval sama sekali yang boleh ditulis ulang dari PMO.'
            );

            return;
        }

        [$items, $itemFailed] = $this->resolveItems($row, $label, $result);

        if ($items === []) {
            $result->failed++;
            $result->addError($label.': tidak ada satu pun baris item yang berhasil dipetakan, dilewati.');

            return;
        }

        $armadaId = $this->pickString($row, ['armada_id']);
        $customer = $armadaId !== ''
            ? Customer::where('ref_armada_id', $armadaId)->first()
            : null;

        if ($customer === null) {
            if ($isInsert) {
                // Dokumen Pengiriman baru wajib punya armada yang jelas — beda dengan update
                // (baris di bawah), di mana armada lama tetap dipertahankan kalau yang baru tidak
                // ketemu.
                $result->failed++;
                $result->addError(
                    $label.': armada_id PMO "'.$armadaId.'" tidak ditemukan di customers.ref_armada_id '
                    .'— jalankan Sinkronisasi Armada lebih dulu, atau pastikan armada_id benar.'
                );

                return;
            }

            $result->addNotice(
                $label.': armada_id PMO "'.$armadaId.'" tidak ditemukan di customers.ref_armada_id — '
                .'armada pada dokumen yang sudah ada dipertahankan apa adanya, tidak diubah.'
            );
        }

        $date = $this->pickString($row, ['date']);
        $buktiFoto = $this->pickString($row, ['bukti_foto']);
        $warehouseId = SalesOrderStock::mainWarehouseId();

        DB::transaction(function () use (
            $so, $isInsert, $items, $customer, $date, $internalStatus, $buktiFoto,
            $refShipmentId, $label, $now, $warehouseId
        ) {
            if ($isInsert) {
                $so = (new SalesOrder())->insertSalesOrder([
                    'so_customer' => (string) $customer->customer_id,
                    'so_date' => $date !== '' ? $date : $now->toDateString(),
                    'so_total' => 0,
                    'so_img' => json_encode([]),
                ]);
                $so->ref_shipment_id = $refShipmentId;
            } else {
                if ($customer !== null) {
                    $so->so_customer = (string) $customer->customer_id;
                }
                if ($date !== '') {
                    $so->so_date = $date;
                }
            }

            $so->status = $internalStatus;
            $so->pmo_bukti_foto = $buktiFoto !== '' ? $buktiFoto : $so->pmo_bukti_foto;

            // bukti_foto SECARA STRUKTURAL selalu kosong dari PMO (lihat docblock kelas ini) —
            // catatan ini bukan tanda ada yang gagal, murni menjelaskan KENAPA tidak ada bukti
            // foto di dokumen ini, supaya operator tidak mengira datanya hilang.
            $noteParts = [];
            if ($buktiFoto === '') {
                $noteParts[] = 'Tidak ada bukti foto — pengiriman ini disinkronkan dari PMO.';
            }
            if (! $isInsert) {
                $noteParts[] = 'Diperbarui dari Sinkronisasi PMO pada '.$now->format('d/m/Y H:i')
                    .' — perubahan dari sisi PMO, tanpa mutasi status barang.';
            }
            if ($noteParts !== []) {
                $so->pmo_sync_note = implode("\n", $noteParts);
            }
            $so->pmo_synced_at = $now;
            $so->save();

            $this->replaceDetails($so, $items, $warehouseId);
        });

        $isInsert ? $result->inserted++ : $result->updated++;

        if ($itemFailed) {
            $result->addNotice($label.': sebagian baris item gagal dipetakan dan dilewati — lihat rincian di atas.');
        }
    }

    /**
     * Cocokkan tiap items[].variant_sku ke product_variants (D5 pada alur Produk: variant_sku
     * adalah kunci varian) dan items[].unit_id ke units.ref_unit_id — sekaligus resolve nama
     * produk (GET /getShipments TIDAK mengirim product_name/variant_name sama sekali, beda
     * dengan body POST /shipments/shipped). Baris item yang gagal dipetakan dilaporkan lalu
     * dilewati — sisanya tetap disinkronkan (D7).
     *
     * @param  array<string, mixed>  $row
     * @return array{0: array<int, array<string, mixed>>, 1: bool} baris siap-insert + ada-yang-gagal
     */
    private function resolveItems(array $row, string $label, SyncStepResult $result): array
    {
        $items = $this->pickList($row, ['items']);
        $skus = [];
        $refUnitIds = [];
        foreach ($items as $item) {
            $sku = $this->pickString($item, ['variant_sku']);
            if ($sku !== '') {
                $skus[] = $sku;
            }
            $refUnitIds[] = $this->pickInt($item, ['unit_id']);
        }

        $variantsBySku = $skus !== []
            ? ProductVariant::whereIn('product_variant_sku', $skus)->where('status', 1)
                ->get(['product_variant_id', 'product_id', 'product_variant_sku', 'product_variant_name'])
                ->keyBy(static fn (ProductVariant $v) => mb_strtoupper((string) $v->product_variant_sku))
            : collect();
        $unitsByRef = $refUnitIds !== []
            ? Unit::whereIn('ref_unit_id', array_filter($refUnitIds))->where('status', 1)
                ->get(['unit_id', 'ref_unit_id'])->keyBy('ref_unit_id')
            : collect();
        $productNames = $variantsBySku->isNotEmpty()
            ? Product::whereIn('product_id', $variantsBySku->pluck('product_id')->unique()->values())
                ->pluck('product_name', 'product_id')
            : collect();

        $resolved = [];
        $anyFailed = false;

        foreach ($items as $itemIndex => $item) {
            $itemLabel = $label.' baris item ke-'.($itemIndex + 1);
            $sku = $this->pickString($item, ['variant_sku']);
            $refUnitId = $this->pickInt($item, ['unit_id']);
            $qty = $this->pickInt($item, ['qty']);

            if ($sku === '') {
                $result->addError($itemLabel.': tidak ada variant_sku.');
                $anyFailed = true;

                continue;
            }

            $variant = $variantsBySku->get(mb_strtoupper($sku));
            if ($variant === null) {
                $result->addError($itemLabel.': SKU "'.$sku.'" tidak ditemukan sebagai varian produk aktif di Pegasus.');
                $anyFailed = true;

                continue;
            }

            $unit = $unitsByRef->get($refUnitId);
            if ($unit === null) {
                $result->addError(
                    $itemLabel.': unit_id PMO '.$refUnitId.' tidak ditemukan di Pegasus — jalankan '
                    .'Sinkronisasi Satuan pada alur Produk lebih dulu.'
                );
                $anyFailed = true;

                continue;
            }

            $resolved[] = [
                'product_variant_id' => (int) $variant->product_variant_id,
                'product_name' => $productNames->get($variant->product_id) ?? '-',
                'variant_name' => (string) ($variant->product_variant_name ?? ''),
                'variant_sku' => (string) $variant->product_variant_sku,
                'internal_unit_id' => (int) $unit->unit_id,
                'qty' => $qty,
            ];
        }

        return [$resolved, $anyFailed];
    }

    /**
     * Ganti seluruh sales_order_details milik $so dengan $items — baris lama dinonaktifkan
     * (status = 0), sama persis pola App\Http\Controllers\ExternalApi\V1\ShipmentController::
     * replaceDetails(). warehouse_id selalu gudang utama (Sync tidak tahu konteks gudang eceran).
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function replaceDetails(SalesOrder $so, array $items, int $warehouseId): void
    {
        $keptIds = [];

        foreach ($items as $item) {
            $keptIds[] = (new SalesOrderDetail())->insertSalesOrderDetail([
                'so_id' => $so->so_id,
                'product_variant_id' => $item['product_variant_id'],
                'product_name' => $item['product_name'],
                'product_variant_name' => $item['variant_name'],
                'product_variant_sku' => $item['variant_sku'],
                'unit_id' => $item['internal_unit_id'],
                'warehouse_id' => $warehouseId,
                'product_variant_price' => 0,
                'so_qty' => $item['qty'],
                'so_subtotal' => 0,
            ]);
        }

        SalesOrderDetail::where('so_id', $so->so_id)->whereNotIn('sod_id', $keptIds)->update(['status' => 0]);
    }
}
