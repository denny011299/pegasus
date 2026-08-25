<?php

namespace App\Synchronization\Steps\ArmadaFlow;

use App\Synchronization\Contracts\SyncStepHandler;
use App\Synchronization\Pmo\PmoException;
use App\Synchronization\Pmo\PmoSnapshot;
use App\Synchronization\Pmo\PmoSnapshotStore;
use App\Synchronization\Support\RowReader;
use App\Synchronization\SyncStepResult;

/**
 * Dasar bersama seluruh langkah alur Sinkronisasi Armada.
 *
 * Beda dengan ShipmentFlowStep: /getArmada TIDAK mewajibkan parameter query
 * apa pun selain "page" (dikonfirmasi 2026-08-20) — sama seperti
 * /getProducts, jadi armada() aman auto-refresh lewat PmoSnapshotStore::get()
 * kalau potretnya belum ada.
 */
abstract class ArmadaFlowStep implements SyncStepHandler
{
    use RowReader;

    protected const FLOW_KEY = 'armada';

    protected const ENDPOINT_KEY = 'armada';

    public function __construct(protected readonly PmoSnapshotStore $snapshots)
    {
    }

    /**
     * @throws PmoException
     */
    protected function armada(): PmoSnapshot
    {
        return $this->snapshots->get(self::FLOW_KEY, self::ENDPOINT_KEY);
    }

    /**
     * Bungkus eksekusi: kegagalan tingkat payload menggagalkan seluruh
     * langkah. Sama persis dengan ProductFlowStep::run() — lihat itu untuk
     * alasannya (D7 pada design doc Produk).
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
     * Id armada PMO sebagai penanda baris pada pesan kesalahan/catatan.
     *
     * @param  array<string, mixed>  $row
     */
    protected function armadaLabel(array $row): string
    {
        $ref = $this->pickInt($row, ['armada_id']);
        $pic = $this->pickString($row, ['pic_name']);

        return 'Armada '.($ref !== 0 ? '#'.$ref : '(tanpa armada_id)')
            .($pic !== '' ? ' "'.$pic.'"' : '');
    }
}
