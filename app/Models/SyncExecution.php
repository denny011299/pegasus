<?php

namespace App\Models;

use App\Synchronization\SyncStatus;
use Illuminate\Database\Eloquent\Model;

class SyncExecution extends Model
{
    protected $table = 'sync_executions';

    protected $primaryKey = 'sync_execution_id';

    public $timestamps = true;

    public $incrementing = true;

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'details' => 'array',
        'errors' => 'array',
        'notices' => 'array',
        'duration_ms' => 'integer',
        'total_processed' => 'integer',
        'total_inserted' => 'integer',
        'total_updated' => 'integer',
        'total_failed' => 'integer',
        'total_skipped' => 'integer',
    ];

    /**
     * Bentuk ringkas untuk dikirim ke wizard.
     *
     * @return array<string, mixed>
     */
    public function toWizardArray(): array
    {
        return [
            'status' => $this->status,
            'status_label' => SyncStatus::label((string) $this->status),
            'badge_class' => SyncStatus::badgeClass((string) $this->status),
            'message' => $this->message,
            'started_at' => optional($this->started_at)->format('d/m/Y H:i:s'),
            'finished_at' => optional($this->finished_at)->format('d/m/Y H:i:s'),
            'duration' => $this->humanDuration(),
            'summary' => [
                'total_processed' => $this->total_processed,
                'total_inserted' => $this->total_inserted,
                'total_updated' => $this->total_updated,
                'total_failed' => $this->total_failed,
                'total_skipped' => $this->total_skipped,
            ],
            'details' => $this->details ?: [],
            'errors' => $this->errors ?: [],
            'notices' => $this->notices ?: [],
            'executed_by_name' => $this->executed_by
                ? (Staff::find($this->executed_by)->staff_name ?? '-')
                : '-',
        ];
    }

    public function humanDuration(): string
    {
        $ms = (int) $this->duration_ms;

        if ($ms < 1000) {
            return $ms.' ms';
        }

        $seconds = $ms / 1000;
        if ($seconds < 60) {
            return number_format($seconds, 2, ',', '.').' detik';
        }

        $minutes = floor($seconds / 60);
        $rest = $seconds - ($minutes * 60);

        return $minutes.' menit '.number_format($rest, 0, ',', '.').' detik';
    }
}
