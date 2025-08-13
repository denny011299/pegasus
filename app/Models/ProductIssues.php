<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductIssues extends Model
{
    use HasFactory;
    protected $table = "product_issues";
    protected $primaryKey = "pi_id";
    public $timestamps = true;
    public $incrementing = true;

    public function getProductIssue($data = []){
        $data = [
            [
                'pi_product' => 'Lenovo 3rd Generation',
                'pi_image' => 'assets/img/products/product-01.png',
                'pi_sku' => 'PT008',
                'pi_date' => '18 Mar 2023',
                'pi_qty' => '12 Pcs',
                'pi_notes' => '-',
                'pi_status' => 'Return'
            ],
            [
                'pi_product' => 'Nike Jordan',
                'pi_image' => 'assets/img/products/product-02.png',
                'pi_sku' => 'PT012',
                'pi_date' => '25 Apr 2023',
                'pi_qty' => '5 Pcs',
                'pi_notes' => 'Customer returned due to defect',
                'pi_status' => 'Return'
            ],
            [
                'pi_product' => 'Apple Series 5 Watch',
                'pi_image' => 'assets/img/products/product-03.png',
                'pi_sku' => 'DSK003',
                'pi_date' => '10 May 2023',
                'pi_qty' => '2 Pcs',
                'pi_notes' => 'Damaged during shipping',
                'pi_status' => 'Damage'
            ]
        ];
        return $data;
    }

    function insertProductIssue($data){
        
    }

    function updateProductIssue($data){

    }

    function deleteProductIssue($data){

    }
}
