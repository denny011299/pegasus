<?php

namespace App\ExternalApi\Support;

use App\Models\Unit;

/**
 * @see UnitAutoSync
 */
final class UnitAutoSyncResult
{
    public const LINKED = 'linked';
    public const ADOPTED = 'adopted';
    public const CREATED = 'created';

    private function __construct(
        public readonly Unit $unit,
        public readonly string $outcome,
    ) {
    }

    public static function linked(Unit $unit): self
    {
        return new self($unit, self::LINKED);
    }

    public static function adopted(Unit $unit): self
    {
        return new self($unit, self::ADOPTED);
    }

    public static function created(Unit $unit): self
    {
        return new self($unit, self::CREATED);
    }

    public function isNew(): bool
    {
        return $this->outcome === self::CREATED;
    }
}
