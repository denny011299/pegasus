<?php

namespace App\Synchronization\Steps\ArmadaFlow;

use App\Models\ArmadaMatchReview;
use App\Models\Customer;
use App\Synchronization\Support\ReferenceMatcher;
use App\Synchronization\SyncStepResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Langkah 1 — Sinkronisasi Armada (tabel `customers`, "Armada" tersimpan di
 * sana — lihat App\Http\Controllers\ExternalApi\V1\MasterArmadaController).
 *
 * Mengikuti flowchart "API Armada", dengan SATU perluasan yang disepakati
 * (percakapan 2026-08-22): flowchart-nya cuma cek "No Pol + PIC sudah ada?",
 * tapi PMO ternyata mengirim armada_id yang stabil — jadi sama seperti
 * SyncProductStep/SyncUnitStep pada alur Produk, langkah ini dua fase:
 *
 * Fase 1 (mapan) — customers.ref_armada_id cocok → PERBARUI baris itu
 * langsung, tidak ada logika No Pol/PIC yang disentuh sama sekali.
 *
 * Fase 2 (belum tersambung) — ref_armada_id belum dikenal → ikuti
 * flowchart: cocokkan lewat No Pol + PIC (dinormalisasi & digabung sebagai
 * satu kunci, ReferenceMatcher::normalise()) terhadap customers yang BELUM
 * punya ref_armada_id.
 *   - Cocok TEPAT SATU → diadopsi (ref_armada_id ditulis ke baris itu).
 *   - TIDAK cocok sama sekali → sesuai flowchart, insert baris baru.
 *   - Cocok LEBIH DARI SATU → BEDA dari alur Produk (yang langsung
 *     melaporkan gagal): baris ini justru DIANTREKAN ke
 *     armada_match_reviews untuk dikonfirmasi manual lewat langkah
 *     "Konfirmasi Armada Ambigu" (App\Synchronization\Steps\ArmadaFlow\ResolveArmadaConflictsStep)
 *     — operator yang memilih menyambungkan ke salah satu, mengabaikan,
 *     atau membiarkannya menggantung. Dihitung sebagai "dilewati", bukan
 *     gagal.
 *
 * Sekali disambungkan (baik otomatis lewat adopsi maupun manual lewat
 * konfirmasi), setiap sinkronisasi berikutnya MENIMPA customer_pic/
 * customer_notes/customer_pic_phone/customer_saldo dari data PMO — sama
 * seperti Produk/Satuan, PMO adalah sumber kebenaran begitu tersambung.
 */
class SyncArmadaStep extends ArmadaFlowStep
{
    public function handle(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $snapshot = $this->armada();
            $result->withDetails($snapshot->details());

            $byRef = $this->loadLinkedCustomers();
            $adoptableByKey = $this->loadAdoptableCustomers();
            $reviewsByRef = ArmadaMatchReview::whereIn(
                'ref_armada_id',
                array_column($snapshot->rows, 'armada_id')
            )->get()->keyBy('ref_armada_id');
            $now = Carbon::now();

            foreach ($snapshot->rows as $row) {
                $result->processed++;
                $this->syncArmada($row, $result, $byRef, $adoptableByKey, $reviewsByRef, $now);
            }

            if ($result->processed === 0) {
                $result->succeed('Tidak ada data armada yang diambil pada langkah sebelumnya.');

                return;
            }

            $result->finish('Sinkronisasi armada selesai.');
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int|string, int>  $byRef  ref_armada_id => customer_id, dimutasi di tempat.
     * @param  array<string, array<int, int>>  $adoptableByKey  dimutasi di tempat.
     * @param  \Illuminate\Support\Collection<int|string, ArmadaMatchReview>  $reviewsByRef
     */
    private function syncArmada(
        array $row,
        SyncStepResult $result,
        array &$byRef,
        array &$adoptableByKey,
        $reviewsByRef,
        Carbon $now
    ): void {
        $refArmadaId = $this->pickInt($row, ['armada_id']);
        $picName = $this->pickString($row, ['pic_name']);
        $label = $this->armadaLabel($row);

        if ($refArmadaId === 0 || $picName === '') {
            $result->failed++;
            $result->addError($label.': armada_id atau pic_name kosong.');

            return;
        }

        $attributes = [
            'ref_armada_id' => $refArmadaId,
            'customer_pic' => mb_substr($picName, 0, 255),
            'customer_notes' => $this->pickString($row, ['nomer_pol']) ?: null,
            'customer_pic_phone' => $this->pickString($row, ['nomer_telp']) ?: null,
            'customer_saldo' => $this->pickInt($row, ['saldo_armada']),
            'updated_at' => $now,
        ];

        // Fase 1 — sudah tersambung: perbarui langsung, tidak ada logika
        // No Pol/PIC yang disentuh sama sekali.
        if (isset($byRef[$refArmadaId])) {
            DB::table('customers')->where('customer_id', $byRef[$refArmadaId])->update($attributes);
            $result->updated++;

            return;
        }

        $review = $reviewsByRef->get($refArmadaId);

        if ($review && $review->status === ArmadaMatchReview::STATUS_DISCARDED) {
            $result->skipped++;
            $result->addNotice($label.': sudah diabaikan sebelumnya lewat konfirmasi manual, dilewati.');

            return;
        }

        $key = $this->compositeKey(
            $this->pickString($row, ['nomer_pol']),
            $picName
        );
        $candidates = $key !== null ? ($adoptableByKey[$key] ?? []) : [];

        if (count($candidates) === 1) {
            $localId = $candidates[0];
            DB::table('customers')->where('customer_id', $localId)->update($attributes);
            $byRef[$refArmadaId] = $localId;
            $this->forgetCandidate($adoptableByKey, $key, $localId);
            $result->updated++;
            $result->addNotice($label.' diadopsi ke pelanggan/armada Pegasus yang sudah ada (customer_id '.$localId.').');

            $this->clearStaleReview($review);

            return;
        }

        if (count($candidates) === 0) {
            $localId = (int) DB::table('customers')->insertGetId($attributes + [
                'customer_code' => (new Customer())->generateCustomerID(),
                'status' => 1,
                'created_at' => $now,
                'created_by' => null,
            ], 'customer_id');

            $byRef[$refArmadaId] = $localId;
            $result->inserted++;

            $this->clearStaleReview($review);

            return;
        }

        // Lebih dari satu kandidat — flowchart tidak menjawab ini secara
        // eksplisit, diantrekan untuk konfirmasi manual, bukan ditebak.
        ArmadaMatchReview::updateOrCreate(
            ['ref_armada_id' => $refArmadaId],
            [
                'pic_name' => mb_substr($picName, 0, 250),
                'nomer_pol' => $this->pickString($row, ['nomer_pol']) ?: null,
                'nomer_telp' => $this->pickString($row, ['nomer_telp']) ?: null,
                'saldo_armada' => $this->pickInt($row, ['saldo_armada']),
                'candidate_customer_ids' => $candidates,
                'status' => ArmadaMatchReview::STATUS_PENDING,
            ]
        );

        $result->skipped++;
        $result->addNotice(
            $label.': cocok dengan '.count($candidates).' pelanggan/armada Pegasus sekaligus (No Pol + PIC '
            .'sama) — menunggu konfirmasi manual pada langkah "Konfirmasi Armada Ambigu".'
        );
    }

    /**
     * ref_armada_id PMO => customer_id, untuk baris yang sudah tersambung.
     *
     * @return array<int, int>
     */
    private function loadLinkedCustomers(): array
    {
        return DB::table('customers')
            ->whereNotNull('ref_armada_id')
            ->pluck('customer_id', 'ref_armada_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Kunci komposit (No Pol + PIC dinormalisasi) => daftar customer_id yang
     * BELUM tersambung ke PMO — kandidat adopsi Fase 2.
     *
     * @return array<string, array<int, int>>
     */
    private function loadAdoptableCustomers(): array
    {
        $map = [];

        DB::table('customers')
            ->whereNull('ref_armada_id')
            ->where('status', 1)
            ->select('customer_id', 'customer_pic', 'customer_notes')
            ->orderBy('customer_id')
            ->each(function ($row) use (&$map) {
                $key = $this->compositeKey((string) $row->customer_notes, (string) $row->customer_pic);
                if ($key === null) {
                    return;
                }

                $map[$key][] = (int) $row->customer_id;
            });

        return $map;
    }

    /**
     * @param  array<string, array<int, int>>  $adoptableByKey
     */
    private function forgetCandidate(array &$adoptableByKey, ?string $key, int $customerId): void
    {
        if ($key === null || ! isset($adoptableByKey[$key])) {
            return;
        }

        $remaining = array_values(array_filter(
            $adoptableByKey[$key],
            static fn (int $id) => $id !== $customerId
        ));

        if ($remaining === []) {
            unset($adoptableByKey[$key]);
        } else {
            $adoptableByKey[$key] = $remaining;
        }
    }

    /**
     * Baris ini sekarang berhasil diselesaikan otomatis (adopsi tunggal
     * atau insert baru) — antrean konfirmasi manual yang tersisa dari
     * percobaan sebelumnya (mis. duplikatnya sudah dirapikan operator sejak
     * itu) sudah basi, bukan lagi mencerminkan keadaan sekarang.
     */
    private function clearStaleReview(?ArmadaMatchReview $review): void
    {
        if ($review && $review->status === ArmadaMatchReview::STATUS_PENDING) {
            $review->delete();
        }
    }

    private function compositeKey(string $nomerPol, string $picName): ?string
    {
        $pic = ReferenceMatcher::normalise($picName);
        if ($pic === '') {
            return null;
        }

        return ReferenceMatcher::normalise($nomerPol).'|'.$pic;
    }
}
