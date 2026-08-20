<?php

namespace App\Synchronization;

use App\Models\SyncExecution;
use App\Synchronization\Contracts\PaginatedStepHandler;
use App\Synchronization\Contracts\SyncStepHandler;
use Illuminate\Support\Facades\Log;
use LogicException;
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
     * Jalur sekali-jalan: handler menunggu sampai seluruh pekerjaannya
     * selesai (termasuk, untuk langkah berbasis halaman, seluruh halaman)
     * sebelum baris riwayat dicatat.
     *
     * @throws PrerequisiteNotMetException
     */
    public function run(SyncFlow $flow, SyncStep $step): SyncExecution
    {
        return $this->execute($flow, $step, fn (SyncStepHandler $handler) => $handler->handle());
    }

    /**
     * Selesaikan langkah berbasis halaman (SyncStep::$paginated) setelah
     * seluruh halamannya diambil lewat endpoint fetch-page — prasyarat,
     * pencatatan waktu, dan SyncExecution yang dihasilkan sama persis
     * dengan run().
     *
     * @throws PrerequisiteNotMetException
     */
    public function runFinalize(SyncFlow $flow, SyncStep $step): SyncExecution
    {
        return $this->execute($flow, $step, function (SyncStepHandler $handler) {
            if (! $handler instanceof PaginatedStepHandler) {
                throw new LogicException(get_class($handler).' bukan langkah berbasis halaman.');
            }

            return $handler->finalize();
        });
    }

    /**
     * @throws PrerequisiteNotMetException
     */
    private function execute(SyncFlow $flow, SyncStep $step, callable $work): SyncExecution
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
            $result = $work($step->makeHandler());
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
