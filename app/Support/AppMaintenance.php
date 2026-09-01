<?php

namespace App\Support;

class AppMaintenance
{
    public static function enabled(): bool
    {
        if (filter_var(config('maintenance.enabled'), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        $file = (string) config('maintenance.file');

        return $file !== '' && is_file($file);
    }

    public static function message(): string
    {
        return (string) config('maintenance.message');
    }

    public static function flagPath(): string
    {
        return (string) config('maintenance.file');
    }

    public static function enableFileFlag(): void
    {
        $path = self::flagPath();
        if ($path === '') {
            return;
        }

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($path, now()->toIso8601String());
    }

    public static function disableFileFlag(): void
    {
        $path = self::flagPath();
        if ($path !== '' && is_file($path)) {
            @unlink($path);
        }
    }

    public static function fileFlagEnabled(): bool
    {
        $path = self::flagPath();

        return $path !== '' && is_file($path);
    }
}
