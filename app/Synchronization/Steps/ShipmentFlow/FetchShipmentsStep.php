<?php

namespace App\Synchronization\Steps\ShipmentFlow;

use App\Synchronization\Contracts\PaginatedStepHandler;
use App\Synchronization\Pmo\PmoException;
use App\Synchronization\SyncStepResult;

/**
 * Langkah 1 — Ambil Data Pengiriman PMO.
 *
 * Beda dengan FetchPmoDataStep (alur Produk): /getShipments TIDAK punya mode
 * "ambil semua tanpa filter" — date_start/date_end wajib dikirim pada SETIAP
 * halaman (dikonfirmasi 2026-08-20, lihat
 * cdocs/integrations/202607260130-product-sync-flow.design.md §10). Rentang
 * tanggalnya dipilih operator lewat input di wizard (SyncStep::$queryFields
 * pada ShipmentSyncFlow), bukan dihitung otomatis — jadi TIDAK ada jalur
 * "aman dijalankan kapan saja tanpa berpikir" seperti langkah fetch Produk.
 *
 * Belum menulis satu pun data master, sama seperti FetchPmoDataStep — tujuan
 * langkah ini murni menarik & menyimpan potret untuk dipakai langkah
 * berikutnya (Step 2, SyncShipmentsStep).
 */
class FetchShipmentsStep extends ShipmentFlowStep implements PaginatedStepHandler
{
    /**
     * Jalur sekali-jalan TIDAK didukung untuk langkah ini: date_start/
     * date_end hanya diketahui lewat input wizard (fetchPage()), tidak ada
     * cara menyuntikkannya ke handle() lewat kontrak SyncStepHandler yang
     * tanpa parameter. Tetap diimplementasikan (wajib oleh interface) supaya
     * gagal jelas kalau ada pemanggil lain (test/tinker) yang mencobanya.
     */
    public function handle(): SyncStepResult
    {
        return $this->run(function (): void {
            throw new PmoException(
                'Langkah ini butuh date_start/date_end dari wizard (lihat SyncStep::$queryFields '
                .'pada ShipmentSyncFlow) — jalankan lewat tombol "Jalankan Sinkronisasi" di wizard '
                .'(fetchPage()+finalize()), bukan handle() satu-jalan.'
            );
        });
    }

    /**
     * @throws PmoException
     */
    public function fetchPage(int $page, array $query = []): array
    {
        return $this->snapshots->fetchPage(
            self::FLOW_KEY,
            self::ENDPOINT_KEY,
            $page,
            $this->normaliseDateRange($query)
        );
    }

    public function finalize(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $snapshot = $this->snapshots->finalizePages(self::FLOW_KEY, self::ENDPOINT_KEY);

            $result->withDetails($snapshot->details() + [
                'Jumlah Baris Pengiriman' => $snapshot->count(),
            ]);
            $result->processed = $snapshot->count();

            if ($snapshot->rows === []) {
                $result->succeed('PMO tidak mengirimkan satu pun data pengiriman pada rentang tanggal ini.');

                return;
            }

            // Belum ada pemeriksaan bentuk baris di sini (beda dengan
            // FetchPmoDataStep::evaluate() pada alur Produk) — field per
            // baris shipment belum pernah dikonfirmasi dari PMO. Ditambah
            // begitu SyncShipmentsStep (Step 2) didesain dari contoh
            // response yang nyata.
            $result->succeed(
                $snapshot->count().' baris pengiriman berhasil diambil dari PMO, siap diperiksa pada '
                .'langkah "Sinkronisasi Pengiriman".'
            );
        });
    }

    /**
     * date_start/date_end wajib diisi (dipilih operator di wizard, lihat
     * SyncStep::$queryFields) — PMO menolak /getShipments tanpa keduanya.
     *
     * @param  array<string, mixed>  $query
     * @return array<string, string>
     *
     * @throws PmoException
     */
    private function normaliseDateRange(array $query): array
    {
        $dateStart = trim((string) ($query['date_start'] ?? ''));
        $dateEnd = trim((string) ($query['date_end'] ?? ''));

        if ($dateStart === '' || $dateEnd === '') {
            throw new PmoException('Tanggal Mulai dan Tanggal Akhir wajib diisi sebelum mengambil data pengiriman dari PMO.');
        }

        if (strtotime($dateStart) === false || strtotime($dateEnd) === false) {
            throw new PmoException('Tanggal Mulai atau Tanggal Akhir tidak valid.');
        }

        if (strtotime($dateStart) > strtotime($dateEnd)) {
            throw new PmoException('Tanggal Mulai tidak boleh setelah Tanggal Akhir.');
        }

        return ['date_start' => $dateStart, 'date_end' => $dateEnd];
    }
}
