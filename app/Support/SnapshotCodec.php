<?php

namespace App\Support;

/**
 * Shared encoding rules between `seed:dump` and SnapshotSeeder.
 *
 * JSON cannot carry invalid UTF-8, which some legacy rows in this database do
 * contain. Those values travel base64-encoded behind a marker prefix instead.
 */
class SnapshotCodec
{
    public const BINARY_PREFIX = '@b64:';

    public static function decode(mixed $value): mixed
    {
        if (is_string($value) && str_starts_with($value, self::BINARY_PREFIX)) {
            return base64_decode(substr($value, strlen(self::BINARY_PREFIX)));
        }

        return $value;
    }
}
