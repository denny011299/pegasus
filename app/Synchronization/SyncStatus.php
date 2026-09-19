<?php

namespace App\Synchronization;

/**
 * Status eksekusi satu langkah sinkronisasi.
 */
final class SyncStatus
{
    public const NOT_EXECUTED = 'not_executed';
    public const RUNNING = 'running';
    public const SUCCESS = 'success';
    /**
     * Sebagian baris berhasil, sebagian gagal (GitHub #184) — beda dari FAILED:
     * PARTIAL tetap dianggap memenuhi prasyarat langkah berikutnya (lihat
     * PrerequisiteChecker), supaya operator bisa memilih lanjut memakai baris
     * yang berhasil tanpa menunggu seluruh baris yang gagal diperbaiki dulu.
     * FAILED tetap dipakai saat TIDAK ADA satu pun baris yang berhasil.
     */
    public const PARTIAL = 'partial';
    public const FAILED = 'failed';

    /** @var array<string, string> */
    private const LABELS = [
        self::NOT_EXECUTED => 'Belum Dijalankan',
        self::RUNNING => 'Sedang Berjalan',
        self::SUCCESS => 'Berhasil',
        self::PARTIAL => 'Sebagian Berhasil',
        self::FAILED => 'Gagal',
    ];

    /** @var array<string, string> */
    private const BADGES = [
        self::NOT_EXECUTED => 'badge-soft-secondary',
        self::RUNNING => 'badge-soft-info',
        self::SUCCESS => 'badge-soft-success',
        self::PARTIAL => 'badge-soft-warning',
        self::FAILED => 'badge-soft-danger',
    ];

    public static function label(string $status): string
    {
        return self::LABELS[$status] ?? self::LABELS[self::NOT_EXECUTED];
    }

    public static function badgeClass(string $status): string
    {
        return self::BADGES[$status] ?? self::BADGES[self::NOT_EXECUTED];
    }
}
