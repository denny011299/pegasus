<?php

namespace App\ExternalApi\Support;

use App\ExternalApi\Docs\ApiDocRegistry;
use App\ExternalApi\Docs\ApiEndpointDoc;
use App\Models\ExternalApiEndpointStatus;

/**
 * Saklar per ENDPOINT (bukan per versi) yang dikelola dari halaman Status API
 * Eksternal — satu baris untuk setiap kelas ApiEndpointDoc terdaftar di
 * config('externalapi.docs'), lintas semua versi sekaligus. Endpoint baru
 * otomatis muncul jadi baris baru begitu didokumentasikan (lihat skill
 * external-api-endpoint), tidak perlu menyentuh kelas ini.
 *
 * Disimpan di tabel khusus `external_api_endpoints`
 * (App\Models\ExternalApiEndpointStatus), BUKAN tabel `settings` generik —
 * beda dari saklar pencatatan RequestLogger yang cukup satu baris global.
 * Baris di sini bertambah seiring endpoint bertambah (sudah puluhan, dan
 * akan terus tumbuh), dan perlu kolom typed (is_active,
 * is_public_docs_show) dengan endpoint_key sebagai identitas, bukan
 * pasangan key/value string generik.
 *
 * Identitas baris memakai ApiEndpointDoc::key() — sudah dijamin unik lintas
 * versi karena ApiDocRegistry::all() sendiri mengindeks seluruh dokumentasi
 * memakai key yang sama sebagai kunci array; dua endpoint beda versi TIDAK
 * BOLEH memakai key yang sama atau salah satunya akan tertimpa di registry.
 *
 * Baris HANYA dibuat saat salah satu saklar endpoint tersebut pertama kali
 * diubah dari nilai bawaan — endpoint yang belum pernah disentuh tidak
 * punya baris sama sekali dan dianggap:
 *
 * - is_active           : AKTIF. Supaya endpoint baru langsung hidup begitu
 *                          didaftarkan, sama seperti sebelum fitur ini ada.
 * - is_public_docs_show : NONAKTIF. Endpoint baru harus sengaja
 *                          dipublikasikan admin. Halaman dokumentasi admin
 *                          (menu "Dokumentasi API Eksternal") tidak
 *                          terpengaruh saklar ini sama sekali, karena itu
 *                          bukan halaman publik.
 */
class ApiEndpointSettings
{
    public function __construct(private readonly ApiDocRegistry $docs)
    {
    }

    public function isActive(string $key): bool
    {
        $row = $this->find($key);

        return $row ? (bool) $row->is_active : true;
    }

    public function setActive(string $key, bool $value): void
    {
        $this->save($key, 'is_active', $value);
    }

    public function isPublicDocsShown(string $key): bool
    {
        $row = $this->find($key);

        return $row ? (bool) $row->is_public_docs_show : false;
    }

    public function setPublicDocsShown(string $key, bool $value): void
    {
        $this->save($key, 'is_public_docs_show', $value);
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
     * tampilannya stabil dan mudah dipindai. Satu query untuk seluruh baris
     * status (bukan satu per endpoint) supaya daftar puluhan endpoint tidak
     * memicu puluhan query terpisah.
     *
     * @return array<int, array{key:string, version:string, method:string, path:string, full_path:string, title:string, group:string, group_title:string, is_active:bool, is_public_docs_show:bool}>
     */
    public function all(): array
    {
        $groupTitles = (array) config('externalapi.doc_groups', []);
        $statuses = ExternalApiEndpointStatus::query()->get()->keyBy('endpoint_key');
        $rows = [];

        foreach ($this->docs->all() as $doc) {
            $status = $statuses->get($doc->key());

            $rows[] = [
                'key' => $doc->key(),
                'version' => $doc->version(),
                'method' => strtoupper($doc->method()),
                'path' => $doc->path(),
                'full_path' => $doc->fullPath(),
                'title' => $doc->title(),
                'group' => $doc->group(),
                'group_title' => $groupTitles[$doc->group()] ?? ucfirst($doc->group()),
                'is_active' => $status ? (bool) $status->is_active : true,
                'is_public_docs_show' => $status ? (bool) $status->is_public_docs_show : false,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            return [$a['version'], $a['group_title'], $a['title']]
                <=> [$b['version'], $b['group_title'], $b['title']];
        });

        return $rows;
    }

    private function find(string $key): ?ExternalApiEndpointStatus
    {
        return ExternalApiEndpointStatus::where('endpoint_key', '=', $key)->first();
    }

    /**
     * Upsert manual, bukan updateOrCreate()/fill() — model ini sengaja tanpa
     * $fillable, mengikuti konvensi model lain di app (assign properti satu
     * per satu, bukan mass-assignment).
     */
    private function save(string $key, string $column, bool $value): void
    {
        $row = $this->find($key);

        if (! $row) {
            $row = new ExternalApiEndpointStatus();
            $row->endpoint_key = $key;
            $row->is_active = true;
            $row->is_public_docs_show = false;
        }

        $row->{$column} = $value;
        $row->save();
    }
}
