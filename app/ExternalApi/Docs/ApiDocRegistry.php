<?php

namespace App\ExternalApi\Docs;

/**
 * Kumpulan dokumentasi endpoint yang terdaftar di config/externalapi.php.
 *
 * Bentuknya sengaja dibuat sama dengan App\Synchronization\SyncFlowRegistry
 * supaya pola "daftar kelas di config, registry yang merakit" konsisten di
 * seluruh modul Integrasi.
 */
class ApiDocRegistry
{
    /** @var array<string, ApiEndpointDoc>|null */
    private ?array $docs = null;

    /**
     * @param  array<int, class-string<ApiEndpointDoc>>  $docClasses
     */
    public function __construct(private readonly array $docClasses)
    {
    }

    /**
     * @return array<string, ApiEndpointDoc>
     */
    public function all(): array
    {
        if ($this->docs !== null) {
            return $this->docs;
        }

        $docs = [];
        foreach ($this->docClasses as $class) {
            $doc = app($class);

            if (! $doc instanceof ApiEndpointDoc) {
                throw new \LogicException('Dokumentasi '.$class.' harus merupakan turunan '.ApiEndpointDoc::class.'.');
            }

            $docs[$doc->key()] = $doc;
        }

        return $this->docs = $docs;
    }

    public function find(string $key): ?ApiEndpointDoc
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Endpoint yang dikelompokkan untuk daftar isi.
     *
     * Urutan kelompok mengikuti config('externalapi.doc_groups'). Endpoint
     * dengan kelompok yang belum terdaftar di sana tetap ditampilkan, disusun
     * di bagian bawah memakai kunci kelompoknya sebagai judul — dokumentasi
     * tidak boleh hilang hanya karena kelompoknya lupa didaftarkan.
     *
     * @param  string|null  $version  batasi ke satu versi API saja
     * @return array<int, array{key:string, title:string, endpoints:array<int, ApiEndpointDoc>}>
     */
    public function grouped(?string $version = null): array
    {
        $known = (array) config('externalapi.doc_groups', []);
        $buckets = [];

        foreach ($this->all() as $doc) {
            if ($version !== null && $doc->version() !== $version) {
                continue;
            }

            $buckets[$doc->group()][] = $doc;
        }

        $groups = [];

        foreach ($known as $key => $title) {
            if (! empty($buckets[$key])) {
                $groups[] = ['key' => $key, 'title' => $title, 'endpoints' => $buckets[$key]];
                unset($buckets[$key]);
            }
        }

        foreach ($buckets as $key => $endpoints) {
            $groups[] = ['key' => $key, 'title' => ucfirst((string) $key), 'endpoints' => $endpoints];
        }

        return $groups;
    }

    /**
     * Satu kelompok berdasarkan kuncinya, atau null bila tidak ada.
     *
     * Dipakai halaman dokumentasi per modul: tiap kelompok punya halamannya
     * sendiri, jadi kelompok baru langsung mendapat halaman tanpa perlu
     * menambah rute atau view apa pun.
     *
     * @return array{key:string, title:string, endpoints:array<int, ApiEndpointDoc>}|null
     */
    public function group(string $key, ?string $version = null): ?array
    {
        foreach ($this->grouped($version) as $group) {
            if ($group['key'] === $key) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Versi API yang benar-benar punya endpoint terdokumentasi.
     *
     * @return array<int, string>
     */
    public function versions(): array
    {
        $declared = (array) config('externalapi.versions', []);
        $used = [];

        foreach ($this->all() as $doc) {
            $used[$doc->version()] = true;
        }

        $versions = array_values(array_filter($declared, static fn ($v) => isset($used[$v])));

        // Sebelum ada endpoint bisnis sama sekali, halaman dokumentasi tetap
        // perlu menampilkan satu versi supaya bagian Base URL & Autentikasi
        // tidak kosong.
        return $versions !== [] ? $versions : array_slice($declared, 0, 1);
    }

    public function count(): int
    {
        return count($this->all());
    }
}
