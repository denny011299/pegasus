<?php

namespace App\Support;

use Illuminate\Http\Request;
use InvalidArgumentException;

class StaffSignature
{
    /**
     * Simpan e-sign dari canvas (data URI) atau upload file lama.
     * Selalu di-normalisasi ulang ke PNG terkontrol — jangan percaya data URI mentah.
     */
    public static function fromRequest(Request $request): array
    {
        if ($request->boolean('remove_signature')) {
            return ['signature_data_uri' => null];
        }

        $uri = trim((string) $request->input('signature_data_uri', ''));
        if ($uri !== '') {
            return ['signature_data_uri' => self::normalizeDataUri($uri)];
        }

        $file = $request->file('signature');
        if (! $file) {
            return [];
        }
        if (! $file->isValid() || $file->getSize() > 1024 * 1024) {
            throw new InvalidArgumentException('Tanda tangan harus berupa PNG/JPG maksimal 1 MB.');
        }
        $binary = file_get_contents($file->getRealPath());
        if ($binary === false) {
            throw new InvalidArgumentException('Gambar tanda tangan tidak dapat dibaca.');
        }

        return ['signature_data_uri' => self::normalizeBinary($binary)];
    }

    public static function normalizeDataUri(string $uri): string
    {
        if (! preg_match('#^data:image/(png|jpeg|jpg);base64,#i', $uri)) {
            throw new InvalidArgumentException('Format tanda tangan tidak valid.');
        }
        if (strlen($uri) > 1_800_000) {
            throw new InvalidArgumentException('Tanda tangan terlalu besar (maks ~1 MB).');
        }
        $raw = substr($uri, (int) strpos($uri, ',') + 1);
        $binary = base64_decode($raw, true);
        if ($binary === false || $binary === '') {
            throw new InvalidArgumentException('Tanda tangan tidak dapat dibaca.');
        }

        return self::normalizeBinary($binary);
    }

    private static function normalizeBinary(string $binary): string
    {
        if (strlen($binary) > 1024 * 1024) {
            throw new InvalidArgumentException('Tanda tangan harus berupa PNG/JPG maksimal 1 MB.');
        }
        $info = @getimagesizefromstring($binary);
        if (! $info || ! in_array($info['mime'], ['image/png', 'image/jpeg'], true)
            || $info[0] > 2000 || $info[1] > 2000) {
            throw new InvalidArgumentException('Tanda tangan harus PNG/JPG, maksimal 2000 × 2000 piksel.');
        }
        $image = @imagecreatefromstring($binary);
        if (! $image) {
            throw new InvalidArgumentException('Gambar tanda tangan tidak dapat dibaca.');
        }
        imagesavealpha($image, true);
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);
        if ($png === false || $png === '') {
            throw new InvalidArgumentException('Gambar tanda tangan tidak dapat dibaca.');
        }

        return 'data:image/png;base64,'.base64_encode($png);
    }
}
