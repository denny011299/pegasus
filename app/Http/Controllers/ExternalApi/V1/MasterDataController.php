<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\CashCategory;
use App\Models\Unit;
use App\Models\WarehouseType;

/**
 * Data master untuk sistem eksternal (API-001).
 *
 * Hanya baca. Tidak ada pembuatan, perubahan, maupun penghapusan lewat jalur
 * ini — perubahan data master tetap dilakukan lewat halaman admin.
 *
 * Pengecualian: gudang (warehouses) dan sales sekarang juga punya
 * create/update/delete lewat External API (API-002 lanjutan). Endpoint-
 * endpoint itu sengaja ditaruh di controller terpisah, masing-masing
 * MasterWarehouseController dan MasterSalesController, supaya controller ini
 * tetap murni baca-saja seperti didokumentasikan di sini.
 *
 * Autentikasi, pencatatan permintaan, dan bentuk respons seluruhnya diurus
 * lapisan platform (SPEC-001), jadi controller ini benar-benar hanya memilih
 * data dan menyusun bentuk keluarannya.
 *
 * Endpoint di controller ini sengaja tidak menerima parameter apa pun.
 * Tabelnya kecil (belasan sampai puluhan baris) dan spesifikasi API-001
 * secara tegas mengeluarkan penyaringan, pencarian, dan paginasi dari
 * lingkupnya — jadi respons selalu berupa daftar utuh data aktif.
 */
class MasterDataController extends Controller
{
    /**
     * GET /api/external/v1/master/units
     *
     * Nama field mengikuti kontrak satuan yang sudah ada pada dokumen
     * integrasi PMO (§8.1 pada 202607260130-product-sync-flow.design.md),
     * supaya kedua sistem memakai istilah yang sama.
     */
    public function units()
    {
        $units = (new Unit())->getUnitForExternalApi();

        return ApiResponse::success(
            $units->map(static fn ($unit) => [
                'unit_id' => (int) $unit->unit_id,
                'ref_unit_id' => $unit->ref_unit_id !== null ? (int) $unit->ref_unit_id : null,
                'unit_name' => (string) $unit->unit_name,
                'unit_short_name' => (string) $unit->unit_short_name,
            ])->all(),
            ['total' => $units->count()],
        );
    }

    /**
     * GET /api/external/v1/master/cash_categories
     *
     * Nama field mengikuti kolom yang ada di basis data (cc_*). Tidak ada
     * dokumen integrasi yang mendefinisikan kontrak kategori kas, jadi bentuk
     * yang dipakai diturunkan dari implementasi yang berjalan, bukan dikarang.
     */
    public function cashCategories()
    {
        $categories = (new CashCategory())->getCashCategoryForExternalApi();

        return ApiResponse::success(
            $categories->map(static fn ($category) => [
                'cc_id' => (int) $category->cc_id,
                'cc_name' => (string) $category->cc_name,
                'cc_type' => (string) $category->cc_type,
            ])->all(),
            ['total' => $categories->count()],
        );
    }

    /**
     * GET /api/external/v1/master/warehouse_types
     *
     * Kontrak API-002 menyebut hanya dua field: nama dan status. status tetap
     * disertakan walau nilainya selalu 1, karena kontraknya memintanya.
     */
    public function warehouseTypes()
    {
        $types = (new WarehouseType())->getWarehouseTypeForExternalApi();

        return ApiResponse::success(
            $types->map(static fn ($type) => [
                'nama' => (string) $type->warehouse_type_name,
                'status' => (int) $type->status,
            ])->all(),
            ['total' => $types->count()],
        );
    }
}
