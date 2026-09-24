<?php

namespace App\Synchronization\Steps\ShipmentFlow;

use App\Synchronization\SyncStepResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Langkah 2 — Sinkronisasi Pengiriman.
 *
 * Contoh response GET /getShipments dikonfirmasi 2026-08-21 (percakapan
 * langsung dengan pemilik produk, BUKAN dari dokumen PMO — belum ada
 * cdocs/integrations/*.design.md untuk alur ini):
 *
 *   { "ref_shipment_id": 930032026150631, "armada_id": 9818022026044148,
 *     "date": "2026-03-31", "bukti_foto": null, "status": "Sudah Dikirim",
 *     "items": [ { "variant_sku": "PGAZ1500ML", "qty": 2, "unit_id": 9506012026014611 }, ... ] }
 *
 * REKONSILIASI, bukan report-only (arah disepakati sebelum contoh payload
 * ada — lihat riwayat percakapan): tiap baris dicocokkan ke sales_orders
 * yang SUDAH ADA lewat ref_shipment_id (kolom itu diisi lewat arah push,
 * App\Http\Controllers\ExternalApi\V1\ShipmentController). Baris PMO yang
 * ref_shipment_id-nya tidak ditemukan di Pegasus dilaporkan gagal — TIDAK
 * PERNAH membuat sales_orders baru dari sini.
 *
 * `sales_delivery_orders`/`sales_delivery_orders_details` (dibuat migrasi
 * 2025-12-03) belum pernah dipakai kode lain sama sekali sebelum langkah
 * ini — tidak ada model, tidak ada controller, 0 baris di snapshot dev.
 * Jadi tidak ada konvensi insert yang sudah ada untuk diikuti; pola di bawah
 * (DB::table() langsung, generateSdoNumber() lokal) sengaja meniru gaya
 * SyncUnitStep/SyncProductStep pada alur Produk, bukan menyalin dari
 * modul lain.
 *
 * TIGA hal SENGAJA belum ditulis ke Pegasus, dilaporkan lewat notices saja:
 * - `armada_id` (16 digit, id PMO asli) — "Armada" belum punya kolom
 *   referensi di Pegasus (customers TIDAK punya ref_armada_id). Alur
 *   GET /getArmada (§10 design doc Produk) yang akan menyediakan pemetaan
 *   itu belum dibangun. PENTING: ini BUKAN customers.customer_id yang
 *   dipakai istilah "armada_id" pada CashPaymentController — namespace id
 *   yang berbeda sama sekali (lihat komentar kelas itu).
 * - `bukti_foto` — seluruh contoh yang dikonfirmasi bernilai null, jadi
 *   bentuknya (URL? nama berkas kompatibel dengan sales_orders.so_img via
 *   ShipmentPhotoStore?) belum pernah terlihat pada data nyata.
 * - `status` ("Sudah Dikirim" pada contoh) — TIDAK cocok persis dengan
 *   label yang dikenal App\ExternalApi\Support\ShipmentStatusMap
 *   ("Sudah terkirim", beda kata "dikirim" vs "terkirim"). Daripada
 *   menebak keduanya sama, sales_orders.status TIDAK PERNAH ditulis oleh
 *   langkah ini — nilainya hanya dilaporkan apa adanya.
 *
 * Kalau salah satu dari ketiganya perlu benar-benar disinkronkan, itu
 * keputusan produk baru (kolom baru + migrasi untuk armada_id/bukti_foto,
 * atau konfirmasi kosakata status) — bukan sesuatu yang bisa diselesaikan
 * dengan menebak dari kode yang ada.
 */
class SyncShipmentsStep extends ShipmentFlowStep
{
    private const SDO_NUMBER_PREFIX = 'SDO';

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

        $salesOrder = DB::table('sales_orders')->where('ref_shipment_id', $refShipmentId)->first();

        if (! $salesOrder) {
            $result->failed++;
            $result->addError(
                $label.': tidak ada sales_orders dengan ref_shipment_id ini di Pegasus — kemungkinan '
                .'belum dibuat lewat Platform API Eksternal (POST /shipments/scheduled atau /shipped), '
                .'atau sudah dihapus. Tidak dibuatkan sales_orders baru dari sini.'
            );

            return;
        }

        $this->noteUnwrittenFields($row, $label, $result);

        [$items, $itemFailed] = $this->resolveItems($row, $label, $result);

        if ($items === []) {
            $result->failed++;
            $result->addError($label.': tidak ada satu pun baris item yang berhasil dipetakan, dilewati.');

            return;
        }

        $sdoId = $this->upsertDeliveryOrder($row, $salesOrder, $label, $now, $result);

        foreach ($items as &$item) {
            $item['sdo_id'] = $sdoId;
        }
        unset($item);

        DB::table('sales_delivery_orders_details')->insert($items);

        if ($itemFailed) {
            $result->addNotice($label.': sebagian baris item gagal dipetakan dan dilewati — lihat rincian di atas.');
        }
    }

    /**
     * armada_id/bukti_foto/status TIDAK ditulis ke mana pun — lihat
     * penjelasan lengkap pada docblock kelas ini. Hanya dicatat sebagai
     * notice supaya operator tahu PMO mengirimkannya.
     *
     * @param  array<string, mixed>  $row
     */
    private function noteUnwrittenFields(array $row, string $label, SyncStepResult $result): void
    {
        $armadaId = $this->pickString($row, ['armada_id']);
        if ($armadaId !== '') {
            $result->addNotice(
                $label.': armada_id PMO "'.$armadaId.'" belum disinkronkan — menunggu alur '
                .'Sinkronisasi Armada (GET /getArmada, belum dibangun).'
            );
        }

        $buktiFoto = $this->pickString($row, ['bukti_foto']);
        if ($buktiFoto !== '') {
            $result->addNotice(
                $label.': bukti_foto dikirim PMO ("'.$buktiFoto.'") tapi belum disimpan — bentuknya '
                .'belum pernah dikonfirmasi dari data nyata.'
            );
        }

        $status = $this->pickString($row, ['status']);
        if ($status !== '') {
            $result->addNotice(
                $label.': status PMO "'.$status.'" tidak cocok dengan kosakata ShipmentStatusMap yang '
                .'dikenal — sales_orders.status TIDAK diubah.'
            );
        }
    }

    /**
     * Cocokkan tiap items[].variant_sku ke product_variants (D5 pada alur
     * Produk: variant_sku adalah kunci varian) dan items[].unit_id ke
     * units.ref_unit_id. Baris item yang gagal dipetakan dilaporkan lalu
     * dilewati — sisanya tetap disinkronkan (D7).
     *
     * @param  array<string, mixed>  $row
     * @return array{0: array<int, array<string, mixed>>, 1: bool} baris siap-insert (belum ada sdo_id) + ada-yang-gagal
     */
    private function resolveItems(array $row, string $label, SyncStepResult $result): array
    {
        $resolved = [];
        $anyFailed = false;

        foreach ($this->pickList($row, ['items']) as $itemIndex => $item) {
            $itemLabel = $label.' baris item ke-'.($itemIndex + 1);
            $sku = $this->pickString($item, ['variant_sku']);
            $refUnitId = $this->pickInt($item, ['unit_id']);
            $qty = $this->pickInt($item, ['qty']);

            if ($sku === '') {
                $result->addError($itemLabel.': tidak ada variant_sku.');
                $anyFailed = true;

                continue;
            }

            $variants = DB::table('product_variants')
                ->where('product_variant_sku', $sku)
                ->where('status', 1)
                ->get();

            if ($variants->count() > 1) {
                $result->addError(
                    $itemLabel.': SKU "'.$sku.'" ada pada '.$variants->count().' varian produk aktif '
                    .'di Pegasus — ambigu, tidak bisa dipetakan.'
                );
                $anyFailed = true;

                continue;
            }

            if ($variants->isEmpty()) {
                $result->addError($itemLabel.': SKU "'.$sku.'" tidak ditemukan di Pegasus.');
                $anyFailed = true;

                continue;
            }

            $unit = DB::table('units')->where('ref_unit_id', $refUnitId)->where('status', 1)->first();

            if (! $unit) {
                $result->addError(
                    $itemLabel.': unit_id PMO '.$refUnitId.' tidak ditemukan di Pegasus — jalankan '
                    .'Sinkronisasi Satuan pada alur Produk lebih dulu.'
                );
                $anyFailed = true;

                continue;
            }

            $resolved[] = [
                'product_variant_id' => $variants->first()->product_variant_id,
                'sdod_sku' => mb_substr($sku, 0, 50),
                'sdod_qty' => $qty,
                'unit_id' => $unit->unit_id,
                'status' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        return [$resolved, $anyFailed];
    }

    /**
     * Satu shipment PMO <-> satu sales_delivery_orders (dicocokkan lewat
     * so_id, bukan bikin baris baru tiap kali disinkronkan ulang). Baris
     * detail LAMA dihapus lalu diganti dengan yang baru dari PMO —
     * shipment ini adalah sumber kebenaran, bukan diakumulasi (sama seperti
     * update pada langkah-langkah alur Produk).
     *
     * @param  array<string, mixed>  $row
     */
    private function upsertDeliveryOrder(array $row, object $salesOrder, string $label, Carbon $now, SyncStepResult $result): int
    {
        $date = $this->pickString($row, ['date']);
        $existing = DB::table('sales_delivery_orders')->where('so_id', $salesOrder->so_id)->first();

        if ($existing) {
            DB::table('sales_delivery_orders')->where('sdo_id', $existing->sdo_id)->update([
                'sdo_date' => $date !== '' ? $date : $existing->sdo_date,
                'updated_at' => $now,
            ]);
            DB::table('sales_delivery_orders_details')->where('sdo_id', $existing->sdo_id)->delete();

            $result->updated++;

            return (int) $existing->sdo_id;
        }

        // sdo_receiver/sdo_phone wajib diisi (NOT NULL) tapi PMO tidak
        // mengirim nama/telepon penerima sama sekali — sdo_receiver
        // memakai nama pelanggan dari sales_orders yang cocok, sdo_phone
        // dibiarkan kosong sampai ada sumber datanya.
        $sdoId = (int) DB::table('sales_delivery_orders')->insertGetId([
            'so_id' => $salesOrder->so_id,
            'sdo_number' => $this->generateSdoNumber(),
            'sdo_receiver' => mb_substr((string) $salesOrder->so_customer, 0, 150),
            'sdo_date' => $date !== '' ? $date : $now->toDateString(),
            'sdo_phone' => '',
            'sdo_desc' => 'Dibuat otomatis oleh Sinkronisasi Pengiriman PMO ('.$label.').',
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], 'sdo_id');

        $result->inserted++;

        return $sdoId;
    }

    /**
     * "SDO" + 6 digit (mis. SDO000001) — muat dalam sdo_number varchar(10).
     * Pola sama seperti generateXID() lain di codebase (prefix + max+1),
     * dengan risiko race condition yang sama (tidak ada locking) — belum
     * ada konvensi lain untuk diikuti karena tabel ini belum pernah dipakai.
     */
    private function generateSdoNumber(): string
    {
        $maxSuffix = DB::table('sales_delivery_orders')
            ->where('sdo_number', 'like', self::SDO_NUMBER_PREFIX.'%')
            ->pluck('sdo_number')
            ->map(fn ($number) => (int) substr((string) $number, strlen(self::SDO_NUMBER_PREFIX)))
            ->max();

        return self::SDO_NUMBER_PREFIX.str_pad((string) (($maxSuffix ?? 0) + 1), 6, '0', STR_PAD_LEFT);
    }
}
