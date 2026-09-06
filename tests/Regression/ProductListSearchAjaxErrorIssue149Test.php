<?php

namespace Tests\Regression;

use Tests\TestCase;
use Tests\Support\ActingAsStaff;

/**
 * GitHub #149: mencari produk (mis. "ka") memicu "DataTables warning: Ajax error"
 * di halaman Daftar Produk. Root cause: kolom `products.product_unit` dan hasil
 * CAST(units.unit_id AS CHAR) punya collation berbeda (drift lama antar tabel),
 * jadi MySQL menolak perbandingan LIKE-nya dengan "Illegal mix of collations".
 */
class ProductListSearchAjaxErrorIssue149Test extends TestCase
{
    use ActingAsStaff;

    public function test_get_product_search_does_not_error(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->getJson('/getProduct?draw=1&start=0&length=10&search%5Bvalue%5D=ka');

        $response->assertStatus(200)
            ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data']);
    }
}
