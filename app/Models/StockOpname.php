<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    public static function getStockOpname($data = [])
    {
        $data = array_merge([
            'sto_date' => null,
            'staff_id' => null,
            'category_id' => null,
            'sto_id' => null,
        ], $data);

        $result = self::where('status', 1);

        if ($data['sto_date']) {
            $result->whereDate('sto_date', $data['sto_date']);
        }

        if ($data['staff_id']) {
            $result->where('staff_id', $data['staff_id']);
        }
        
        if ($data['sto_id']) {
            $result->where('sto_id','=', $data['sto_id']);
        }

        $result->orderBy('created_at', 'desc');

        $result =  $result->get();

        foreach ($result as $key => $value) {
           $value->staff_name = Staff::find($value->staff_id)->staff_name;
        //    $value->category_name = Category::find($value->category_id)->category_name;
           $value->item = (new StockOpnameDetail())->getDetail(["sto_id"=>$value->sto_id]);
        }
        return $result;
    }

    /**
     * Insert new stock opname
     */
    public static function insertStockOpname($data)
    {
        $t = new self();
        $t->sto_date = $data['sto_date'];
        $t->staff_id = $data['staff_id'];
        $t->category_id = $data['category_id'];
        $t->sto_notes = $data['sto_notes'] ?? null;
        $t->save();

        return $t->sto_id;
    }

    /**
     * Update stock opname
     */
    public static function updateStockOpname($data)
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
    public static function deleteStockOpname($data)
    {
        $t = self::find($data['sto_id']);
        if ($t) {
            $t->status = 0;
            $t->save();
        }
    }
}
