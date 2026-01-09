<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameDetail extends Model
{
    protected $table = "stock_opname_details";
    protected $primaryKey = "stod_id";
    public $timestamps = true;
    public $incrementing = true;
    /**
     * Get detail list
     */
    public static function getDetail($data = [])
    {
        $data = array_merge([
            'sto_id' => null,
            'product_id' => null,
            'product_variant_id' => null,
        ], $data);

        $result = self::where('status', 1);

        if ($data['sto_id']) $result->where('sto_id', $data['sto_id']);
        if ($data['product_id']) $result->where('product_id', $data['product_id']);
        if ($data['product_variant_id']) $result->where('product_variant_id', $data['product_variant_id']);

        $result->orderBy('created_at', 'asc');
        $result = $result->get();
        foreach ($result as $key => $value) {
            $pv = (new ProductVariant())->getProductVariant(["product_variant_id"=>$value->product_variant_id]);
            if(isset($pv[0]->product_variant_id)) $pv = $pv[0];

            $temp = $pv;
            $temp->stod_system = $value->stod_system;
            $temp->stod_real =  $value->stod_real;
            $temp->stod_selisih =  $value->stod_selisih;
            $temp->stod_notes =  $value->stod_notes;
            $temp->stod_id  =  $value->stod_id ;
            $temp->sto_id  =  $value->sto_id ;
            $result[$key] = $temp;
        }
        return $result;
    }

    /**
     * Insert detail
     */
    public static function insertDetail($data)
    {
        foreach ($data['units'] as $u) {
            $sto = StockOpname::find($data['sto_id']);
            $s = ProductStock::where('product_variant_id', $data['product_variant_id'])
                ->where('unit_id', $u['unit_id'])
                ->first();
            
            // Catat log
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $sto->sto_code,
                'log_type'    => 1,
                'log_category' => 2,
                'log_item_id' => $data['product_variant_id'],
                'log_notes'  => "Stock Opname Produk",
                'log_jumlah' => $s->ps_stock,
                'unit_id'    => $u['unit_id'],
            ]);

            $s->ps_stock = $u['real_qty'];
            $s->save();

            // Catat log
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $sto->sto_code,
                'log_type'    => 1,
                'log_category' => 1,
                'log_item_id' => $data['product_variant_id'],
                'log_notes'  => "Stock Opname Produk",
                'log_jumlah' => $s->ps_stock,
                'unit_id'    => $u['unit_id'],
            ]);
        }
        
        $t = new self();
        $t->sto_id = $data['sto_id'];
        $t->product_id = $data['product_id'];
        $t->product_variant_id = $data['product_variant_id'];
        $t->stod_system = $data['stod_system'] ?? null;
        $t->stod_real = $data['stod_real'] ?? null;
        $t->stod_selisih = $data['stod_selisih'] ?? null;
        $t->stod_notes = $data['stod_notes'] ?? null;
        $t->save();

        return $t->stod_id;
    }

    /**
     * Update detail
     */
    public static function updateDetail($data)
    {
        $t = self::find($data['stod_id']);
        if (!$t) return null;

        $t->sto_id = $data['sto_id'];
        $t->product_id = $data['product_id'];
        $t->product_variant_id = $data['product_variant_id'];
        $t->stod_system = $data['stod_system'] ?? null;
        $t->stod_real = $data['stod_real'] ?? null;
        $t->stod_selisih = $data['stod_selisih'] ?? null;
        $t->stod_notes = $data['stod_notes'] ?? null;
        $t->save();

        return $t->stod_id;
    }

    /**
     * Soft delete detail (status = 0)
     */
    public static function deleteDetail($data)
    {
        $t = self::find($data['stod_id']);
        if ($t) {
            $t->status = 0;
            $t->save();
        }
    }

}
