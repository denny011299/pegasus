<?php

namespace App\Synchronization\Steps\ArmadaFlow;

use App\Models\ArmadaMatchReview;
use App\Synchronization\Contracts\ReviewQueueStepHandler;
use App\Synchronization\SyncStepResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use InvalidArgumentException;

/**
 * Langkah 2 — Konfirmasi Armada Ambigu.
 *
 * Antrean baris armada PMO yang SyncArmadaStep tidak berani menebak (No Pol
 * + PIC cocok ke lebih dari satu pelanggan/armada Pegasus sekaligus).
 * Ditampilkan & diselesaikan langsung di pane langkah ini lewat
 * pendingReviews()/resolveReview() (ReviewQueueStepHandler) — bukan halaman
 * terpisah, dan bukan lewat tombol "Jalankan Sinkronisasi" biasa (tidak ada
 * PMO yang dipanggil di sini sama sekali).
 *
 * Dua aksi per baris (disepakati 2026-08-22):
 * - "connect" — sambungkan ke salah satu kandidat yang ditampilkan (bukan
 *   pencarian bebas — cuma kandidat yang memang bentrok saat pencocokan).
 *   MENIMPA customer_pic/customer_notes/customer_pic_phone/customer_saldo
 *   pelanggan itu dengan data PMO, sama seperti adopsi otomatis pada
 *   SyncArmadaStep — makanya wizard menampilkan peringatan global di atas
 *   daftar, bukan per baris.
 * - "discard" — diabaikan PERMANEN; ref_armada_id ini tidak akan diantrekan
 *   ulang pada sinkronisasi berikutnya (lihat SyncArmadaStep, cek status
 *   STATUS_DISCARDED).
 *
 * Tidak ada aksi "biarkan menggantung" — itu cuma berarti operator belum
 * mengklik apa-apa, barisnya tetap ada di pendingReviews() sampai memang
 * diselesaikan. handle() TIDAK PERNAH gagal karena masih ada yang
 * menggantung — itu keadaan akhir yang sah, bukan kegagalan.
 */
class ResolveArmadaConflictsStep extends ArmadaFlowStep implements ReviewQueueStepHandler
{
    public function handle(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $pending = ArmadaMatchReview::where('status', ArmadaMatchReview::STATUS_PENDING)->count();
            $result->processed = $pending;
            $result->withDetails(['Menunggu Konfirmasi Manual' => $pending]);

            $result->succeed($pending > 0
                ? $pending.' baris armada masih menunggu konfirmasi manual — lihat daftar di bawah.'
                : 'Tidak ada armada yang menunggu konfirmasi manual.');
        });
    }

    public function pendingReviews(): array
    {
        $reviews = ArmadaMatchReview::where('status', ArmadaMatchReview::STATUS_PENDING)
            ->orderBy('armada_match_review_id')
            ->get();

        $customerIds = $reviews->flatMap(fn (ArmadaMatchReview $review) => $review->candidate_customer_ids)
            ->unique()
            ->values();

        $customers = DB::table('customers')
            ->whereIn('customer_id', $customerIds)
            ->select('customer_id', 'customer_code', 'customer_pic', 'customer_notes', 'customer_pic_phone')
            ->get()
            ->keyBy('customer_id');

        return $reviews->map(fn (ArmadaMatchReview $review) => $this->present($review, $customers))->all();
    }

    public function resolveReview(int $reviewId, string $action, ?int $customerId): array
    {
        $review = ArmadaMatchReview::find($reviewId);

        if (! $review || $review->status !== ArmadaMatchReview::STATUS_PENDING) {
            throw new InvalidArgumentException('Baris konfirmasi ini tidak ditemukan atau sudah diselesaikan.');
        }

        match ($action) {
            'connect' => $this->connect($review, $customerId),
            'discard' => $this->discard($review),
            default => throw new InvalidArgumentException('Aksi "'.$action.'" tidak dikenal.'),
        };

        return $this->pendingReviews();
    }

    private function connect(ArmadaMatchReview $review, ?int $customerId): void
    {
        if ($customerId === null || ! in_array($customerId, $review->candidate_customer_ids, true)) {
            throw new InvalidArgumentException(
                'Pilih salah satu pelanggan/armada yang ditampilkan sebagai kandidat baris ini.'
            );
        }

        $customer = DB::table('customers')->where('customer_id', $customerId)->first();
        if (! $customer) {
            throw new InvalidArgumentException('Pelanggan/armada yang dipilih tidak ditemukan.');
        }

        $now = Carbon::now();

        DB::table('customers')->where('customer_id', $customerId)->update([
            'ref_armada_id' => $review->ref_armada_id,
            'customer_pic' => $review->pic_name,
            'customer_notes' => $review->nomer_pol,
            'customer_pic_phone' => $review->nomer_telp,
            'customer_saldo' => $review->saldo_armada,
            'updated_at' => $now,
        ]);

        $review->update([
            'status' => ArmadaMatchReview::STATUS_CONNECTED,
            'resolved_customer_id' => $customerId,
            'resolved_by' => $this->currentStaffId(),
            'resolved_at' => $now,
        ]);
    }

    private function discard(ArmadaMatchReview $review): void
    {
        $review->update([
            'status' => ArmadaMatchReview::STATUS_DISCARDED,
            'resolved_customer_id' => null,
            'resolved_by' => $this->currentStaffId(),
            'resolved_at' => Carbon::now(),
        ]);
    }

    private function currentStaffId(): ?int
    {
        $user = Session::get('user');

        return $user->staff_id ?? null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $customers
     * @return array<string, mixed>
     */
    private function present(ArmadaMatchReview $review, $customers): array
    {
        return [
            'id' => $review->armada_match_review_id,
            // String, bukan int: id PMO 16 digit dan sebagian melewati
            // Number.MAX_SAFE_INTEGER JavaScript (mis. 9506012026014615
            // terbaca 9506012026014616 setelah JSON.parse). Dikirim sebagai
            // string supaya yang tampil di wizard persis id aslinya.
            'ref_armada_id' => (string) $review->ref_armada_id,
            'pic_name' => $review->pic_name,
            'nomer_pol' => $review->nomer_pol,
            'nomer_telp' => $review->nomer_telp,
            'saldo_armada' => $review->saldo_armada,
            'candidates' => collect($review->candidate_customer_ids)
                ->map(function (int $customerId) use ($customers) {
                    $customer = $customers->get($customerId);

                    return [
                        'customer_id' => $customerId,
                        'customer_code' => $customer->customer_code ?? null,
                        'customer_pic' => $customer->customer_pic ?? null,
                        'customer_notes' => $customer->customer_notes ?? null,
                        'customer_pic_phone' => $customer->customer_pic_phone ?? null,
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}
