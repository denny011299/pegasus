<?php

namespace App\ExternalApi\Support;

use App\ExternalApi\Docs\ApiDocRegistry;
use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Saklar per ENDPOINT (bukan per versi) yang dikelola dari halaman Status API
 * Eksternal — satu baris untuk setiap kelas ApiEndpointDoc terdaftar di
 * config('externalapi.docs'), lintas semua versi sekaligus. Endpoint baru
 * otomatis muncul jadi baris baru begitu didokumentasikan (lihat skill
 * external-api-endpoint), tidak perlu menyentuh kelas ini.
 *
 * Identitas baris memakai ApiEndpointDoc::key() — sudah dijamin unik lintas
 * versi karena ApiDocRegistry::all() sendiri mengindeks seluruh dokumentasi
 * memakai key yang sama sebagai kunci array; dua endpoint beda versi TIDAK
 * BOLEH memakai key yang sama atau salah satunya akan tertimpa di registry.
 *
 * Dua saklar per baris, disimpan lewat SettingStore (tabel `settings`), pola
 * sama dengan RequestLogger:
 *
 * - is_active           : apakah endpoint ini bisa dipanggil. Default AKTIF
 *                          supaya endpoint baru langsung hidup begitu
 *                          didaftarkan, sama seperti sebelum fitur ini ada.
 * - is_public_docs_show : apakah endpoint ini muncul di halaman dokumentasi
 *                          publik (/api-docs, tanpa login). Default NONAKTIF
 *                          — endpoint baru harus sengaja dipublikasikan
 *                          admin. Halaman dokumentasi admin (menu
 *                          "Dokumentasi API Eksternal") tidak terpengaruh
 *                          saklar ini sama sekali, karena itu bukan halaman
 *                          publik.
 */
class ApiEndpointSettings
{
    private const ACTIVE_PREFIX = 'external_api_endpoint_active_';
    private const PUBLIC_DOCS_PREFIX = 'external_api_endpoint_public_docs_';

    public function __construct(
        private readonly SettingStore $settings,
        private readonly ApiDocRegistry $docs,
    ) {
    }

    public function isActive(string $key): bool
    {
        return $this->settings->getBool(self::ACTIVE_PREFIX.$key, true);
    }

    public function setActive(string $key, bool $value): void
    {
        $this->settings->putBool(self::ACTIVE_PREFIX.$key, $value);
    }

    public function isPublicDocsShown(string $key): bool
    {
        return $this->settings->getBool(self::PUBLIC_DOCS_PREFIX.$key, false);
    }

    public function setPublicDocsShown(string $key, bool $value): void
    {
        $this->settings->putBool(self::PUBLIC_DOCS_PREFIX.$key, $value);
    }

    public function isKnownKey(string $key): bool
    {
        return $this->docs->find($key) !== null;
    }

    /**
     * Cari dokumentasi endpoint yang cocok dengan satu permintaan yang
     * sedang berjalan (versi + metode + path relatif terhadap versi),
     * dipakai AuthenticateExternalApi untuk tahu baris mana yang harus
     * dicek. Path dibandingkan apa adanya (termasuk placeholder {param})
     * karena rute asli (routes/external-api/{versi}.php) dan path di kelas
     * dokumentasi wajib sama persis — itulah gunanya dokumentasi tetap
     * akurat terhadap kode.
     *
     * Null berarti rute ini belum/tidak terdokumentasi — pemanggil
     * memperlakukannya sebagai aktif (fail-open), bukan alasan untuk
     * memblokir endpoint yang belum sempat dituliskan dokumentasinya.
     */
    public function findDocForRequest(string $version, string $method, string $path): ?ApiEndpointDoc
    {
        $method = strtoupper($method);
        $path = '/'.trim($path, '/');

        foreach ($this->docs->all() as $doc) {
            if ($doc->version() !== $version) {
                continue;
            }

            if (strtoupper($doc->method()) !== $method) {
                continue;
            }

            if ('/'.trim($doc->path(), '/') !== $path) {
                continue;
            }

            return $doc;
        }

        return null;
    }

    /**
     * Satu baris per endpoint terdokumentasi — sumber data langsung untuk
     * halaman Status API Eksternal. Diurutkan versi → kelompok → judul supaya
     * tampilannya stabil dan mudah dipindai.
     *
     * @return array<int, array{key:string, version:string, method:string, path:string, full_path:string, title:string, group:string, group_title:string, is_active:bool, is_public_docs_show:bool}>
     */
    public function all(): array
    {
        $groupTitles = (array) config('externalapi.doc_groups', []);
        $rows = [];

        foreach ($this->docs->all() as $doc) {
            $rows[] = [
                'key' => $doc->key(),
                'version' => $doc->version(),
                'method' => strtoupper($doc->method()),
                'path' => $doc->path(),
                'full_path' => $doc->fullPath(),
                'title' => $doc->title(),
                'group' => $doc->group(),
                'group_title' => $groupTitles[$doc->group()] ?? ucfirst($doc->group()),
                'is_active' => $this->isActive($doc->key()),
                'is_public_docs_show' => $this->isPublicDocsShown($doc->key()),
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            return [$a['version'], $a['group_title'], $a['title']]
                <=> [$b['version'], $b['group_title'], $b['title']];
        });

        return $rows;
    }
}
