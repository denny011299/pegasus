<?php

namespace App\Support;

/** Baca feature flag dari config/features.php. Flag yang tidak terdaftar dianggap off. */
class Feature
{
    public static function on(string $name): bool
    {
        return (bool) config('features.'.$name, false);
    }

    /** @return array<string, bool> semua flag, untuk window.PG_FEATURES */
    public static function all(): array
    {
        return array_map(fn ($v) => (bool) $v, (array) config('features', []));
    }
}
