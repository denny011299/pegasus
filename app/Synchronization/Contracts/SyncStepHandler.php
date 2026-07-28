<?php

namespace App\Synchronization\Contracts;

use App\Synchronization\SyncStepResult;

/**
 * Semua logika sinkronisasi tinggal di implementasi kontrak ini.
 * Wizard hanya memanggil handler, tidak pernah memuat logika sinkronisasi.
 */
interface SyncStepHandler
{
    public function handle(): SyncStepResult;
}
