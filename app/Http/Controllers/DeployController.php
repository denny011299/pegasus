<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Browser-triggerable Artisan runner for shared hosting without SSH/terminal
 * access (no cPanel "Terminal" app, no remote DB access). Hit these routes
 * from a browser after uploading new code (File Manager/FTP/Git) to run
 * pending migrations and clear caches — Laravel's own `migrations` table
 * keeps track of what's already applied, so there's nothing to track by hand.
 *
 * Security relies entirely on DEPLOY_TOKEN in .env being long/secret — these
 * routes are intentionally NOT behind checkLogin (deploy can happen before
 * any session/user setup is valid) and are NOT registered under check.access.
 * See cdocs/docs/deploy-shared-hosting.md for the full runbook, including the
 * cron-job and .cpanel.yml alternatives.
 *
 * seedSnapshot() and freshEmpty() are DATA-DESTRUCTIVE (db:seed truncates
 * every snapshot table and reloads dev fixtures; migrate:fresh drops every
 * table). They're POST-only (so a leaked link/crawler/prefetch can't trigger
 * them via a plain GET) and require a typed confirm phrase on top of the
 * token, and every call is logged. Use console() for a browser form that
 * enforces the confirm-phrase typing without needing curl.
 */
class DeployController extends Controller
{
    private function guard(Request $request): void
    {
        $expected = (string) env('DEPLOY_TOKEN', '');

        if (strlen($expected) < 24) {
            abort(403, 'DEPLOY_TOKEN belum diset (atau kurang dari 24 karakter) di .env. Set token acak yang panjang sebelum endpoint ini bisa dipakai.');
        }

        $given = (string) $request->query('token', '');

        if ($given === '' || ! hash_equals($expected, $given)) {
            abort(403);
        }
    }

    private function requireConfirmation(Request $request, string $phrase): void
    {
        $given = (string) $request->input('confirm', '');

        if ($given !== $phrase) {
            abort(403, "Aksi ini menghapus data. Kirim POST dengan field 'confirm' persis berisi: {$phrase}");
        }
    }

    private function logDestructive(Request $request, string $action): void
    {
        Log::warning("[deploy] {$action} triggered via /deploy console", [
            'ip' => $request->ip(),
            'at' => now()->toDateTimeString(),
        ]);
    }

    public function migrate(Request $request)
    {
        $this->guard($request);

        Artisan::call('migrate', ['--force' => true]);

        return response('<pre>'.e(Artisan::output()).'</pre>');
    }

    public function status(Request $request)
    {
        $this->guard($request);

        Artisan::call('migrate:status');

        return response('<pre>'.e(Artisan::output()).'</pre>');
    }

    public function optimizeClear(Request $request)
    {
        $this->guard($request);

        $log = '';

        foreach (['config:clear', 'cache:clear', 'view:clear', 'route:clear'] as $command) {
            Artisan::call($command);
            $log .= "$ php artisan {$command}\n".Artisan::output()."\n";
        }

        return response('<pre>'.e($log).'</pre>');
    }

    /**
     * Full dev-data snapshot restore (database/seeders/data/*.json). Truncates
     * every table the snapshot covers and reloads it — intended for staging,
     * not for a production DB holding real data.
     */
    public function seedSnapshot(Request $request)
    {
        $this->guard($request);
        $this->requireConfirmation($request, 'SEED');
        $this->logDestructive($request, 'db:seed (full snapshot restore)');

        Artisan::call('db:seed', ['--force' => true]);

        return response('<pre>'.e(Artisan::output()).'</pre>');
    }

    /**
     * Drops every table and reruns all migrations, leaving an empty schema
     * with NO data — for standing up a fresh production DB before real usage
     * starts. Does not seed; call seedSnapshot() after if dev data is wanted.
     */
    public function freshEmpty(Request $request)
    {
        $this->guard($request);
        $this->requireConfirmation($request, 'FRESH');
        $this->logDestructive($request, 'migrate:fresh (drops all tables)');

        Artisan::call('migrate:fresh', ['--force' => true]);

        return response('<pre>'.e(Artisan::output()).'</pre>');
    }

    /**
     * Small standalone HTML form so seed/fresh can be triggered from a
     * browser without curl, while still forcing the operator to type the
     * confirm phrase by hand (never pre-filled).
     */
    public function console(Request $request)
    {
        $this->guard($request);

        return view('Deploy.console', ['token' => $request->query('token')]);
    }
}
