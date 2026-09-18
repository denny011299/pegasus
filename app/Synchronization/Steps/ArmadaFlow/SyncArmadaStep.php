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
 *
 * Rekonsiliasi lewat kode kendaraan (GitHub #187 lanjutan, PMO#16): PMO
 * confirmed /getArmada TIDAK mengirim kode kendaraan (oms_vehicle.kode) sama
 * sekali — hanya armada_id numerik. Kode itu (mis. "PMOT-003") justru SAMA
 * PERSIS dengan armada_code yang dikirim POST /shipments/scheduled/shipped,
 * dan itulah customers.customer_code yang dimaksud kontrak Shipment API.
 * Tanpa kode itu di /getArmada, Sinkronisasi Armada tidak pernah tahu satu
 * armada_id numerik = satu armada_code tertentu, jadi kalau shipment sampai
 * duluan (lewat App\Support\ArmadaUpsert, yang membuat baris minim ber-
 * customer_code = armada_code) SEBELUM armada itu tersinkron, akan ada DUA
 * baris customers untuk satu armada yang sama: satu dari sini (customer_code
 * auto-generate), satu dari shipment (customer_code = kode asli).
 *
 * PMO sudah diminta menambahkan kode itu ke /getArmada (PMO#16, field
 * "code"/"kode"). Begitu tersedia, langkah ini membacanya (lihat blok kode
 * di awal syncArmada()) dan MENGUTAMAKANNYA di atas pencocokan No Pol+PIC:
 * - Kode cocok dengan baris yang SUDAH tersambung lewat ref_armada_id
 *   armada ini -> lanjut Fase 1 apa adanya (customer_code ikut disamakan).
 * - Kode cocok dengan baris LAIN yang belum tersambung -> digabung
 *   (mergeDuplicateByCode()): sales_orders milik baris duplikat itu
 *   dipindah ke baris yang tersambung, baris duplikatnya dinonaktifkan.
 *   Cakupan gabung SENGAJA hanya sales_orders — baris duplikat di sini
 *   HANYA pernah dibuat ArmadaUpsert (upsert bare dari shipment), yang
 *   satu-satunya jejaknya di Pegasus adalah baris sales_orders yang
 *   dibuatnya sendiri; tidak ada tabel lain yang mungkin sudah menyentuhnya.
 * - Kode belum dipakai siapa pun -> jadi kandidat adopsi (setara No Pol+PIC
 *   tunggal) atau customer_code untuk baris baru, menggantikan
 *   Customer::generateCustomerID().
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
        $code = $this->pickString($row, ['code', 'kode', 'armada_code']);
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

        if ($code !== '') {
            $byCode = DB::table('customers')->where('customer_code', $code)->first();
            $byCodeRefArmadaId = $byCode !== null && $byCode->ref_armada_id !== null ? (int) $byCode->ref_armada_id : null;

            if ($byCode !== null && $byCodeRefArmadaId !== null && $byCodeRefArmadaId !== $refArmadaId) {
                // customer_code ini sudah terpakai armada LAIN (ref_armada_id-nya beda) — kode
                // seharusnya unik per kendaraan di PMO, jadi ini kejanggalan data, bukan sesuatu
                // yang aman ditebak/ditimpa begitu saja. Lanjut tanpa menyentuh customer_code sama
                // sekali (perilaku sebelum GitHub #187 lanjutan), dilaporkan supaya operator sadar.
                $result->addNotice(
                    $label.': kode "'.$code.'" sudah dipakai armada lain (customer_id '.$byCode->customer_id
                    .', ref_armada_id '.$byCodeRefArmadaId.') — customer_code TIDAK disamakan, periksa data PMO.'
                );
            } elseif ($byCode !== null && isset($byRef[$refArmadaId]) && (int) $byCode->customer_id !== $byRef[$refArmadaId]) {
                // Dua baris untuk satu armada: satu sudah tersambung lewat ref_armada_id (biasanya
                // dari sinkronisasi sebelum PMO mengirim kode), satu lagi punya customer_code =
                // kode asli (biasanya dari ArmadaUpsert lewat shipment). Gabung ke baris yang
                // tersambung, bukan biarkan dua-duanya hidup.
                $this->mergeDuplicateByCode((int) $byCode->customer_id, $byRef[$refArmadaId], $code, $label, $result);
                $attributes['customer_code'] = $code;
            } else {
                if ($byCode !== null && ! isset($byRef[$refArmadaId])) {
                    // Belum tersambung lewat ref_armada_id sama sekali, tapi kodenya sudah dikenal
                    // (dan tidak dipakai armada lain) — baris itu yang diadopsi, No Pol+PIC tidak
                    // perlu dicocokkan lagi.
                    $byRef[$refArmadaId] = (int) $byCode->customer_id;
                    $this->forgetCandidate(
                        $adoptableByKey,
                        $this->compositeKey((string) $byCode->customer_notes, (string) $byCode->customer_pic),
                        (int) $byCode->customer_id
                    );
                }

                $attributes['customer_code'] = $code;
            }
        }

        // Fase 1 — sudah tersambung (langsung atau baru saja lewat kecocokan kode di atas):
        // perbarui langsung, tidak ada logika No Pol/PIC yang disentuh sama sekali.
        if (isset($byRef[$refArmadaId])) {
            DB::table('customers')->where('customer_id', $byRef[$refArmadaId])->update($attributes);
            $result->updated++;
            $this->clearStaleReview($reviewsByRef->get($refArmadaId));

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
                // $attributes['customer_code'] hanya terisi kalau kodenya bebas dipakai (lihat blok
                // kode di awal method) — kalau konflik dengan armada lain, ini SENGAJA jatuh ke
                // generateCustomerID() alih-alih memaksakan kode yang sudah dipegang baris lain.
                'customer_code' => $attributes['customer_code'] ?? (new Customer())->generateCustomerID(),
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

    /**
     * Gabungkan baris duplikat (customer_code cocok, tapi ref_armada_id belum diisi — dibuat
     * App\Support\ArmadaUpsert lewat shipment SEBELUM armada ini tersambung ke sinkronisasi) ke
     * baris yang sudah tersambung. Cakupannya SENGAJA sempit: pindahkan sales_orders.so_customer,
     * lalu nonaktifkan baris duplikatnya — baris begini tidak pernah disentuh proses lain
     * (ArmadaUpsert cuma menulis customers + sales_orders, tidak pernah lebih), jadi dua tabel itu
     * sudah cukup. Tidak menghapus baris duplikat (soft, status=0) — jejaknya tetap ada untuk
     * audit, dan customer_code-nya sengaja TIDAK dilepas (dibiarkan seperti apa adanya) supaya
     * tidak bisa dipakai ulang secara tidak sengaja.
     */
    private function mergeDuplicateByCode(int $duplicateId, int $survivingId, string $code, string $label, SyncStepResult $result): void
    {
        DB::transaction(function () use ($duplicateId, $survivingId, $code) {
            DB::table('sales_orders')
                ->where('so_customer', (string) $duplicateId)
                ->update(['so_customer' => (string) $survivingId]);

            // customer_code UNIK — baris duplikat harus melepas kodenya di sini (bukan cuma
            // dinonaktifkan) supaya baris yang tersambung bisa mengklaimnya di update yang
            // menyusul. Diganti nama, bukan null polos, supaya masih terlihat kode aslinya kalau
            // baris ini pernah diperiksa manual belakangan.
            DB::table('customers')->where('customer_id', $duplicateId)->update([
                'status' => 0,
                'customer_code' => mb_substr($code.'-merged-into-'.$survivingId, 0, 64),
            ]);
        });

        $result->addNotice(
            $label.': baris duplikat (customer_id '.$duplicateId.', dibuat sebelumnya lewat shipment '
            .'karena belum tersambung ke sinkronisasi) digabungkan ke customer_id '.$survivingId
            .' — sales_orders miliknya dipindahkan, baris duplikatnya dinonaktifkan.'
        );
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
