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
    public const FAILED = 'failed';

    /** @var array<string, string> */
    private const LABELS = [
        self::NOT_EXECUTED => 'Belum Dijalankan',
        self::RUNNING => 'Sedang Berjalan',
        self::SUCCESS => 'Berhasil',
        self::FAILED => 'Gagal',
    ];

    /** @var array<string, string> */
    private const BADGES = [
        self::NOT_EXECUTED => 'badge-soft-secondary',
        self::RUNNING => 'badge-soft-info',
        self::SUCCESS => 'badge-soft-success',
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
