<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncSnapshot extends Model
{
    protected $table = 'sync_snapshots';

    protected $primaryKey = 'sync_snapshot_id';

    public $timestamps = true;

    public $incrementing = true;

    protected $guarded = [];

    protected $casts = [
        'fetched_at' => 'datetime',
        'row_count' => 'integer',
    ];
}
