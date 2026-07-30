<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferDetail extends Model
{
    protected $table = 'stock_transfer_details';

    protected $primaryKey = 'std_id';

    public $timestamps = true;

    public $incrementing = true;

    protected $fillable = [
        'st_id',
        'product_id',
        'product_variant_id',
        'unit_id',
        'received_unit_id',
        'qty',
        'qty_received',
        'status',
    ];

    public function header()
    {
        return $this->belongsTo(StockTransfer::class, 'st_id', 'st_id');
    }
}
