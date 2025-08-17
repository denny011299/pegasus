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
                'pr_category'    => 'Elektronik',
                'pr_merk'       => 'Samsung',
                'pr_unit'      => 'DUS',
            ],
            [
                'pr_id'   => 0002,
                'pr_name' => 'Lenovo Ideapad 3',
                'pr_category'    => 'Elektronik',
                'pr_merk'       => 'Lenovo',
                'pr_unit'      => 'PCS',
            ],
        ];
        return $data;
    }
}
