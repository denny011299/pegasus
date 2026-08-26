<?php

namespace App\Http\Controllers;

use App\Support\StockOpnameUntouchedUnitHealer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Browser-triggerable runner for GitHub #78's one-time repair tool (App\Support\
 * StockOpnameUntouchedUnitHealer) — for a production host that has NEITHER SSH/terminal access
 * (so `php artisan stockopname:heal-untouched-units` can't run there) NOR direct database access
 * the team is willing to use (so hand-pasting the --sql output into phpMyAdmin/Adminer is also off
 * the table). This mirrors the DEPLOY_TOKEN-gated browser-Artisan-runner pattern designed for
 * exactly this shared-hosting situation — see cdocs/docs/deploy-shared-hosting.md and
 * App\Http\Controllers\DeployController on branch fase2/main (not merged into main — that module
 * also carries migrate/seed/fresh-empty and a SnapshotRegistry dependency this doesn't need, so
 * the guard/confirm pattern is reproduced narrowly here rather than importing that whole module).
 *
 * Security: identical shape to DeployController's guard() — DEPLOY_TOKEN in .env, checked with
 * hash_equals, must be >= 24 chars. NOT behind checkLogin/check.access (same rationale: this has
 * to work even when no session/app state is trustworthy yet). preview() is read-only (GET, safe to
 * hit repeatedly — the healer's dry-run path never writes). apply() actually persists changes —
 * POST-only (a leaked link/crawler/prefetch can never fire it via GET) and requires a typed
 * confirm phrase on top of the token, and every call is logged.
 */
class StockOpnameHealController extends Controller
{
    /** Returns null when the request may proceed, or the 403 response to return immediately. */
    private function guard(Request $request): ?Response
    {
        $expected = (string) env('DEPLOY_TOKEN', '');

        if (strlen($expected) < 24) {
            return $this->forbidden('DEPLOY_TOKEN belum diset (atau kurang dari 24 karakter) di .env. Set token acak yang panjang sebelum endpoint ini bisa dipakai.');
        }

        $given = (string) $request->query('token', '');

        if ($given === '') {
            return $this->forbidden('Token deploy tidak dikirim. Tambahkan ?token=<DEPLOY_TOKEN> di alamat.');
        }

        if (! hash_equals($expected, $given)) {
            return $this->forbidden('Token deploy salah.');
        }

        return null;
    }

    private function requireConfirmation(Request $request, string $phrase): ?Response
    {
        $given = (string) $request->input('confirm', '');

        if ($given !== $phrase) {
            return $this->forbidden("Aksi ini menulis ke database. Kirim POST dengan field 'confirm' persis berisi: {$phrase}");
        }

        return null;
    }

    private function forbidden(string $message = 'Forbidden.'): Response
    {
        return response($message, 403)->header('Content-Type', 'text/plain');
    }

    /**
     * Halaman utama: form untuk memilih sto_id/stob_id, menampilkan preview (dry-run, GET) dan
     * tombol apply (POST + frasa konfirmasi).
     */
    public function console(Request $request)
    {
        if ($blocked = $this->guard($request)) {
            return $blocked;
        }

        return view('Deploy.stockopname-heal', [
            'token' => $request->query('token'),
            'id' => null,
            'bahan' => false,
            'result' => null,
            'applied' => false,
        ]);
    }

    /** Read-only dry-run: sama seperti `--sql`/tanpa `--apply` di CLI, tidak menulis apa pun. */
    public function preview(Request $request)
    {
        if ($blocked = $this->guard($request)) {
            return $blocked;
        }

        $id = (int) $request->query('id', 0);
        if ($id <= 0) {
            return $this->forbidden('Parameter id wajib diisi dengan sto_id/stob_id yang valid.');
        }

        $bahan = $request->boolean('bahan');
        $healer = new StockOpnameUntouchedUnitHealer();
        $result = $bahan ? $healer->healSupplies($id, apply: false) : $healer->healProduct($id, apply: false);

        return view('Deploy.stockopname-heal', [
            'token' => $request->query('token'),
            'id' => $id,
            'bahan' => $bahan,
            'result' => $result,
            'applied' => false,
        ]);
    }

    /**
     * Benar-benar menulis perubahan (via StockOpnameUntouchedUnitHealer::healProduct()/
     * healSupplies() dengan apply=true — Eloquent + DB::transaction, jalur normal aplikasi, bukan
     * SQL mentah). POST-only, token + frasa konfirmasi HEAL wajib, setiap panggilan dicatat log.
     */
    public function apply(Request $request)
    {
        if ($blocked = $this->guard($request)) {
            return $blocked;
        }
        if ($blocked = $this->requireConfirmation($request, 'HEAL')) {
            return $blocked;
        }

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return $this->forbidden('Parameter id wajib diisi dengan sto_id/stob_id yang valid.');
        }

        $bahan = $request->boolean('bahan');

        Log::warning('[deploy] stockopname-heal apply triggered via /deploy/heal-stock-opname console', [
            'id' => $id,
            'bahan' => $bahan,
            'ip' => $request->ip(),
            'at' => now()->toDateTimeString(),
        ]);

        $healer = new StockOpnameUntouchedUnitHealer();
        $result = $bahan ? $healer->healSupplies($id, apply: true) : $healer->healProduct($id, apply: true);

        return view('Deploy.stockopname-heal', [
            'token' => $request->query('token'),
            'id' => $id,
            'bahan' => $bahan,
            'result' => $result,
            'applied' => true,
        ]);
    }
}
