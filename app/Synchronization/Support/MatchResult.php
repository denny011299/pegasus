<?php

namespace App\Synchronization\Support;

/**
 * Hasil pencocokan satu baris PMO terhadap data Pegasus.
 */
class MatchResult
{
    public const BY_REFERENCE = 'by_reference';
    public const ADOPTED = 'adopted';
    public const NOT_FOUND = 'not_found';
    public const AMBIGUOUS = 'ambiguous';

    /**
     * @param  array<int, int>  $candidates  id lokal yang bersaing saat ambigu
     */
    private function __construct(
        public readonly string $kind,
        public readonly ?int $localId = null,
        public readonly array $candidates = [],
    ) {
    }

    public static function byReference(int $localId): self
    {
        return new self(self::BY_REFERENCE, $localId);
    }

    public static function adopted(int $localId): self
    {
        return new self(self::ADOPTED, $localId);
    }

    public static function notFound(): self
    {
        return new self(self::NOT_FOUND);
    }

    /**
     * @param  array<int, int>  $candidates
     */
    public static function ambiguous(array $candidates): self
    {
        return new self(self::AMBIGUOUS, null, $candidates);
    }

    public function found(): bool
    {
        return $this->localId !== null;
    }

    public function isAmbiguous(): bool
    {
        return $this->kind === self::AMBIGUOUS;
    }
}
