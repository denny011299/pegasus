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

        // Ditambahkan (2026-08-14), dihapus lagi (2026-08-15): sempat ada debounce 15 menit
        // per staf+modul di sini supaya klik-klik/refresh cepat tidak jadi baris baru tiap
        // kali. Ternyata itu menutupi bug yang jauh lebih parah -- `return` dini di sini
        // melompati juga logika "tutup sesi 'open' sebelumnya" di bawah, bukan cuma logika
        // insert baris baru. Jadi membuka ulang modul yang SAMA dalam 15 menit (mis. balik ke
        // Dashboard yang baru saja dibuka) diam-diam gagal menutup modul LAIN yang sedang
        // terbuka -- persis gejala yang dilaporkan user ("Dashboard nav click still not
        // ending the previous page session"). Setiap kunjungan sekarang selalu dicatat.

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

        // Ditambahkan (2026-08-15): '' (root '/') SEBELUMNYA ikut dikecualikan di sini, disalin
        // dari daftar exclude shouldLogChange() -- tapi routes/web.php me-render dashboard
        // langsung di GET '/' (bukan redirect ke '/admin'), jadi itu JUSTRU rute yang paling
        // sering dipakai user untuk "kembali ke dashboard". Mengecualikannya berarti baris 'open'
        // sebelumnya (menu apa pun yang masih terbuka) tidak pernah ditutup saat user balik ke
        // dashboard lewat '/' -- selalu nyangkut di "Sedang dibuka". session()->has('user') di
        // atas sudah cukup menyaring pengunjung yang belum login, jadi '' aman untuk dicatat.
        $path = strtolower(trim($request->path(), '/'));
        if (in_array($path, ['up', 'login', 'logout'], true)) {
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

    /**
     * module_key adalah segmen pertama URL apa adanya (mis. "detailstockopname",
     * "insertstockopname") -- Str::title() begitu saja menghasilkan label yang teknis dan
     * membingungkan (user melaporkan: "Insertstockopname" dikira halaman untuk MEMBUKA form,
     * padahal itu baris mutasi AJAX; halaman form aslinya berlabel "Detailstockopname"). Basis
     * data ini menerjemahkan awalan aksi (insert/update/delete/detail/acc/dst.) + nama modul
     * dasar jadi label Indonesia yang jelas, mis. "detailstockopname" -> "Input Stok Opname",
     * "insertstockopname" -> "Tambah Stok Opname". module_key yang tidak dikenal tetap jatuh ke
     * fallback Str::title() lama, jadi modul yang belum dipetakan tidak pernah rusak/kosong --
     * cuma tetap teknis seperti sebelumnya. Hanya memengaruhi baris yang BARU dibuat setelah ini
     * (module_label disimpan permanen saat insert, bukan dihitung ulang saat ditampilkan).
     */
    private const MODULE_BASE_LABELS = [
        'dashboard' => 'Dashboard',
        'admin' => 'Dashboard',
        'stockopnamebahan' => 'Stok Opname Bahan Mentah',
        'stockopname' => 'Stok Opname',
        'stockalertsupplies' => 'Stock Alert Bahan',
        'stockalert' => 'Stock Alert',
        'customer' => 'Customer',
        'supplier' => 'Supplier',
        'product' => 'Produk',
        'supplies' => 'Bahan Mentah',
        'staff' => 'Staff',
        'purchaseorder' => 'Purchase Order',
        'salesorder' => 'Sales Order',
        'production' => 'Produksi',
        'productissues' => 'Retur Produk',
        'returnsupplies' => 'Retur Bahan Mentah',
        'role' => 'Role / Hak Akses',
        'area' => 'Area',
        'bank' => 'Bank',
        'category' => 'Kategori',
        'unit' => 'Satuan',
        'variant' => 'Variasi',
        'bom' => 'BOM (Resep Produksi)',
        'cashadmin' => 'Kas Operasional',
        'cashgudang' => 'Kas Gudang',
        'casharmada' => 'Kas Armada',
        'cashsales' => 'Kas Sales',
        'tt' => 'Tanda Terima',
        'sodelivery' => 'Pengiriman SO',
        'podelivery' => 'Pengiriman PO',
        'invoicepo' => 'Invoice PO',
        'invoiceso' => 'Invoice SO',
    ];

    // Diperiksa berurutan sesuai array ini -- "accept" HARUS sebelum "acc", kalau tidak
    // "acceptcashadmin" akan salah terpotong jadi "acc" + "eptcashadmin".
    private const MODULE_ACTION_PREFIXES = [
        'accept' => 'Terima',
        'decline' => 'Tolak',
        'insert' => 'Tambah',
        'update' => 'Ubah',
        'delete' => 'Hapus',
        'detail' => 'Input',
        'tolak' => 'Tolak',
        'acc' => 'ACC',
    ];

    private function formatModuleLabel(string $moduleKey): string
    {
        $key = strtolower($moduleKey);

        if (isset(self::MODULE_BASE_LABELS[$key])) {
            return self::MODULE_BASE_LABELS[$key];
        }

        foreach (self::MODULE_ACTION_PREFIXES as $prefix => $verb) {
            if (str_starts_with($key, $prefix)) {
                $base = substr($key, strlen($prefix));
                if (isset(self::MODULE_BASE_LABELS[$base])) {
                    return $verb.' '.self::MODULE_BASE_LABELS[$base];
                }
            }
        }

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
