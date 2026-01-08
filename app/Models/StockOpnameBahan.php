<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
            'staff_id' => null,
            'category_id' => null,
            'stob_id' => null,
        ], $data);

        $result = self::where('status', 1);

        if ($data['stob_date']) {
            $result->whereDate('stob_date', $data['stob_date']);
        }

        if ($data['staff_id']) {
            $result->where('staff_id', $data['staff_id']);
        }
        
        if ($data['stob_id']) {
            $result->where('stob_id','=', $data['stob_id']);
        }

        $result->orderBy('stob_date', 'desc');

        $result =  $result->get();
        
        foreach ($result as $key => $value) {
           $value->staff_name = Staff::find($value->staff_id)->staff_name;
        //    $value->category_name = Category::find($value->category_id)->category_name;
           $value->item = (new StockOpnameDetailBahan())->getDetail(["stob_id"=>$value->stob_id]);
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
