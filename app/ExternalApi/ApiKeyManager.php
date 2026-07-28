<?php

namespace App\ExternalApi;

use Illuminate\Support\Str;

/**
 * Pembuatan dan pemeriksaan API Key.
 *
 * Kunci berbentuk {prefix}_{label_lingkungan}_{selector}{secret}, contoh:
 *
 *     ext_live_a1b2c3d4e5f6XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
 *     └┬┘ └─┬┘ └────┬─────┘└───────────┬──────────────────┘
 *      │    │       │                   └ rahasia, hanya hash-nya disimpan
 *      │    │       └ selector, ikut disimpan apa adanya sebagai penanda cari
 *      │    └ label lingkungan, memudahkan manusia mengenali kunci
 *      └ awalan tetap dari config
 *
 * Selector dipisah dari rahasia supaya autentikasi cukup satu query terindeks
 * (`where key_prefix = ...`) alih-alih memindai seluruh tabel kunci dan
 * mencocokkan hash satu per satu.
 *
 * Hash memakai SHA-256, bukan bcrypt/argon. Kunci di sini bukan kata sandi
 * pilihan manusia melainkan 32 karakter acak dari CSPRNG, jadi tidak ada ruang
 * tebakan yang perlu diperlambat; yang dibutuhkan justru hash cepat dan
 * deterministik agar setiap permintaan API tidak membayar ongkos KDF.
 * Perbandingan tetap constant-time lewat hash_equals().
 */
class ApiKeyManager
{
    /**
     * @param  array<string, mixed>  $config  isi config('externalapi.key')
     */
    public function __construct(private readonly array $config)
    {
    }

    /**
     * Buat satu kunci baru.
     *
     * @return array{plain:string, prefix:string, hash:string, last_four:string}
     */
    public function generate(string $environment): array
    {
        $selector = $this->randomString($this->selectorLength());
        $secret = $this->randomString($this->secretLength());

        $prefix = $this->keyPrefix() . '_' . $this->environmentLabel($environment) . '_' . $selector;
        $plain = $prefix . $secret;

        return [
            'plain' => $plain,
            'prefix' => $prefix,
            'hash' => $this->hash($plain),
            'last_four' => substr($plain, -4),
        ];
    }

    public function hash(string $plainKey): string
    {
        return hash('sha256', $plainKey);
    }

    /** Perbandingan tahan timing attack. */
    public function matches(string $plainKey, string $storedHash): bool
    {
        return hash_equals($storedHash, $this->hash($plainKey));
    }

    /**
     * Ambil bagian penanda dari kunci yang dikirim klien, untuk mencari
     * barisnya di database. Mengembalikan null kalau bentuknya tidak sesuai —
     * dengan begitu kunci asal-asalan ditolak sebelum menyentuh database.
     */
    public function extractPrefix(string $plainKey): ?string
    {
        $parts = explode('_', $plainKey);
        if (count($parts) < 3) {
            return null;
        }

        [$prefix, $environment] = $parts;
        if ($prefix !== $this->keyPrefix() || $environment === '') {
            return null;
        }

        $head = $prefix . '_' . $environment . '_';
        $selector = substr($plainKey, strlen($head), $this->selectorLength());

        if (strlen($selector) < $this->selectorLength()) {
            return null;
        }

        return $head . $selector;
    }

    /** Nama header tempat klien mengirim kunci. */
    public function header(): string
    {
        return (string) ($this->config['header'] ?? 'X-API-Key');
    }

    /**
     * @return array<string, string> environment => label di dalam kunci
     */
    public function environments(): array
    {
        return (array) ($this->config['environments'] ?? ['production' => 'live']);
    }

    public function normalizeEnvironment(?string $environment): string
    {
        $environment = strtolower(trim((string) $environment));

        return array_key_exists($environment, $this->environments()) ? $environment : 'production';
    }

    public function environmentLabel(string $environment): string
    {
        return $this->environments()[$this->normalizeEnvironment($environment)] ?? 'live';
    }

    private function keyPrefix(): string
    {
        return (string) ($this->config['prefix'] ?? 'ext');
    }

    private function selectorLength(): int
    {
        return (int) ($this->config['selector_length'] ?? 12);
    }

    private function secretLength(): int
    {
        return (int) ($this->config['secret_length'] ?? 32);
    }

    /** Str::random() memakai random_bytes(), jadi aman secara kriptografis. */
    private function randomString(int $length): string
    {
        return Str::random($length);
    }
}
