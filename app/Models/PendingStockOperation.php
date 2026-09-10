<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingStockOperation extends Model
{
    protected $table = 'pending_stock_operations';

    protected $primaryKey = 'pso_id';

    public $timestamps = true;

    public $incrementing = true;

    public const STATUS_DELETED = 0;

    public const STATUS_PENDING = 1;

    public const STATUS_APPLIED = 2;

    public const STATUS_CANCELLED = 3;

    public const DOMAIN_PRODUCT = 'product';

    public const DOMAIN_SUPPLIES = 'supplies';

    public const SOURCE_PRODUCTION_ACC = 'production_acc';

    public const SOURCE_STOCK_TRANSFER_SHIP = 'stock_transfer_ship';

    public const SOURCE_STOCK_TRANSFER_ACCEPT = 'stock_transfer_accept';

    protected $fillable = [
        'warehouse_id',
        'domain',
        'source_type',
        'source_id',
        'source_code',
        'payload',
        'status',
        'blocked_by_opname_type',
        'blocked_by_opname_id',
        'created_by',
        'applied_by',
        'applied_at',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'applied_at' => 'datetime',
    ];
}
