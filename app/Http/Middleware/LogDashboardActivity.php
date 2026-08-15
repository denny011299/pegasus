<?php

namespace App\Http\Middleware;

use App\Models\DashboardChangeLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class LogDashboardActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldLogChange($request, $response)) {
            $this->logChange($request);
        } elseif ($this->shouldLogOpen($request, $response)) {
            // GitHub #53: "jam dia buka modul atau menu apapun" — traceability tambahan di
            // samping log mutasi di atas. Baris 'open' dicatat dengan activity_type terpisah
            // supaya ReportController::dashboardChangeLogCounts() (KPI "Changelog" = antrean
            // butuh ACC Direktur) tidak ikut menghitung page-view biasa sebagai item pending.
            $this->logOpen($request);
        }

        return $response;
    }

    private function logChange(Request $request): void
    {
        $action = $this->detectAction($request);
        if ($action === null) {
            return;
        }

        $moduleKey = $this->detectModuleKey($request);
        $moduleLabel = $this->formatModuleLabel($moduleKey);
        $reference = $this->detectReference($request);
        $staffId = (int) (session('user')->staff_id ?? 0);

        DashboardChangeLog::create([
            'module_key' => $moduleKey,
            'activity_type' => 'change',
            'module_label' => $moduleLabel,
            'reference' => $reference,
            'what_changed' => ucfirst($action).' pada '.$moduleLabel,
            'summary' => trim(($reference ? $reference.' · ' : '').'Aksi '.$action),
            'url' => $this->detectSafeUrl($request),
            'url_label' => 'Buka menu',
            'created_by' => $staffId > 0 ? $staffId : null,
            'meta' => [
                'method' => $request->method(),
                'path' => $request->path(),
                'action' => $action,
            ],
        ]);
    }

    /**
     * Catat "staf X membuka menu Y" + isi durasi baris 'open' sebelumnya milik staf yang sama.
     * Ini estimasi pasif (jarak waktu ke request berikutnya), bukan sinyal tab-ditutup yang
     * sesungguhnya — lihat catatan cap 4 jam di bawah.
     */
    private function logOpen(Request $request): void
    {
        $staffId = (int) (session('user')->staff_id ?? 0);
        if ($staffId <= 0) {
            return;
        }

        $moduleKey = $this->detectModuleKey($request);
        $moduleLabel = $this->formatModuleLabel($moduleKey);
        $now = now();

        // Debounce: staf yang sama membuka modul yang sama berkali-kali (klik-klik/refresh)
        // dalam jendela singkat tidak perlu jadi baris baru tiap kali.
        $recentlyOpened = DashboardChangeLog::where('created_by', $staffId)
            ->where('module_key', $moduleKey)
            ->where('activity_type', 'open')
            ->where('created_at', '>=', $now->copy()->subMinutes(15))
            ->exists();
        if ($recentlyOpened) {
            return;
        }

        // Tutup baris 'open' terakhir milik staf ini (modul manapun) yang belum punya durasi —
        // durasinya = jarak ke pembukaan menu berikutnya ini. Kalau jaraknya lebih dari 4 jam,
        // anggap tab lama itu cuma ditinggal (idle/browser ditutup tanpa navigasi lagi) dan
        // jangan diisi durasi yang menyesatkan.
        $previous = DashboardChangeLog::where('created_by', $staffId)
            ->where('activity_type', 'open')
            ->whereNull('duration_seconds')
            ->orderByDesc('created_at')
            ->first();
        if ($previous) {
            // Explicit $absolute=true + cast to int: Carbon 3's diffInSeconds() defaults to a
            // signed, sub-second-precision float (negative here since $previous is in the past).
            $seconds = (int) $now->diffInSeconds($previous->created_at, true);
            if ($seconds <= 4 * 3600) {
                $previous->duration_seconds = $seconds;
                $previous->save();
            }
        }

        $staffName = session('user')->staff_name ?? '-';

        DashboardChangeLog::create([
            'module_key' => $moduleKey,
            'activity_type' => 'open',
            'module_label' => $moduleLabel,
            'reference' => null,
            'what_changed' => 'Membuka menu '.$moduleLabel,
            'summary' => $staffName.' membuka '.$moduleLabel,
            // Beda dari logChange(): request GET ini SENDIRI adalah halaman yang dibuka, jadi
            // link-nya ke path saat ini -- bukan detectSafeUrl() (referer) yang dipakai untuk
            // baris mutasi karena POST tidak punya halaman sendiri untuk dituju.
            'url' => url(trim($request->path(), '/')),
            'url_label' => 'Buka menu',
            'created_by' => $staffId,
            'meta' => [
                'method' => $request->method(),
                'path' => $request->path(),
            ],
            'duration_seconds' => null,
        ]);
    }

    private function shouldLogChange(Request $request, Response $response): bool
    {
        if (!in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }
        if ($response->getStatusCode() >= 400) {
            return false;
        }
        if (!session()->has('user')) {
            return false;
        }

        $path = strtolower(trim($request->path(), '/'));
        if ($path === '' || str_starts_with($path, 'get')) {
            return false;
        }
        if (in_array($path, ['dismissdashboardqueueitem', 'updatedashboardwidgets', 'updatepermission'], true)) {
            return false;
        }

        return true;
    }

    /**
     * Hanya GET yang benar-benar me-render sebuah halaman (bukan endpoint AJAX/data) yang
     * dihitung sebagai "buka menu" — dibedakan lewat $response->original instanceof View,
     * karena response()->json(...)/JsonResponse tidak punya properti itu sama sekali.
     */
    private function shouldLogOpen(Request $request, Response $response): bool
    {
        if (strtoupper($request->method()) !== 'GET') {
            return false;
        }
        if ($response->getStatusCode() >= 400) {
            return false;
        }
        if (!session()->has('user')) {
            return false;
        }
        if (!($response instanceof HttpResponse) || !(($response->original ?? null) instanceof View)) {
            return false;
        }

        $path = strtolower(trim($request->path(), '/'));
        if (in_array($path, ['', 'up', 'login', 'logout'], true)) {
            return false;
        }

        return true;
    }

    private function detectAction(Request $request): ?string
    {
        $txt = strtolower($request->path().' '.($request->route()?->getName() ?? ''));
        $map = [
            'insert' => ['insert', 'create', 'store', 'tambah'],
            'update' => ['update', 'edit', 'perbarui'],
            'delete' => ['delete', 'remove', 'hapus'],
            'acc' => ['acc', 'accept', 'approve', 'konfirmasi'],
            'tolak' => ['decline', 'reject', 'tolak'],
        ];
        foreach ($map as $label => $keys) {
            foreach ($keys as $k) {
                if (str_contains($txt, $k)) {
                    return $label;
                }
            }
        }

        return null;
    }

    private function detectModuleKey(Request $request): string
    {
        $path = trim($request->path(), '/');
        $seg = $path === '' ? 'dashboard' : explode('/', $path)[0];
        $seg = strtolower(preg_replace('/[^a-z0-9_]+/i', '_', $seg) ?? 'dashboard');
        return $seg !== '' ? $seg : 'dashboard';
    }

    private function formatModuleLabel(string $moduleKey): string
    {
        return Str::title(str_replace('_', ' ', $moduleKey));
    }

    private function detectReference(Request $request): ?string
    {
        $candidates = [
            'so_invoice_no',
            'so_number',
            'po_number',
            'production_code',
            'pi_code',
            'ref_num',
            'reference',
            'barcode',
            'role_name',
            'staff_name',
        ];

        foreach ($candidates as $key) {
            $val = trim((string) $request->input($key, ''));
            if ($val !== '') {
                return $val;
            }
        }

        $idCandidates = ['so_id', 'po_id', 'production_id', 'pi_id', 'product_id', 'supplies_id', 'role_id', 'staff_id', 'id'];
        foreach ($idCandidates as $key) {
            $val = $request->input($key);
            if ($val !== null && $val !== '') {
                return strtoupper($key).'#'.$val;
            }
        }

        return null;
    }

    private function detectSafeUrl(Request $request): string
    {
        $ref = (string) $request->headers->get('referer', '');
        if ($ref !== '') {
            $parts = parse_url($ref);
            $host = strtolower((string) ($parts['host'] ?? ''));
            $currentHost = strtolower((string) $request->getHost());
            $path = (string) ($parts['path'] ?? '');
            if ($host !== '' && $host === $currentHost && $path !== '') {
                return url(trim($path, '/'));
            }
        }

        return url('admin');
    }
}
