<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProductReturn extends Model
{
    protected $table = 'customer_product_returns';
    protected $primaryKey = 'return_id';

    protected $fillable = [
        'return_number',
        'return_group',
        'customer_id',
        'return_date',
        'ref_number',
        'notes',
        'proof_path',
        'status',
        'created_by',
        'qc_staff_id',
        'acc_by',
    ];

    protected $casts = [
        'return_date' => 'date:Y-m-d',
        'status' => 'integer',
    ];

    /** Nomor urut: PBJ0001, PBJ0002, ... */
    public function generateReturnNumber(): string
    {
        $id = (int) (self::max('return_id') ?? 0) + 1;

        return 'PBJ' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }
}
