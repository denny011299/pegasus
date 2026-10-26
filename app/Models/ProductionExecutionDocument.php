<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionExecutionDocument extends Model
{
    protected $table = 'production_execution_documents';
    protected $guarded = [];
    protected $casts = ['items' => 'array', 'signatures' => 'array'];
}
