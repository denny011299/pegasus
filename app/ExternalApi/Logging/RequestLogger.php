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
 */
class RequestLogger
{
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
