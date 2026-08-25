<?php

namespace App\Synchronization\Steps\ShipmentFlow;

use App\Synchronization\Contracts\SyncStepHandler;
use App\Synchronization\Pmo\PmoException;
use App\Synchronization\Pmo\PmoSnapshot;
use App\Synchronization\Pmo\PmoSnapshotStore;
use App\Synchronization\Support\RowReader;
use App\Synchronization\SyncStepResult;

/**
 * Dasar bersama seluruh langkah alur Sinkronisasi Pengiriman.
 *
 * Berbeda dari ProductFlowStep: /getShipments mewajibkan date_start/date_end
 * (lihat FetchShipmentsStep) dan belum ada contoh response yang dikonfirmasi
 * dari PMO — cdocs/integrations/202607260130-product-sync-flow.design.md §10
 * baru mengonfirmasi kontrak query-nya, bukan bentuk baris per shipment.
 */
abstract class ShipmentFlowStep implements SyncStepHandler
{
    use RowReader;

    protected const FLOW_KEY = 'shipment';

    protected const ENDPOINT_KEY = 'shipments';

    public function __construct(protected readonly PmoSnapshotStore $snapshots)
    {
    }

    /**
     * Potret /getShipments yang SUDAH diambil lewat langkah "Ambil Data
     * Pengiriman PMO" — TIDAK PERNAH memanggil PMO sendiri (lihat
     * PmoSnapshotStore::getExisting()), karena date_start/date_end hanya
     * diketahui oleh langkah itu, dipilih operator di wizard.
     *
     * @throws PmoException
     */
    protected function shipments(): PmoSnapshot
    {
        return $this->snapshots->getExisting(self::FLOW_KEY, self::ENDPOINT_KEY);
    }

    /**
     * Bungkus eksekusi: kegagalan tingkat payload (PMO tak terjangkau, respons
     * rusak, potret belum ada) menggagalkan seluruh langkah. Sama persis
     * dengan ProductFlowStep::run() — lihat itu untuk alasannya (D7 pada
     * design doc Produk).
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
}
