<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardChangeLog extends Model
{
    protected $table = 'dashboard_change_logs';

    protected $fillable = [
        'module_key',
        'activity_type',
        'module_label',
        'reference',
        'what_changed',
        'summary',
        'url',
        'url_label',
        'created_by',
        'meta',
        'duration_seconds',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * Tutup baris 'open' terakhir milik SESI LOGIN ini (modul manapun) yang belum punya
     * durasi, dengan durasi = jarak waktu ke $endedAt. Dipakai dari dua tempat:
     * LogDashboardActivity (estimasi pasif -- ditutup oleh pembukaan menu BERIKUTNYA) dan
     * tab-close beacon (sinyal aktif -- ditutup oleh browser saat tab/halaman benar-benar
     * ditinggalkan).
     *
     * $sessionId WAJIB diisi (Laravel session ID milik request yang sedang berjalan) supaya
     * query di-scope ke sesi login itu saja, bukan ke SEMUA baris 'open' milik $staffId.
     * Tanpa scoping ini, staf yang login bersamaan di 2 device/browser (akun yang sama, dua
     * sesi login berbeda) saling menutup baris 'open' satu sama lain -- device A tak sengaja
     * ikut "ditutup" begitu device B membuka menu apa pun, padahal tab A masih benar-benar
     * terbuka. Null hanya untuk baris lama (pra-fix) yang belum punya session_id di meta-nya.
     *
     * $maxSeconds membatasi durasi yang menyesatkan (tab lama ditinggal idle tanpa navigasi
     * lagi); default 4 jam sama seperti estimasi pasif. Beacon boleh melewati batas ini
     * (null) karena sinyalnya nyata, bukan estimasi.
     *
     * Idempotent lewat whereNull('duration_seconds') di query pemanggil -- baris yang sudah
     * ditutup (oleh salah satu jalur) tidak akan tertimpa oleh jalur lainnya.
     */
    public static function closeOpenSession(int $staffId, $endedAt, ?int $maxSeconds = 4 * 3600, ?string $sessionId = null): ?self
    {
        $previous = self::where('created_by', $staffId)
            ->where('activity_type', 'open')
            ->whereNull('duration_seconds')
            ->when($sessionId !== null, fn ($q) => $q->where('meta->session_id', $sessionId))
            ->orderByDesc('created_at')
            ->first();

        if (!$previous) {
            return null;
        }

        // Explicit $absolute=true + cast to int: Carbon 3's diffInSeconds() defaults to a
        // signed, sub-second-precision float (negative here since $previous is in the past).
        $seconds = (int) $endedAt->diffInSeconds($previous->created_at, true);
        if ($maxSeconds !== null && $seconds > $maxSeconds) {
            return null;
        }

        $previous->duration_seconds = $seconds;
        $previous->save();

        return $previous;
    }

    /**
     * Sama seperti closeOpenSession(), tapi menutup baris 'open' yang SPESIFIK lewat
     * client_token (tertanam di meta saat baris dibuat -- lihat
     * LogDashboardActivity::logOpen()), bukan "baris open terakhir milik staf ini".
     *
     * Dipakai oleh DashboardActivityController::closeSession() (beacon tab-close) supaya
     * tidak salah tutup baris LAIN yang keburu dibuat oleh navigasi berikutnya sebelum beacon
     * sampai ke server -- lihat catatan race condition di logOpen().
     */
    public static function closeOpenSessionByToken(int $staffId, string $token, $endedAt): ?self
    {
        $row = self::where('created_by', $staffId)
            ->where('activity_type', 'open')
            ->whereNull('duration_seconds')
            ->where('meta->client_token', $token)
            ->first();

        if (!$row) {
            return null;
        }

        $seconds = (int) $endedAt->diffInSeconds($row->created_at, true);
        $row->duration_seconds = $seconds;
        $row->save();

        return $row;
    }
}
