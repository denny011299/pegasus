<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameDetail extends Model
{
    protected $table = "stock_opname_details";
    protected $primaryKey = "stod_id";
    public $timestamps = true;
    public $incrementing = true;

    function getDetailStockOpname($data = [])
    {
        $data = [
            [
                'pr_sku' => 'SKU-001',
                'pr_name' => 'Logitech Wireless Mouse',
                'pr_stock' => 15,
                'stpd_stock' => 15,
                'stpd_real_stock' => 14,
                'stpd_note' => '1 unit rusak',
                'selisih' => -1
            ],
            [
                'pr_sku' => 'SKU-002',
                'pr_name' => 'Dell Mechanical Keyboard',
                'pr_stock' => 8,
                'stpd_stock' => 8,
                'stpd_real_stock' => 8,
                'stpd_note' => '',
                'selisih' => 0
            ],
            [
                'pr_sku' => 'SKU-003',
                'pr_name' => 'Samsung 24-inch Monitor',
                'pr_stock' => 5,
                'stpd_stock' => 5,
                'stpd_real_stock' => 6,
                'stpd_note' => 'Tambahan 1 unit dari gudang lama',
                'selisih' => 1
            ]
        ];
        return $data;
        // $data = array_merge([
        //     "stp_id"=>null,
        // ], $data);

        // $result = self::query();
        // if($data["stp_id"]) $result->where('stp_id','=',$data["stp_id"]);
        // $result->orderBy('created_at', 'asc');
       
        // $result =   $result->get();

        // foreach ($result as $key => $value) {
        //      $sup = Supplies::find($value->sup_id);
        //     if($sup){
        //         $value->sup_name = $sup->sup_name;
        //         $value->sup_unit = $sup->sup_unit;
        //         $value->sup_sku = $sup->sup_sku;
        //     }
        //     else{
        //         $sup = Product::find($value->pr_id);
        //         if($sup){
        //             $value->pr_name = $sup->pr_name;
        //             $value->pr_unit = $sup->pr_unit;
        //             $value->pr_sku = $sup->pr_sku;
        //         }
        //     }
        // }

        // return $result;
    }

    function insertDetailStockOpname($data)
    {
        // $t = new self();
        // $t->stp_id = $data["stp_id"];
        // if(isset($data["pr_id"]))$t->pr_id = $data["pr_id"];
        // if(isset($data["sup_id"]))$t->sup_id = $data["sup_id"];
        // $t->stpd_stock = $data["stpd_stock"];
        // $t->stpd_real_stock = $data["stpd_real_stock"];
        // $t->stpd_selisih = $data["stpd_selisih"];
        // $t->stpd_note = $data["stpd_note"];
        // $t->save();
        // return $t->stpd_id;
    }

    function updateDetailStockOpname($data)
    {
        // $t = self::find($data["stpd_id"]);
        // $t->stp_id = $data["stp_id"];
        // if(isset($data["pr_id"]))$t->pr_id = $data["pr_id"];
        // if(isset($data["sup_id"]))$t->sup_id = $data["sup_id"];
        // $t->stpd_stock = $data["stpd_stock"];
        // $t->stpd_real_stock = $data["stpd_real_stock"];
        // $t->stpd_selisih = $data["stpd_selisih"];
        // $t->stpd_note = $data["stpd_note"];
        // $t->save();
        // return $t->stp_id;
    }

    function deleteDetailStockOpname($data)
    {
        // $t = self::find($data["stpd_id"]);
        // $t->status = 0;
        // $t->save();
    }
}
