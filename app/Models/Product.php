<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = "products";
    protected $primaryKey = "pr_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProduct($data = []){
        $data = [
            [
                'pr_id'   => 0001,
                'pr_name' => 'Case Samsung Mickey',
                'pr_sku' => 'PT002',
                'pr_category' => 'Elektronik',
                'pr_merk' => 'Samsung',
                'pr_unit' => json_encode(["Dus", "Pcs"]),
                'pr_variant' => json_encode(["Merah", "Kuning", "Hitam"]),
                'pr_barcode' => 'assets/img/barcodes/barcode-01.png',
            ],
            [
                'pr_id'   => 0002,
                'pr_name' => 'Lenovo Ideapad 3',
                'pr_sku' => 'PT008',
                'pr_category' => 'Elektronik',
                'pr_merk' => 'Lenovo',
                'pr_unit' => json_encode(["Pcs"]),
                'pr_variant' => json_encode(["Putih", "Hitam"]),
                'pr_barcode' => 'assets/img/barcodes/barcode-02.png',
            ],
        ];
        return $data;
    }

    
}
