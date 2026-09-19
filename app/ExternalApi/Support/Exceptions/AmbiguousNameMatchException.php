<?php

namespace App\ExternalApi\Support\Exceptions;

/**
 * Dilempar App\ExternalApi\Support\UnitAutoSync / CategoryAutoSync ketika
 * pencocokan lewat nama (fase adopsi App\Synchronization\Support\ReferenceMatcher)
 * menemukan lebih dari satu baris lokal yang cocok — sama seperti ambiguitas yang
 * dilaporkan gagal oleh SyncUnitStep/SyncCategoryStep, di sini dijawab sebagai
 * ErrorCatalog::AMBIGUOUS_NAME_MATCH (422) oleh controller yang menangkapnya.
 */
class AmbiguousNameMatchException extends \RuntimeException
{
    /**
     * @param  array<int, int>  $candidateIds
     */
    public function __construct(
        public readonly string $entityLabel,
        public readonly string $name,
        public readonly array $candidateIds,
    ) {
        parent::__construct(
            $entityLabel.' "'.$name.'" cocok dengan '.count($candidateIds).' baris sekaligus di Pegasus.'
        );
    }
}
