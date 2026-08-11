<?php

namespace App\ExternalApi\Support;

use Illuminate\Http\UploadedFile;

/**
 * Penyimpan foto shipment untuk POST /api/external/v1/shipments/shipped.
 *
 * Beda dengan App\ExternalApi\Support\PaymentPhotoStore (payments/cash, base64 saja): endpoint
 * shipment mengirim berkas sungguhan lewat multipart/form-data (photos[] sebagai file upload),
 * jadi kelas ini menerima DUA bentuk — Illuminate\Http\UploadedFile (multipart, jalur utama) atau
 * data URI base64 (kalau pemanggil memilih kirim JSON murni tanpa berkas) — dan menulis
 * keduanya ke folder yang SAMA dengan yang dipakai halaman admin Pengiriman
 * (CustomerController::insertSalesOrder(), public/issue/), disimpan sebagai JSON nama berkas di
 * sales_orders.so_img — kolom yang sama, bukan kolom baru.
 */
class ShipmentPhotoStore
{
    private const FOLDER = 'issue';

    private const ALLOWED = ['png' => 'png', 'jpg' => 'jpg', 'jpeg' => 'jpg'];

    /** @var array<int, string> path absolut berkas yang ditulis permintaan ini */
    private array $written = [];

    /**
     * @param  array<int, UploadedFile|string>  $photos
     * @return array<int, string>
     *
     * @throws \InvalidArgumentException bila ada foto yang formatnya tidak sah
     */
    public function store(array $photos): array
    {
        $directory = public_path(self::FOLDER);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $names = [];
        foreach ($photos as $index => $photo) {
            $names[] = $photo instanceof UploadedFile
                ? $this->writeUploaded($photo, $directory, $index)
                : $this->writeBase64((string) $photo, $directory, $index);
        }

        return $names;
    }

    /** Dipanggil saat transaksi database dibatalkan — berkas tidak ikut ter-rollback. */
    public function cleanup(): void
    {
        foreach ($this->written as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $this->written = [];
    }

    private function writeUploaded(UploadedFile $file, string $directory, int $index): string
    {
        if (! $file->isValid()) {
            throw new \InvalidArgumentException('Foto ke-'.($index + 1).' gagal diunggah.');
        }

        // Validasi mimes:png,jpg,jpeg sudah dijalankan lewat $request->validate() sebelum
        // sampai sini (lihat ShipmentController::validateShippedPayload()) — extension() di sini
        // membaca isi berkas sungguhan (bukan sekadar percaya nama aslinya).
        $extension = self::ALLOWED[strtolower($file->extension() ?: '')] ?? null;
        if ($extension === null) {
            throw new \InvalidArgumentException(
                'Foto ke-'.($index + 1).' bukan gambar PNG atau JPEG yang sah.'
            );
        }

        $name = 'photo_'.uniqid().'.'.$extension;
        $file->move($directory, $name);
        $this->written[] = $directory.'/'.$name;

        return $name;
    }

    private function writeBase64(string $photo, string $directory, int $index): string
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $photo, $match)) {
            $declared = strtolower($match[1]);

            if (! isset(self::ALLOWED[$declared])) {
                throw new \InvalidArgumentException(
                    'Foto ke-'.($index + 1).' memakai format "'.$declared.'". Hanya PNG dan JPEG yang diterima.'
                );
            }

            $photo = preg_replace('/^data:image\/\w+;base64,/', '', $photo);
        }

        $binary = base64_decode($photo, true);
        if ($binary === false || $binary === '') {
            throw new \InvalidArgumentException('Foto ke-'.($index + 1).' bukan base64 yang sah.');
        }

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

    /** URL publik satu nama berkas, untuk ditampilkan di respons. */
    public static function url(string $fileName): string
    {
        return asset(self::FOLDER.'/'.$fileName);
    }
}
