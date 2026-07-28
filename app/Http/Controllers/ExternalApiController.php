<?php

namespace App\Http\Controllers;

use App\ExternalApi\ApiKeyManager;
use App\ExternalApi\Docs\ApiDocRegistry;
use App\ExternalApi\Docs\PlatformErrors;
use App\ExternalApi\Logging\LogCleanupStrategy;
use App\ExternalApi\Logging\RequestLogger;
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
        $versions = $this->docs->versions();
        $version = $req->get('version');

        if (! in_array($version, $versions, true)) {
            $version = $versions[0] ?? (string) config('externalapi.default_version', 'v1');
        }

        $groups = $this->docs->grouped($version);
        $current = null;

        if ($group !== null) {
            $current = $this->docs->group($group, $version);

            if (! $current) {
                abort(404, 'Kelompok dokumentasi "'.$group.'" tidak ditemukan.');
            }
        }

        return view('Backoffice.ExternalApi.Documentation', [
            'groups' => $groups,
            'current' => $current,
            'versions' => $versions,
            'version' => $version,
            'totalEndpoint' => $this->docs->count(),
            'baseUrl' => ExternalApiPath::baseUrl($version),
            'keyHeader' => $this->keys->header(),
            'platformErrors' => PlatformErrors::all(),
        ]);
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
