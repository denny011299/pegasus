<?php

namespace App\Synchronization\Steps\ProductFlow;

use App\Synchronization\Contracts\SyncStepHandler;
use App\Synchronization\Pmo\PmoException;
use App\Synchronization\Pmo\PmoSnapshot;
use App\Synchronization\Pmo\PmoSnapshotStore;
use App\Synchronization\Support\RowReader;
use App\Synchronization\SyncStepResult;

/**
 * Dasar bersama seluruh langkah alur Sinkronisasi Produk.
 *
 * Menyediakan akses ke potret data PMO dan pembacaan baris yang toleran.
 * Logika pemetaan tiap entitas ada di kelas turunannya.
 */
abstract class ProductFlowStep implements SyncStepHandler
{
    use RowReader;

    protected const FLOW_KEY = 'product';

    public function __construct(protected readonly PmoSnapshotStore $snapshots)
    {
    }

    /**
     * Potret /getProducts — diambil dari PMO hanya bila belum ada.
     *
     * @throws PmoException
     */
    protected function products(): PmoSnapshot
    {
        return $this->snapshots->get(self::FLOW_KEY, 'products');
    }

    /**
     * Daftar satuan unik, diturunkan dari items[].units[] tiap produk pada
     * potret /getProducts. PMO tidak menyediakan endpoint satuan terpisah
     * (dikonfirmasi 2026-08-20) — jadi ini bukan pemanggilan PMO lagi,
     * hanya agregasi atas potret yang sudah ada.
     *
     * Konsekuensinya: unit_short_name dan status aktif per satuan tidak
     * pernah dikirim PMO. SyncUnitStep menjaga unit_short_name yang sudah
     * ada di Pegasus saat pembaruan, dan status baru selalu dianggap aktif.
     *
     * @throws PmoException
     */
    protected function units(): PmoSnapshot
    {
        $products = $this->products();

        $rows = [];
        foreach ($products->rows as $product) {
            foreach ($this->pickList($product, ['units', 'unit']) as $unit) {
                $unitId = $this->pickInt($unit, ['unit_id', 'id']);
                if ($unitId === 0) {
                    continue;
                }

                $rows[$unitId] ??= [
                    'unit_id' => $unitId,
                    'unit_name' => $this->pickString($unit, ['unit_name', 'name']),
                ];
            }
        }

        return new PmoSnapshot(
            rows: array_values($rows),
            meta: $products->meta,
            fetchedAt: $products->fetchedAt,
            url: $products->url,
            justFetched: $products->justFetched,
        );
    }

    /**
     * Bungkus eksekusi: kegagalan tingkat payload (PMO tak terjangkau, respons
     * rusak) menggagalkan seluruh langkah, sesuai keputusan D7. Kegagalan per
     * baris ditangani di dalam callback dan hanya dihitung.
     */
    protected function run(callable $work): SyncStepResult
    {
        $result = SyncStepResult::start();

        try {
            $work($result);
        } catch (PmoException $e) {
            return $result->addError($e->getMessage())->fail($e->getMessage());
        }

        return $result;
    }

    /**
     * Id produk PMO sebagai penanda baris pada pesan kesalahan.
     *
     * @param  array<string, mixed>  $product
     */
    protected function productLabel(array $product): string
    {
        $ref = $this->pickInt($product, ['ref_product_id', 'product_id', 'id']);
        $name = $this->pickString($product, ['product_name', 'name']);

        return 'Produk '.($ref !== 0 ? '#'.$ref : '(tanpa ref_product_id)')
            .($name !== '' ? ' "'.$name.'"' : '');
    }
}
