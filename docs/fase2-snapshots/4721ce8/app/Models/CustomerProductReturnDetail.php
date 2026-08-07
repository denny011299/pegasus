<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProductReturnDetail extends Model
{
    protected $table = 'customer_product_return_details';
    protected $primaryKey = 'return_detail_id';

    protected $fillable = [
        'return_id',
        'product_variant_id',
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
