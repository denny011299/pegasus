<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class StockOpnameBahan extends Model
{
    protected $table = "stock_opname_bahans";
    protected $primaryKey = "stob_id";
    public $timestamps = true;
    public $incrementing = true;

    function getStockOpnameBahan($data = [])
    {
        $data = array_merge([
            'stob_date' => null,
            'staff_id'  => null,
            'stob_id'   => null,
        ], $data);

        $result = self::where('status', '>=', 1);

        if ($data['stob_date']) $result->whereDate('stob_date', $data['stob_date']);
        if ($data['staff_id'])  $result->where('staff_id', $data['staff_id']);
        if ($data['stob_id'])   $result->where('stob_id', $data['stob_id']);

        $result->orderBy('status', 'asc')->orderBy('stob_date', 'desc');

        $result = $result->get();

        $staffIdSet = [];
        foreach ($result as $value) {
            if ($value->staff_id) {
                $staffIdSet[(int) $value->staff_id] = true;
            }
            if ($value->created_by) {
                $staffIdSet[(int) $value->created_by] = true;
            }
            if ($value->acc_by) {
                $staffIdSet[(int) $value->acc_by] = true;
            }
        }
        $staffMap = $staffIdSet !== []
            ? Staff::whereIn('staff_id', array_keys($staffIdSet))->pluck('staff_name', 'staff_id')
            : collect();

        $stobIds = $result->pluck('stob_id')->unique()->filter();
        $detailsGrouped = StockOpnameDetailBahan::getDetailBulk($stobIds->toArray());

        foreach ($result as $value) {
            $value->staff_name = $staffMap[$value->staff_id] ?? '-';
            $value->item = $detailsGrouped->get($value->stob_id, collect());
            $staffMain = $value->staff_name ?? '-';
            $value->created_by_name = $value->created_by
                ? ($staffMap[$value->created_by] ?? $staffMain)
                : $staffMain;
            $value->acc_by_name = $value->acc_by ? ($staffMap[$value->acc_by] ?? '-') : '-';
        }

        return $result;
    }

    /**
     * Insert new stock opname
     */
    function insertStockOpnameBahan($data)
    {
        $t = new self();
        $t->stob_date = $data['stob_date'];
        $t->stob_code   = $this->generateStockOpnameBahanID();
        $t->staff_id = $data['staff_id'];
        $t->stob_notes = $data['stob_notes'] ?? null;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->stob_id;
    }

    /**
     * Update stock opname
     */
    function updateStockOpnameBahan($data)
    {
        $t = self::find($data['stob_id']);
        if (!$t) return null;

        $t->stob_date = $data['stob_date'];
        $t->staff_id = $data['staff_id'];
        $t->stob_notes = $data['stob_notes'] ?? null;
        $t->save();

        return $t->stob_id;
    }

    /**
     * Soft delete stock opname (set status = 0)
     */
    function deleteStockOpnameBahan($data)
    {
        $t = self::find($data['stob_id']);
        if ($t) {
            $t->status = 0;
            $t->save();
        }
    }

    function generateStockOpnameBahanID()
    {
        $id = self::max('stob_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "SB" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
