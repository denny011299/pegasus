<?php

namespace App\Synchronization;

use App\Models\SyncExecution;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Menjalankan tepat satu langkah sinkronisasi.
 *
 * Sengaja tidak pernah melanjutkan ke langkah berikutnya: eksekusi selalu
 * manual dan sepenuhnya dikendalikan pengguna.
 */
class SyncRunner
{
    public function __construct(
        private readonly SyncExecutionRepository $executions,
        private readonly PrerequisiteChecker $prerequisites,
    ) {
    }

    /**
     * @throws PrerequisiteNotMetException
     */
    public function run(SyncFlow $flow, SyncStep $step): SyncExecution
    {
        $latest = $this->executions->latestForFlow($flow);
        $unmet = $this->prerequisites->unmet($flow, $step, $latest);

        if ($unmet !== []) {
            throw new PrerequisiteNotMetException(
                'Langkah ini belum bisa dijalankan. Selesaikan dulu: '.implode(', ', $unmet).'.'
            );
        }

        $result = SyncStepResult::start();

        try {
            $result = $step->makeHandler()->handle();
        } catch (Throwable $e) {
            Log::error('Sinkronisasi gagal', [
                'flow' => $flow->key(),
                'step' => $step->key,
                'exception' => $e,
            ]);

            $result->addError($e->getMessage())->fail($e->getMessage());
        }

        return $this->executions->record($flow, $step, $result);
    }
}
