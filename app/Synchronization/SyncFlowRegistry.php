<?php

namespace App\Synchronization;

/**
 * Kumpulan alur sinkronisasi yang terdaftar di config/synchronization.php.
 */
class SyncFlowRegistry
{
    /** @var array<string, SyncFlow>|null */
    private ?array $flows = null;

    /**
     * @param  array<int, class-string<SyncFlow>>  $flowClasses
     */
    public function __construct(private readonly array $flowClasses)
    {
    }

    /**
     * @return array<string, SyncFlow>
     */
    public function all(): array
    {
        if ($this->flows !== null) {
            return $this->flows;
        }

        $flows = [];
        foreach ($this->flowClasses as $class) {
            $flow = app($class);

            if (! $flow instanceof SyncFlow) {
                throw new \LogicException('Alur '.$class.' harus merupakan turunan '.SyncFlow::class.'.');
            }

            $flows[$flow->key()] = $flow;
        }

        return $this->flows = $flows;
    }

    public function find(string $key): ?SyncFlow
    {
        return $this->all()[$key] ?? null;
    }

    public function findOrFail(string $key): SyncFlow
    {
        $flow = $this->find($key);

        if (! $flow) {
            abort(404, 'Alur sinkronisasi "'.$key.'" tidak ditemukan.');
        }

        return $flow;
    }
}
