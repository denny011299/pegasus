<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerSupplyReturnDetail extends Model
{
    protected $table = 'customer_supply_return_details';
    protected $primaryKey = 'return_detail_id';

    protected $fillable = [
        'return_id',
        'supplies_id',
        'unit_id',
        'warehouse_id',
        'qty',
        'status',
    ];

    protected $casts = [
        'qty' => 'integer',
        'status' => 'integer',
    ];
}
