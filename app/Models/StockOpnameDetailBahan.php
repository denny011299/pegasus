<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameDetailBahan extends Model
{
    protected $table = "stock_opname_detail_bahans";
    protected $primaryKey = "stobd_id";
    public $timestamps = true;
    public $incrementing = true;
    /**
     * Get detail list
     */
    public static function getDetail($data = [])
    {
        $data = array_merge([
            'stob_id' => null,
            'supplies_id' => null,
        ], $data);

        $result = self::where('status', 1);

        if ($data['stob_id']) $result->where('stob_id', $data['stob_id']);
        if ($data['supplies_id']) $result->where('supplies_id', $data['supplies_id']);

        $result->orderBy('created_at', 'asc');
        $result = $result->get();
        foreach ($result as $key => $value) {
            $pv = (new Supplies())->getSupplies(["supplies_id"=>$value->supplies_id]);
            if(isset($pv[0]->supplies_id)) $pv = $pv[0];

            $temp = $pv;
            $temp->sp_units = [];
            $temp->stobd_system = $value->stobd_system;
            $temp->stobd_real =  $value->stobd_real;
            $temp->stobd_selisih =  $value->stobd_selisih;
            $temp->stobd_notes =  $value->stobd_notes;
            $temp->stobd_id  =  $value->stobd_id ;
            $temp->stob_id  =  $value->stob_id ;
            $result[$key] = $temp;
        }
        return $result;
    }

    /**
     * Insert detail
     */
    public static function insertDetail($data)
    {
        $stob = StockOpnameBahan::find($data['stob_id']);
        foreach ($data['sp_units'] as $u) {
            $s = SuppliesStock::where('supplies_id', $data['supplies_id'])
                ->where('unit_id', $u['unit_id'])
                ->first();

            // Catat log
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $stob->stob_code,
                'log_category' => 2,
                'log_item_id' => $data['supplies_id'],
                'log_notes'  => "Stock Opname Bahan Mentah",
                'log_jumlah' => $s->ss_stock,
                'unit_id'    => $u['unit_id'],
            ]);
            $s->ss_stock = $u['real_qty'];
            $s->save();

            // Catat log
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $stob->stob_code,
                'log_category' => 1,
                'log_item_id' => $data['supplies_id'],
                'log_notes'  => "Stock Opname Bahan Mentah",
                'log_jumlah' => $s->ss_stock,
                'unit_id'    => $u['unit_id'],
            ]);
        }
        
        $t = new self();
        $t->stob_id = $data['stob_id'];
        $t->supplies_id = $data['supplies_id'];
        $t->stobd_system = $data['stobd_system'] ?? null;
        $t->stobd_real = $data['stobd_real'] ?? null;
        $t->stobd_selisih = $data['stobd_selisih'] ?? null;
        $t->stobd_notes = $data['stobd_notes'] ?? null;
        $t->save();

        return $t->stobd_id;
    }

    /**
     * Update detail
     */
    public static function updateDetail($data)
    {
        $t = self::find($data['stobd_id']);
        if (!$t) return null;

        $t->stob_id = $data['stob_id'];
        $t->supplies_id = $data['supplies_id'];
        $t->stobd_system = $data['stobd_system'] ?? null;
        $t->stobd_real = $data['stobd_real'] ?? null;
        $t->stobd_selisih = $data['stobd_selisih'] ?? null;
        $t->stobd_notes = $data['stobd_notes'] ?? null;
        $t->save();

        return $t->stobd_id;
    }

    /**
     * Soft delete detail (status = 0)
     */
    public static function deleteDetail($data)
    {
        $t = self::find($data['stobd_id']);
        if ($t) {
            $t->status = 0;
            $t->save();
        }
    }

}
