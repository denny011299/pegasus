<?php

namespace App\Http\Controllers;

use App\ExternalApi\ApiKeyManager;
use App\ExternalApi\Docs\ApiDocRegistry;
use App\ExternalApi\Docs\PlatformErrors;
use App\ExternalApi\Logging\LogCleanupStrategy;
use App\ExternalApi\Logging\RequestLogger;
use App\ExternalApi\Support\ApiEndpointSettings;
use App\ExternalApi\Support\ExternalApiPath;
use App\Models\ExternalApiKey;
use App\Models\ExternalApiRequestLog;
use App\Models\ExternalApplication;
use Illuminate\Http\Request;

/**
 * Halaman administrasi External API di modul Integrasi.
 *
 * Controller ini murni sisi admin (session + check.access). Endpoint yang
 * dipakai sistem pihak ketiga sama sekali tidak lewat sini — jalurnya ada di
 * routes/external-api/.
 */
class ExternalApiController extends Controller
{
    public function __construct(
        private readonly ApiDocRegistry $docs,
        private readonly ApiKeyManager $keys,
        private readonly RequestLogger $logger,
        private readonly ApiEndpointSettings $endpointSettings,
    ) {
    }

    /* ------------------------------------------------------------------ */
    /* Aplikasi Eksternal                                                  */
    /* ------------------------------------------------------------------ */

    public function externalApplication()
    {
        return view('Backoffice.ExternalApi.Applications');
    }

    function getExternalApplication(Request $req)
    {
        $data = (new ExternalApplication())->getExternalApplication($req->all());
        return response()->json($data);
    }

    function insertExternalApplication(Request $req)
    {
        $data = $req->all();
        return (new ExternalApplication())->insertExternalApplication($data);
    }

    function updateExternalApplication(Request $req)
    {
        $data = $req->all();
        return (new ExternalApplication())->updateExternalApplication($data);
    }

    function deleteExternalApplication(Request $req)
    {
        $data = $req->all();
        return (new ExternalApplication())->deleteExternalApplication($data);
    }

    function toggleExternalApplication(Request $req)
    {
        $data = $req->all();
        return (new ExternalApplication())->toggleExternalApplication($data);
    }

    /* ------------------------------------------------------------------ */
    /* API Key                                                             */
    /* ------------------------------------------------------------------ */

    /** Halaman kelola kunci milik satu aplikasi. */
    public function externalApplicationDetail($id)
    {
        $application = ExternalApplication::where('external_application_id', '=', $id)
            ->where('status', '=', 1)
            ->first();

        if (! $application) {
            abort(404, 'Aplikasi eksternal tidak ditemukan.');
        }

        return view('Backoffice.ExternalApi.ApplicationDetail', [
            'application' => $application,
            'environments' => $this->keys->environments(),
            'keyHeader' => $this->keys->header(),
        ]);
    }

    function getExternalApiKey(Request $req)
    {
        $data = (new ExternalApiKey())->getExternalApiKey($req->all());
        return response()->json($data);
    }

    /**
     * Kunci polos hanya ada di respons ini, sekali seumur hidup kunci
     * tersebut. Sesudah dialog pembuatan ditutup, tidak ada cara mengambilnya
     * kembali — yang tersimpan di database hanyalah hash-nya.
     */
    function insertExternalApiKey(Request $req)
    {
        $data = $req->all();
        $result = (new ExternalApiKey())->insertExternalApiKey($data);

        return response()->json([
            "status" => 1,
            "external_api_key_id" => $result["external_api_key_id"],
            "plain_key" => $result["plain_key"],
        ]);
    }

    function updateExternalApiKey(Request $req)
    {
        $data = $req->all();
        return (new ExternalApiKey())->updateExternalApiKey($data);
    }

    function revokeExternalApiKey(Request $req)
    {
        $data = $req->all();
        return (new ExternalApiKey())->revokeExternalApiKey($data);
    }

    function deleteExternalApiKey(Request $req)
    {
        $data = $req->all();
        return (new ExternalApiKey())->deleteExternalApiKey($data);
    }

    /* ------------------------------------------------------------------ */
    /* Dokumentasi                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Dokumentasi dipecah per modul: satu halaman "Umum" berisi hal yang
     * berlaku untuk semua endpoint, lalu satu halaman untuk tiap kelompok.
     *
     * Satu method ini melayani seluruhnya — kelompok baru cukup muncul di
     * config('externalapi.doc_groups') dan langsung punya halaman sendiri,
     * tanpa rute atau view tambahan.
     *
     * @param  string|null  $group  null = halaman Umum
     */
    public function externalApiDocumentation(Request $req, ?string $group = null)
    {
        return $this->renderApiDocumentation($req, $group, 'Backoffice.ExternalApi.Documentation', [
            'index' => 'externalApiDocumentation',
            'group' => 'externalApiDocumentationGroup',
        ]);
    }

    /**
     * Sama seperti externalApiDocumentation(), tapi diakses tanpa login —
     * untuk pengembang pihak ketiga yang belum/tidak punya akun Pegasus.
     * View-nya sendiri (Public.ApiDocumentation) yang tidak memuat sidebar
     * admin; isi (partials doc-nav/doc-general/doc-group) sama persis.
     *
     * Satu-satunya halaman yang tunduk pada saklar "Dokumentasi Publik" di
     * halaman Status API Eksternal — PER ENDPOINT, bukan per versi.
     * Endpoint yang belum dipublikasikan admin hilang dari daftar isi &
     * halaman kelompoknya di sini, tapi tetap tampil normal di halaman
     * dokumentasi admin di atas (staf yang sudah login tidak terpengaruh
     * saklar ini sama sekali).
     */
    public function publicApiDocumentation(Request $req, ?string $group = null)
    {
        return $this->renderApiDocumentation($req, $group, 'Public.ApiDocumentation', [
            'index' => 'apiDocsPublic',
            'group' => 'apiDocsPublicGroup',
        ], publicOnly: true);
    }

    /** Versi terpilih dari query ?version=, jatuh ke versi default bila kosong/tidak dikenal. */
    private function resolveDocVersion(Request $req): string
    {
        $versions = $this->docs->versions();
        $version = $req->get('version');

        if (! in_array($version, $versions, true)) {
            $version = $versions[0] ?? (string) config('externalapi.default_version', 'v1');
        }

        return $version;
    }

    private function renderApiDocumentation(Request $req, ?string $group, string $view, array $docRoute, bool $publicOnly = false)
    {
        $version = $this->resolveDocVersion($req);
        $groups = $this->docs->grouped($version);
        $current = null;

        if ($group !== null) {
            $current = $this->docs->group($group, $version);

            if (! $current) {
                abort(404, 'Kelompok dokumentasi "'.$group.'" tidak ditemukan.');
            }
        }

        if ($publicOnly) {
            $groups = $this->filterPublicGroups($groups);

            if ($current !== null) {
                $current = $this->filterPublicGroup($current);

                if (! $current) {
                    abort(404, 'Dokumentasi kelompok "'.$group.'" belum dipublikasikan untuk umum.');
                }
            }
        }

        return view($view, [
            'groups' => $groups,
            'current' => $current,
            'versions' => $this->docs->versions(),
            'version' => $version,
            'totalEndpoint' => $publicOnly
                ? array_sum(array_map(fn (array $g) => count($g['endpoints']), $groups))
                : $this->docs->count(),
            'baseUrl' => ExternalApiPath::baseUrl($version),
            'keyHeader' => $this->keys->header(),
            'platformErrors' => PlatformErrors::all(),
            'docRoute' => $docRoute,
            'endpointStatus' => $this->endpointStatusByKey(),
            // Halaman publik hanya berisi endpoint yang is_public_docs_show-nya
            // sudah true — badge "Dokumentasi Publik/Privat" jadi tidak
            // bermakna di sana (semuanya pasti Publik), jadi disembunyikan.
            // Badge "Endpoint Aktif/Nonaktif" tetap tampil di kedua halaman.
            'showVisibilityBadge' => ! $publicOnly,
        ]);
    }

    /**
     * Status Endpoint Aktif & Dokumentasi Publik per endpoint (lihat halaman
     * Status API Eksternal), dibaca ulang di sini supaya kedua halaman
     * dokumentasi (admin dan publik, keduanya lewat partials/doc-group)
     * menampilkan badge status yang sama persis pada setiap kartu endpoint —
     * admin melihatnya untuk semua endpoint apa adanya, publik hanya melihat
     * endpoint yang memang sudah lolos filter is_public_docs_show.
     *
     * @return array<string, array{is_active:bool, is_public_docs_show:bool}>
     */
    private function endpointStatusByKey(): array
    {
        $status = [];

        foreach ($this->endpointSettings->all() as $row) {
            $status[$row['key']] = [
                'is_active' => $row['is_active'],
                'is_public_docs_show' => $row['is_public_docs_show'],
            ];
        }

        return $status;
    }

    /**
     * Buang endpoint yang saklar "Dokumentasi Publik"-nya mati dari setiap
     * kelompok, lalu buang kelompok yang jadi kosong sama sekali — kelompok
     * dengan sebagian endpoint publik tetap tampil, hanya berisi endpoint
     * yang dipublikasikan.
     *
     * @param  array<int, array{key:string, title:string, endpoints:array<int, \App\ExternalApi\Docs\ApiEndpointDoc>}>  $groups
     * @return array<int, array{key:string, title:string, endpoints:array<int, \App\ExternalApi\Docs\ApiEndpointDoc>}>
     */
    private function filterPublicGroups(array $groups): array
    {
        $filtered = [];

        foreach ($groups as $group) {
            $group = $this->filterPublicGroup($group);

            if ($group !== null) {
                $filtered[] = $group;
            }
        }

        return $filtered;
    }

    /** @return array{key:string, title:string, endpoints:array<int, \App\ExternalApi\Docs\ApiEndpointDoc>}|null */
    private function filterPublicGroup(array $group): ?array
    {
        $group['endpoints'] = array_values(array_filter(
            $group['endpoints'],
            fn ($doc) => $this->endpointSettings->isPublicDocsShown($doc->key()),
        ));

        return $group['endpoints'] !== [] ? $group : null;
    }

    /* ------------------------------------------------------------------ */
    /* Status                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * Satu baris per ENDPOINT terdokumentasi (lintas seluruh versi), masing-
     * masing dengan dua saklar: Endpoint Aktif (is_active) dan Dokumentasi
     * Publik (is_public_docs_show). Data tabelnya sendiri diambil lewat AJAX
     * (getExternalApiStatus) mengikuti pola daftar berbasis DataTables di
     * modul ini — halaman cuma menyediakan kerangka tabelnya.
     */
    public function externalApiStatus()
    {
        return view('Backoffice.ExternalApi.Status');
    }

    function getExternalApiStatus(Request $req)
    {
        return response()->json($this->endpointSettings->all());
    }

    /** Saklar apakah satu endpoint tertentu bisa dipanggil. */
    function toggleExternalApiEndpointActive(Request $req)
    {
        $key = (string) $req->get('key');

        if (! $this->endpointSettings->isKnownKey($key)) {
            return ["status" => -1, "message" => "Endpoint tidak dikenal."];
        }

        $enabled = filter_var($req->get('enabled'), FILTER_VALIDATE_BOOLEAN);
        $this->endpointSettings->setActive($key, $enabled);

        return response()->json(["status" => 1, "key" => $key, "is_active" => $enabled]);
    }

    /** Saklar apakah satu endpoint tertentu muncul di halaman dokumentasi publik (/api-docs). */
    function toggleExternalApiEndpointPublicDocs(Request $req)
    {
        $key = (string) $req->get('key');

        if (! $this->endpointSettings->isKnownKey($key)) {
            return ["status" => -1, "message" => "Endpoint tidak dikenal."];
        }

        $enabled = filter_var($req->get('enabled'), FILTER_VALIDATE_BOOLEAN);
        $this->endpointSettings->setPublicDocsShown($key, $enabled);

        return response()->json(["status" => 1, "key" => $key, "is_public_docs_show" => $enabled]);
    }

    /* ------------------------------------------------------------------ */
    /* Log                                                                 */
    /* ------------------------------------------------------------------ */

    public function externalApiLog()
    {
        return view('Backoffice.ExternalApi.Logs', [
            'applications' => (new ExternalApplication())->getExternalApplication(),
            'cleanupStrategies' => LogCleanupStrategy::all(),
            'loggingEnabled' => $this->logger->isEnabled(),
        ]);
    }

    function getExternalApiLog(Request $req)
    {
        $data = (new ExternalApiRequestLog())->getExternalApiRequestLog($req->all());
        return response()->json($data);
    }

    function getExternalApiLogSummary(Request $req)
    {
        $summary = (new ExternalApiRequestLog())->getExternalApiRequestLogSummary();
        $summary["logging_enabled"] = $this->logger->isEnabled();

        return response()->json($summary);
    }

    /** Saklar pencatatan; mematikannya membuat permintaan tidak lagi disimpan. */
    function toggleExternalApiLogging(Request $req)
    {
        $enabled = filter_var($req->get('enabled'), FILTER_VALIDATE_BOOLEAN);
        $this->logger->setEnabled($enabled);

        return response()->json(["status" => 1, "enabled" => $enabled]);
    }

    /**
     * Pembersihan log. Strategi yang tersedia diambil dari config, jadi
     * menambah cara baru tidak perlu menyentuh method ini.
     */
    function deleteExternalApiLog(Request $req)
    {
        $strategy = LogCleanupStrategy::find($req->get('strategy'));

        if (! $strategy) {
            return ["status" => -1, "message" => "Strategi pembersihan tidak dikenal."];
        }

        if ($strategy->needsDate() && ! $req->get('before')) {
            return ["status" => -1, "message" => "Tanggal & waktu batas wajib diisi."];
        }

        $boundary = $strategy->boundary($req->get('before'));
        $deleted = (new ExternalApiRequestLog())->deleteExternalApiRequestLog([
            "before" => $boundary,
        ]);

        return response()->json([
            "status" => 1,
            "deleted" => $deleted,
            "message" => "Berhasil menghapus " . $deleted . " baris log.",
        ]);
    }
}
