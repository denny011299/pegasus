<?php

namespace App\Synchronization\Steps\ArmadaFlow;

use App\Synchronization\Contracts\PaginatedStepHandler;
use App\Synchronization\Pmo\PmoSnapshot;
use App\Synchronization\SyncStepResult;

/**
 * Langkah 0 — Ambil & Periksa Data Armada PMO.
 *
 * Selalu menarik ulang /getArmada dan menyimpannya sebagai potret yang
 * dipakai bersama langkah "Sinkronisasi Armada". Tidak menulis satu pun
 * data master — sama filosofinya dengan FetchPmoDataStep pada alur Produk:
 * pastikan PMO terjangkau dan datanya layak sebelum ada yang ditulis.
 *
 * Mengikuti flowchart "API Armada": "Apakah Datanya Valid?" diterjemahkan
 * sebagai baris wajib punya armada_id dan pic_name — baris yang tidak lolos
 * dilaporkan gagal di sini, sebelum langkah pencocokan berikutnya membaca
 * apa pun.
 *
 * /getArmada tidak mewajibkan parameter query (beda dengan /getShipments),
 * jadi seperti FetchPmoDataStep, handle() TETAP jalur sekali-jalan yang
 * berfungsi penuh — bukan cuma fetchPage()+finalize() lewat wizard.
 */
class FetchArmadaStep extends ArmadaFlowStep implements PaginatedStepHandler
{
    public function handle(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $this->evaluate($this->snapshots->refresh(self::FLOW_KEY, self::ENDPOINT_KEY), $result);
        });
    }

    public function fetchPage(int $page, array $query = []): array
    {
        return $this->snapshots->fetchPage(self::FLOW_KEY, self::ENDPOINT_KEY, $page);
    }

    public function finalize(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $this->evaluate($this->snapshots->finalizePages(self::FLOW_KEY, self::ENDPOINT_KEY), $result);
        });
    }

    private function evaluate(PmoSnapshot $snapshot, SyncStepResult $result): void
    {
        if ($snapshot->rows === []) {
            $result->withDetails($snapshot->details());
            $result->succeed('PMO tidak mengirimkan satu pun data armada.');

            return;
        }

        foreach ($snapshot->rows as $index => $row) {
            $result->processed++;
            $label = $this->armadaLabel($row);

            if ($this->pickInt($row, ['armada_id']) === 0) {
                $result->failed++;
                $result->addError('Baris ke-'.($index + 1).': tidak ada armada_id.');
            }

            if ($this->pickString($row, ['pic_name']) === '') {
                $result->failed++;
                $result->addError($label.': pic_name kosong.');
            }
        }

        $result->withDetails($snapshot->details() + [
            'Jumlah Armada' => count($snapshot->rows),
        ]);

        if ($result->failed > 0) {
            $result->fail(
                $result->failed.' baris armada PMO tidak memenuhi syarat minimum. '
                .'Perbaiki di sisi PMO sebelum menjalankan langkah berikutnya.'
            );

            return;
        }

        $result->succeed(
            'Data armada PMO berhasil diambil dan diperiksa. '
            .count($snapshot->rows).' armada siap disinkronkan.'
        );
    }
}
