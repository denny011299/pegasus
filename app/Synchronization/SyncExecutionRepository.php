<?php

namespace App\Synchronization;

use App\Models\SyncExecution;
use Illuminate\Support\Facades\Session;

/**
 * Baca/tulis riwayat eksekusi langkah sinkronisasi.
 */
class SyncExecutionRepository
{
    /**
     * Eksekusi terakhir untuk setiap langkah pada satu alur.
     *
     * @return array<string, SyncExecution>
     */
    public function latestForFlow(SyncFlow $flow): array
    {
        $stepKeys = array_keys($flow->stepsByKey());

        if ($stepKeys === []) {
            return [];
        }

        $rows = SyncExecution::where('flow_key', $flow->key())
            ->whereIn('step_key', $stepKeys)
            ->orderBy('sync_execution_id', 'desc')
            ->get();

        $latest = [];
        foreach ($rows as $row) {
            if (! isset($latest[$row->step_key])) {
                $latest[$row->step_key] = $row;
            }
        }

        return $latest;
    }

    public function record(SyncFlow $flow, SyncStep $step, SyncStepResult $result): SyncExecution
    {
        $user = Session::get('user');

        return SyncExecution::create([
            'flow_key' => $flow->key(),
            'step_key' => $step->key,
            'status' => $result->status,
            'started_at' => $result->startedAt,
            'finished_at' => $result->finishedAt,
            'duration_ms' => $result->durationMs(),
            'total_processed' => $result->processed,
            'total_inserted' => $result->inserted,
            'total_updated' => $result->updated,
            'total_failed' => $result->failed,
            'total_skipped' => $result->skipped,
            'message' => $result->message,
            'details' => $result->details ?: null,
            'errors' => $result->errors ?: null,
            'notices' => $result->notices ?: null,
            'executed_by' => $user->staff_id ?? null,
        ]);
    }
}
