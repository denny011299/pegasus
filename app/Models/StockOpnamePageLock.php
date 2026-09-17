<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnamePageLock extends Model
{
    protected $table = 'stock_opname_page_locks';

    protected $primaryKey = 'id';

    public $timestamps = true;

    public $incrementing = true;

    protected $casts = [
        'locked_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];
}
