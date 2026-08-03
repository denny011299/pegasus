<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProductReturn extends Model
{
    protected $table = 'customer_product_returns';
    protected $primaryKey = 'return_id';

    protected $fillable = [
        'return_number',
        'customer_id',
        'return_date',
        'ref_number',
        'notes',
        'proof_path',
        'status',
        'created_by',
        'acc_by',
    ];

    protected $casts = [
        'return_date' => 'date:Y-m-d',
        'status' => 'integer',
    ];
}
