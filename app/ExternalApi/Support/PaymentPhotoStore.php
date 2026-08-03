<?php

namespace App\ExternalApi\Support;

/**
 * Penyimpan foto bukti pembayaran untuk External API.
 *
 * Mengikuti cara yang sudah dipakai halaman Kas Operasional: foto dikirim
 * sebagai data URI base64, ditulis sebagai berkas ke public/kas_admin/{jenis}/,
 * lalu nama berkasnya disimpan sebagai JSON array di kolom cr_img / cs_img.
 * Bukan media library, bukan path absolut — hanya nama berkas.
 *
 * Dua hal yang diperketat dibanding halaman admin, keduanya karena di sini
 * masukan datang dari sistem luar yang tidak bisa dipercaya begitu saja:
 *
 * 1. Format diperiksa. Kode admin menulis apa pun sebagai .png; di sini hanya
 *    PNG dan JPEG yang diterima, dan berkasnya memakai ekstensi yang benar.
 * 2. Berkas yang sudah terlanjur ditulis bisa dibatalkan lewat cleanup(),
 *    dipakai saat transaksi database gagal — supaya tidak meninggalkan berkas
 *    yatim yang tidak dirujuk baris mana pun.
 */
class PaymentPhotoStore
{
    /** Jenis kas => folder tujuan, mengikuti struktur yang sudah ada. */
    private const FOLDERS = [
        'armada' => 'kas_admin/armada',
        'sales' => 'kas_admin/sales',
    ];

    private const ALLOWED = [
        'png' => 'png',
        'jpg' => 'jpg',
        'jpeg' => 'jpg',
    ];

    /** @var array<int, string> path absolut berkas yang ditulis permintaan ini */
    private array $written = [];

    /**
     * Tulis seluruh foto, kembalikan daftar nama berkasnya.
     *
     * @param  array<int, string>  $photos  data URI base64
     * @return array<int, string>
     *
     * @throws \InvalidArgumentException bila ada foto yang formatnya tidak sah
     */
    public function store(array $photos, string $kind): array
    {
        $folder = self::FOLDERS[$kind] ?? null;

        if ($folder === null) {
            throw new \InvalidArgumentException('Jenis kas "'.$kind.'" tidak dikenal.');
        }

        $directory = public_path($folder);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $names = [];

        foreach ($photos as $index => $photo) {
            $names[] = $this->writeOne((string) $photo, $directory, $folder, $index);
        }

        return $names;
    }

    /**
     * Hapus berkas yang ditulis permintaan ini.
     *
     * Dipanggil saat transaksi database dibatalkan: berkas tidak ikut
     * ter-rollback oleh database, jadi harus dibereskan sendiri.
     */
    public function cleanup(): void
    {
        foreach ($this->written as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->written = [];
    }

    private function writeOne(string $photo, string $directory, string $folder, int $index): string
    {
        $extension = 'png';

        // Data URI: ambil formatnya dari prefix bila ada.
        if (preg_match('/^data:image\/(\w+);base64,/', $photo, $match)) {
            $declared = strtolower($match[1]);

            if (! isset(self::ALLOWED[$declared])) {
                throw new \InvalidArgumentException(
                    'Foto ke-'.($index + 1).' memakai format "'.$declared.'". Hanya PNG dan JPEG yang diterima.'
                );
            }

            $extension = self::ALLOWED[$declared];
            $photo = preg_replace('/^data:image\/\w+;base64,/', '', $photo);
        }

        $binary = base64_decode((string) $photo, true);

        if ($binary === false || $binary === '') {
            throw new \InvalidArgumentException('Foto ke-'.($index + 1).' bukan base64 yang sah.');
        }

        // Periksa isi berkasnya, bukan sekadar percaya prefix yang dikirim.
        $info = @getimagesizefromstring($binary);

        if ($info === false || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            throw new \InvalidArgumentException('Foto ke-'.($index + 1).' bukan gambar PNG atau JPEG yang sah.');
        }

        $extension = $info[2] === IMAGETYPE_JPEG ? 'jpg' : 'png';
        $name = 'photo_'.uniqid().'.'.$extension;
        $path = $directory.'/'.$name;

        if (file_put_contents($path, $binary) === false) {
            throw new \RuntimeException('Gagal menyimpan foto ke-'.($index + 1).'.');
        }

        $this->written[] = $path;

        return $name;
    }

    /** URL publik satu nama berkas, untuk ditampilkan di respons GET. */
    public static function url(string $fileName, string $kind): string
    {
        $folder = self::FOLDERS[$kind] ?? self::FOLDERS['armada'];

        return asset($folder.'/'.$fileName);
    }
}
