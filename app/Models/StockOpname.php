<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class StockOpname extends Model
{
    protected $table = "stock_opnames";
    protected $primaryKey = "sto_id";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'sto_date',
        'staff_id',
        'category_id',
        'sto_notes',
        'status'
    ];

    /**
     * Get list of stock opname
     */
    function getStockOpname($data = [])
    {
        $data = array_merge([
            'sto_date' => null,
            'staff_id' => null,
            'category_id' => null,
            'sto_id' => null,
        ], $data);

        $result = self::where('status', '>=', 1);

        if ($data['sto_date']) {
            $result->whereDate('sto_date', $data['sto_date']);
        }

        if ($data['staff_id']) {
            $result->where('staff_id', $data['staff_id']);
        }
        
        if ($data['sto_id']) {
            $result->where('sto_id','=', $data['sto_id']);
        }

        $result->orderBy('status', 'asc')->orderBy('sto_date', 'desc');

        $result = $result->get();

        $staffIds = [];
        foreach ($result as $value) {
            if ($value->staff_id) {
                $staffIds[(int) $value->staff_id] = true;
            }
            if ($value->created_by) {
                $staffIds[(int) $value->created_by] = true;
            }
            if ($value->acc_by) {
                $staffIds[(int) $value->acc_by] = true;
            }
        }
        $staffMap = [];
        if (count($staffIds) > 0) {
            $staffMap = Staff::whereIn('staff_id', array_keys($staffIds))
                ->pluck('staff_name', 'staff_id')
                ->toArray();
        }

        $stoIds = $result->pluck('sto_id')->unique()->filter()->values()->all();
        $detailsGrouped = StockOpnameDetail::getDetailBulk($stoIds);

        foreach ($result as $value) {
            $value->staff_name = $staffMap[$value->staff_id] ?? '-';
            $value->item = $detailsGrouped->get($value->sto_id, collect())->values();
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
    function insertStockOpname($data)
    {
        $t = new self();
        $t->sto_date = $data['sto_date'];
        $t->sto_code   = $this->generateStockOpnameID();
        $t->staff_id = $data['staff_id'];
        $t->category_id = $data['category_id'];
        $t->sto_notes = $data['sto_notes'] ?? null;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->sto_id;
    }

    /**
     * Update stock opname
     */
    function updateStockOpname($data)
    {
        $t = self::find($data['sto_id']);
        if (!$t) return null;

        $t->sto_date = $data['sto_date'];
        $t->staff_id = $data['staff_id'];
        $t->category_id = $data['category_id'];
        $t->sto_notes = $data['sto_notes'] ?? null;
        $t->save();

        return $t->sto_id;
    }

    /**
     * Soft delete stock opname (set status = 0)
     */
    function deleteStockOpname($data)
    {
        $t = self::find($data['sto_id']);
        if ($t) {
            $t->status = 0;
            $t->save();
        }
    }

    function generateStockOpnameID()
    {
        $id = self::max('sto_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "SP" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
