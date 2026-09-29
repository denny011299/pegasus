<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOutputReport extends Model
{
    protected $table = 'production_output_reports';
    protected $guarded = [];
    protected $casts = ['items' => 'array', 'actor_snapshot' => 'array'];
}
