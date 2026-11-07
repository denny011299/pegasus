<?php

namespace App\Observers;

use App\Models\ProductionExecutionDocument;
use App\Models\ProductionOutputReport;
use App\Models\ProductionPlanning;
use App\Models\ProductionPlanningItem;
use App\Models\ProductionWorkOrder;
use App\Support\ProductionFloorBroadcast;
use Illuminate\Database\Eloquent\Model;

class ProductionFloorObserver
{
    public function saved(Model $model): void
    {
        ProductionFloorBroadcast::ping($this->warehouseId($model), 'saved');
    }

    public function deleted(Model $model): void
    {
        ProductionFloorBroadcast::ping($this->warehouseId($model), 'deleted');
    }

    private function warehouseId(Model $model): ?int
    {
        if (isset($model->warehouse_id) && (int) $model->warehouse_id > 0) {
            return (int) $model->warehouse_id;
        }

        if ($model instanceof ProductionPlanningItem) {
            $wh = ProductionPlanning::whereKey($model->production_planning_id)->value('warehouse_id');

            return $wh ? (int) $wh : ProductionPlanning::activeWarehouseId();
        }

        if ($model instanceof ProductionExecutionDocument || $model instanceof ProductionOutputReport) {
            $wh = ProductionWorkOrder::whereKey($model->production_work_order_id)->value('warehouse_id');

            return $wh ? (int) $wh : ProductionPlanning::activeWarehouseId();
        }

        return ProductionPlanning::activeWarehouseId();
    }
}
