<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionPlanningItem extends Model
{
    protected $table = 'production_planning_items';
    protected $primaryKey = 'ppi_id';
    public $timestamps = true;
    public $incrementing = true;
}
