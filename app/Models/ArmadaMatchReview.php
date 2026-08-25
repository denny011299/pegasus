<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Baris armada PMO yang antre konfirmasi manual — lihat migrasi
 * create_armada_match_reviews_table dan
 * App\Synchronization\Steps\ArmadaFlow\SyncArmadaStep/ResolveArmadaConflictsStep.
 */
class ArmadaMatchReview extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_DISCARDED = 'discarded';

    protected $table = 'armada_match_reviews';

    protected $primaryKey = 'armada_match_review_id';

    public $timestamps = true;

    public $incrementing = true;

    protected $guarded = [];

    protected $casts = [
        'candidate_customer_ids' => 'array',
        'saldo_armada' => 'integer',
        'resolved_at' => 'datetime',
    ];
}
