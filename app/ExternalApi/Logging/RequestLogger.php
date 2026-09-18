<?php

namespace App\ExternalApi\Logging;

use App\ExternalApi\Auth\ExternalApiAuthenticator;
use App\ExternalApi\Support\ExternalApiPath;
use App\ExternalApi\Support\SettingStore;
use App\Models\ExternalApiRequestLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Penulis log permintaan External API.
 *
 * Satu-satunya tempat yang tahu bagaimana log ditulis. Middleware hanya
 * memanggil write(); kalau nanti volume permintaan menuntut penulisan lewat
 * queue atau ke penyimpanan lain, cukup isi kelas ini yang diganti.
 *
 * Pemanggilnya adalah terminable middleware, jadi kode di sini berjalan
 * SESUDAH respons dikirim ke klien dan tidak menambah waktu tunggu yang
 * dirasakan pemanggil API.
 *
 * Kegagalan menulis log tidak pernah dibiarkan menjatuhkan permintaan — log
 * lalu lintas bukan bagian dari kontrak API, jadi masalah di sini ditelan dan
 * diteruskan ke log aplikasi biasa.
 *
 * request_body/response_body HANYA diisi saat app()->environment('local') —
 * alat bantu debug integrasi PMO (mis. melihat pesan validasi lengkap di
 * balik satu 422 tanpa buka storage/logs), bukan sesuatu yang aman disimpan
 * apa adanya di lingkungan lain (bisa memuat data sensitif pemanggil, dan
 * baris log ini tidak dienkripsi/redaksi apa pun).
 */
class RequestLogger
{
    /** Dipotong supaya satu permintaan raksasa (mis. foto base64) tidak membengkakkan tabel log. */
    private const MAX_BODY_LENGTH = 20000;

    /** Cache per-request agar pembacaan setting tidak berulang. */
    private ?bool $enabled = null;

    public function __construct(private readonly SettingStore $settings)
    {
    }

    public function write(Request $request, Response $response, float $startedAt): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        try {
            $application = ExternalApiAuthenticator::application($request);
            $key = ExternalApiAuthenticator::key($request);

            $log = new ExternalApiRequestLog();
            $log->external_application_id = $application?->external_application_id;
            $log->external_api_key_id = $key?->external_api_key_id;
            $log->application_name = $application?->application_name;
            $log->key_name = $key?->key_name;
            $log->method = $request->method();
            $log->endpoint = $this->endpoint($request);
            $log->route_name = $request->route()?->getName();
            $log->api_version = $this->version($request);
            $log->status_code = $response->getStatusCode();
            $log->duration_ms = (int) round((microtime(true) - $startedAt) * 1000);
            $log->ip_address = $request->ip();
            $log->user_agent = $this->userAgent($request);
            if (app()->environment('local')) {
                $log->request_body = $this->requestBody($request);
                $log->response_body = $this->truncate($response->getContent() ?: null);
            }
            $log->requested_at = now();
            $log->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Saklar pencatatan. Disimpan di tabel `settings` supaya bisa diubah admin
     * lewat UI; nilai di config hanya dipakai saat barisnya belum pernah ada.
     */
    public function isEnabled(): bool
    {
        if ($this->enabled !== null) {
            return $this->enabled;
        }

        return $this->enabled = $this->settings->getBool(
            $this->settingKey(),
            (bool) config('externalapi.logging.enabled_by_default', true),
        );
    }

    public function setEnabled(bool $enabled): void
    {
        $this->settings->putBool($this->settingKey(), $enabled);
        $this->enabled = $enabled;
    }

    private function settingKey(): string
    {
        return (string) config('externalapi.logging.setting_key', 'external_api_logging_enabled');
    }

    /** Path lengkap beserta query string, dipotong agar muat di kolom. */
    private function endpoint(Request $request): string
    {
        $path = '/' . ltrim($request->path(), '/');
        $query = $request->getQueryString();

        if ($query) {
            $path .= '?' . $query;
        }

        return mb_substr($path, 0, 255);
    }

    /**
     * getContent() bekerja untuk permintaan JSON biasa, tapi SELALU kosong untuk
     * multipart/form-data (mis. photos[] pada POST /shipments/shipped) — PHP sudah
     * menguraikan isinya duluan ke $_POST/$_FILES sebelum sampai ke sini. Untuk kasus itu,
     * rekonstruksi ringkasannya dari input yang sudah di-parse Laravel, dengan berkas upload
     * diganti nama aslinya saja (bukan isi bytenya, supaya tidak raksasa/berulang di log).
     */
    private function requestBody(Request $request): ?string
    {
        $raw = $request->getContent();
        if ($raw !== '') {
            return $this->truncate($raw);
        }

        if (! $request->files->count()) {
            return null;
        }

        $data = $request->except(array_keys($request->files->all()));
        foreach ($request->files->all() as $key => $files) {
            $data[$key] = collect(is_array($files) ? $files : [$files])
                ->map(fn ($file) => $file?->getClientOriginalName())
                ->all();
        }

        return $this->truncate(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null);
    }

    private function truncate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (mb_strlen($value) <= self::MAX_BODY_LENGTH) {
            return $value;
        }

        return mb_substr($value, 0, self::MAX_BODY_LENGTH).' … (dipotong, lebih dari '.self::MAX_BODY_LENGTH.' karakter)';
    }

    private function userAgent(Request $request): ?string
    {
        $agent = $request->userAgent();
        if (!$agent) {
            return null;
        }

        return mb_substr($agent, 0, (int) config('externalapi.logging.max_user_agent_length', 255));
    }

    /**
     * Versi diambil dari segmen tepat sesudah awalan External API, jadi setiap
     * versi baru ikut tercatat tanpa perlu menyentuh kelas ini. Posisinya
     * dihitung dari panjang awalan, bukan angka tetap, supaya tetap benar bila
     * config('externalapi.base_path') diubah.
     */
    private function version(Request $request): ?string
    {
        return ExternalApiPath::versionFrom($request);
    }
}
