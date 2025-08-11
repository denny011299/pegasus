<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariants extends Model
{
    use HasFactory;
    protected $table = "variants";
    protected $primaryKey = "variant_id";
    public $timestamps = true;
    public $incrementing = true;

    function getVariant($data = []){
        $data = [
            [
                "variant_id" => 1,
                "variant_name" => "Warna",
                "variant_attribute" => json_encode(["Merah", "Biru", "Hijau"]),
                "variant_date" => now(),
            ],
            [
                "variant_id" => 2,
                "variant_name" => "Ukuran",
                "variant_attribute" => json_encode(["S", "M", "L", "XL"]),
                "variant_date" => now(),
            ],
            [
                "variant_id" => 3,
                "variant_name" => "Material",
                "variant_attribute" => json_encode(["Katun", "Polyester"]),
                "variant_date" => now(),
            ],
            [
                "variant_id" => 4,
                "variant_name" => "Rasa",
                "variant_attribute" => json_encode(["Cokelat", "Vanilla", "Strawberry"]),
                "variant_date" => now(),
            ],
        ];

        return $data;
    }

    function insertVariant($data){
        
    }

    function updateVariant($data){

    }

    function deleteVariant($data){

    }
}
