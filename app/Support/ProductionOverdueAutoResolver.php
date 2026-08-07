<?php

namespace App\Support;

use App\Http\Controllers\ProductionController;
use App\Models\Production;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Auto-timeout untuk produksi yang mangkrak lebih dari ~4 hari sejak production_date:
 * - status = 1 (menunggu ACC) → auto-ACC (accProduction() controller, mutasi stok penuh),
 *   fallback ke declineProduction() kalau auto-ACC itu sendiri gagal (mis. stok kurang).
 * - status = 4 (menunggu persetujuan batal) → auto-tolak permintaan batal (kembali ke status 2
 *   "Berhasil", pure status flip — tidak ada reversal stok karena permintaan batal sendiri belum
 *   pernah mengubah stok apa pun).
 *
 * Extracted dari `Production::getProduction()`, yang dulu menjalankan logika ini inline di tengah
 * loop tampilan list — jadi perilakunya diam-diam tergantung baris mana yang kebetulan lolos filter
 * request GET yang sedang berjalan. Sekarang query independen (bukan bergantung ke hasil query GET
 * manapun), supaya perilakunya sama persis baik dipanggil dari getProduction() maupun dari
 * `php artisan production:resolve-overdue` (lihat ResolveOverdueProductionsCommand).
 *
 * `getProduction()` masih memanggil ini di setiap request hari ini (belum dicabut dari GET) — kalau
 * nanti mau dipindah supaya HANYA berjalan lewat cron, cukup comment-out satu baris pemanggilnya di
 * `Production::getProduction()`, logika di sini tidak perlu diubah.
 */
class ProductionOverdueAutoResolver
{
    private const OVERDUE_AFTER_DAYS = 4;

    /**
     * @return array{
     *   pending_checked: int,
     *   pending_approved: int,
     *   pending_declined: int,
     *   cancel_checked: int,
     *   cancel_timed_out: int,
     *   details: list<array{production_id: int, production_code: string, action: string}>
     * }
     */
    public function resolveOverdue(?int $overdueAfterDays = null, bool $dryRun = false): array
    {
        $overdueAfterDays = $overdueAfterDays ?? self::OVERDUE_AFTER_DAYS;

        $summary = [
            'pending_checked' => 0,
            'pending_approved' => 0,
            'pending_declined' => 0,
            'cancel_checked' => 0,
            'cancel_timed_out' => 0,
            'details' => [],
        ];

        $pending = Production::where('status', 1)->get();
        foreach ($pending as $production) {
            if (!$this->isOverdue($production->production_date, $overdueAfterDays)) {
                continue;
            }
            $summary['pending_checked']++;

            if ($dryRun) {
                $summary['details'][] = $this->detailRow($production, 'would auto-acc');
                continue;
            }

            $request = new Request();
            $request->merge(['production_id' => $production->production_id]);
            $result = (new ProductionController())->accProduction($request);

            // accProduction() doesn't always return bare 1 on success on this branch — when the
            // production's output stays in the same warehouse it produced in (no Stock Transfer
            // needed), it returns a JsonResponse with status:1 instead. A strict `=== 1` check
            // (correct on main, where accProduction() has no Stock Transfer branching) misreads
            // that as a failure and falls through to auto-decline. Same check this branch's own
            // now-removed inline predecessor used in Production::getProduction().
            $isSuccess = $result === 1
                || ($result instanceof \Illuminate\Http\JsonResponse
                    && (int) ($result->getData(true)['status'] ?? 0) === 1);

            if ($isSuccess) {
                $this->markResolvedBySystem($production);
                $summary['pending_approved']++;
                $summary['details'][] = $this->detailRow($production, 'auto-approved');
            } else {
                $declineRequest = new Request();
                $declineRequest->merge(['production_id' => $production->production_id]);
                (new ProductionController())->declineProduction($declineRequest);
                $this->markResolvedBySystem($production);
                $summary['pending_declined']++;
                $summary['details'][] = $this->detailRow($production, 'auto-declined (acc failed)');
            }
        }

        $cancelRequests = Production::where('status', 4)->get();
        foreach ($cancelRequests as $production) {
            if (!$this->isOverdue($production->production_date, $overdueAfterDays)) {
                continue;
            }
            $summary['cancel_checked']++;

            if ($dryRun) {
                $summary['details'][] = $this->detailRow($production, 'would auto-reject cancel request');
                continue;
            }

            (new Production())->accProduction(['production_id' => $production->production_id]);
            $this->markResolvedBySystem($production);
            $summary['cancel_timed_out']++;
            $summary['details'][] = $this->detailRow($production, 'cancel request auto-rejected');
        }

        return $summary;
    }

    /**
     * Tandai produksi ini sebagai diproses OTOMATIS oleh sistem, bukan aksi staf sungguhan —
     * accProduction()/declineProduction() di atas tetap mengisi acc_by dari Session::get('user')
     * kalau kebetulan ada session aktif (mis. auto-timeout ini terpicu lewat GET /getProduction
     * saat seorang staf sedang membuka halaman list), jadi acc_by SENDIRI tidak bisa dipercaya
     * untuk membedakan "staf yang benar-benar approve" dari "cuma kebetulan lagi buka halaman".
     * Kolom ini eksplisit menandai itu, terlepas dari isi acc_by.
     *
     * Dibungkus Schema::hasColumn() supaya tetap aman kalau migration kolom ini belum ter-merge di
     * branch/environment yang menjalankan kode ini — lihat migration
     * 2026_08_05_010000_add_resolved_by_system_to_productions_table.
     */
    private function markResolvedBySystem(Production $production): void
    {
        if (!Schema::hasColumn('productions', 'resolved_by_system')) {
            return;
        }

        $production->resolved_by_system = true;
        $production->save();
    }

    private function isOverdue(string $productionDate, int $overdueAfterDays): bool
    {
        $diffDays = Carbon::now()->diffInDays(Carbon::parse($productionDate), false);

        return $diffDays < -$overdueAfterDays;
    }

    private function detailRow(Production $production, string $action): array
    {
        return [
            'production_id' => (int) $production->production_id,
            'production_code' => (string) $production->production_code,
            'action' => $action,
        ];
    }
}
