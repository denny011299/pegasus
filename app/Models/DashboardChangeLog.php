<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DashboardChangeLog extends Model
{
    protected $table = 'dashboard_change_logs';

    protected $fillable = [
        'module_key',
        'module_label',
        'reference',
        'what_changed',
        'summary',
        'url',
        'url_label',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
