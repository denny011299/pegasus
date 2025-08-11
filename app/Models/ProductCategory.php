<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;
    protected $table = "categories";
    protected $primaryKey = "category_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCategory($data = []){
        $data = [
            [
                "category_id" => 1,
                "category_name" => "Laptop",
                "created_at" => now(),
            ],
            [
                "category_id" => 2,
                "category_name" => "Handphone",
                "created_at" => now(),
            ]
        ];

        return $data;
    }

    function insertCategory($data){
        
    }

    function updateCategory($data){

    }

    function deleteCategory($data){

    }
}
