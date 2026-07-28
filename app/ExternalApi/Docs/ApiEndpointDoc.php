<?php

namespace App\ExternalApi\Docs;

use App\ExternalApi\Support\ExternalApiPath;

/**
 * Dokumentasi satu endpoint External API.
 *
 * Satu endpoint = satu kelas turunan kelas ini, didaftarkan di
 * config('externalapi.docs'). Halaman Dokumentasi API Eksternal dibangun
 * seluruhnya dari daftar itu, jadi tidak ada halaman HTML yang perlu ditulis
 * atau digandakan setiap kali API baru dibuat.
 *
 * Pola ini dipilih (bukan Markdown atau file konfigurasi terpisah) karena
 * dokumentasinya jadi ikut satu repositori dan satu review dengan kode
 * endpoint-nya, sehingga lebih sulit tertinggal saat endpoint diubah.
 *
 * Yang wajib diisi hanya identitas dan bentuk permintaan/respons. Selebihnya
 * punya nilai bawaan yang masuk akal supaya kelas dokumentasi tetap ringkas.
 */
abstract class ApiEndpointDoc
{
    /** Identitas unik endpoint, dipakai sebagai anchor di halaman dokumentasi. */
    abstract public function key(): string;

    /** Judul singkat, contoh: "Daftar Produk". */
    abstract public function title(): string;

    /** Metode HTTP, contoh: "GET". */
    abstract public function method(): string;

    /** Path relatif terhadap Base URL versi, contoh: "/products". */
    abstract public function path(): string;

    /** Penjelasan satu-dua kalimat tentang kegunaan endpoint. */
    abstract public function description(): string;

    /**
     * Kelompok endpoint. Harus salah satu kunci di
     * config('externalapi.doc_groups') agar urutannya terkendali; kelompok di
     * luar daftar itu tetap tampil, diletakkan di bagian bawah.
     */
    abstract public function group(): string;

    /** Versi API tempat endpoint ini hidup. */
    public function version(): string
    {
        return (string) config('externalapi.default_version', 'v1');
    }

    /**
     * Endpoint ini butuh API Key atau tidak. Nyaris selalu true — dibuat bisa
     * diubah hanya untuk endpoint publik semacam health check.
     */
    public function requiresAuthentication(): bool
    {
        return true;
    }

    /**
     * Parameter query string.
     *
     * @return array<int, array{name:string, type?:string, required?:bool, description?:string}>
     */
    public function queryParameters(): array
    {
        return [];
    }

    /**
     * Parameter di dalam path, contoh {id}.
     *
     * @return array<int, array{name:string, type?:string, required?:bool, description?:string}>
     */
    public function pathParameters(): array
    {
        return [];
    }

    /**
     * Field pada body permintaan.
     *
     * @return array<int, array{name:string, type?:string, required?:bool, description?:string}>
     */
    public function bodyParameters(): array
    {
        return [];
    }

    /**
     * Contoh body permintaan. Null untuk endpoint tanpa body (GET/DELETE).
     *
     * @return array<string, mixed>|null
     */
    public function requestExample(): ?array
    {
        return null;
    }

    /**
     * Contoh respons berhasil, mengikuti bentuk baku App\ExternalApi\Http\ApiResponse.
     *
     * @return array<string, mixed>
     */
    abstract public function responseExample(): array;

    /**
     * Kemungkinan error khas endpoint ini. Error umum lapisan platform
     * (kunci hilang / tidak valid / dicabut / kedaluwarsa / aplikasi nonaktif)
     * TIDAK perlu ditulis ulang di sini — semuanya sudah ditampilkan sekali di
     * bagian "Respons Error" halaman dokumentasi.
     *
     * @return array<int, array{code:string, http_status:int, message:string}>
     */
    public function errors(): array
    {
        return [];
    }

    /** Catatan tambahan, batasan, atau peringatan pemakaian. */
    public function notes(): array
    {
        return [];
    }

    /** Path lengkap termasuk awalan versi, dipakai untuk ditampilkan & disalin. */
    public function fullPath(): string
    {
        return ExternalApiPath::endpoint($this->version(), $this->path());
    }

    /** Warna badge metode HTTP, mengikuti kelas badge tema admin. */
    public function methodBadgeClass(): string
    {
        return match (strtoupper($this->method())) {
            'POST' => 'badge-soft-success',
            'PUT', 'PATCH' => 'badge-soft-warning',
            'DELETE' => 'badge-soft-danger',
            default => 'badge-soft-info',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key(),
            'title' => $this->title(),
            'method' => strtoupper($this->method()),
            'path' => $this->path(),
            'full_path' => $this->fullPath(),
            'description' => $this->description(),
            'group' => $this->group(),
            'version' => $this->version(),
            'requires_authentication' => $this->requiresAuthentication(),
            'query_parameters' => $this->queryParameters(),
            'path_parameters' => $this->pathParameters(),
            'body_parameters' => $this->bodyParameters(),
            'request_example' => $this->requestExample(),
            'response_example' => $this->responseExample(),
            'errors' => $this->errors(),
            'notes' => $this->notes(),
        ];
    }
}
