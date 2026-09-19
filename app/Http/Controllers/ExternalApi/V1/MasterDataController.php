<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ExternalApi\V1\Concerns\HandlesListQueryParams;
use App\Models\CashCategory;
use App\Models\Category;
use App\Models\WarehouseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Data master untuk sistem eksternal (API-001).
 *
 * Hanya baca. Tidak ada pembuatan, perubahan, maupun penghapusan lewat jalur
 * ini — perubahan data master tetap dilakukan lewat halaman admin.
 *
 * Pengecualian: gudang (warehouses), sales, dan satuan (units) sekarang
 * juga punya create/update/delete lewat External API (API-001/API-002
 * lanjutan). Endpoint-endpoint itu sengaja ditaruh di controller terpisah,
 * masing-masing MasterWarehouseController, MasterSalesController, dan
 * MasterUnitController, supaya controller ini tetap murni baca-saja seperti
 * didokumentasikan di sini.
 *
 * Autentikasi, pencatatan permintaan, dan bentuk respons seluruhnya diurus
 * lapisan platform (SPEC-001), jadi controller ini benar-benar hanya memilih
 * data dan menyusun bentuk keluarannya.
 *
 * Paginasi bersifat OPSIONAL lewat HandlesListQueryParams (kirim ?page=
 * untuk mengaktifkannya) — bukan lagi dikeluarkan dari lingkup sepenuhnya.
 * Tabelnya tetap kecil (belasan sampai puluhan baris), jadi tanpa ?page=
 * respons tetap berupa daftar utuh data aktif seperti sebelumnya.
 */
class MasterDataController extends Controller
{
    use HandlesListQueryParams;

    /**
     * GET /api/external/v1/master/cash_categories
     *
     * Nama field mengikuti kolom yang ada di basis data (cc_*). Tidak ada
     * dokumen integrasi yang mendefinisikan kontrak kategori kas, jadi bentuk
     * yang dipakai diturunkan dari implementasi yang berjalan, bukan dikarang.
     *
     * Paginasi, urutan (?sort=), dan pencarian (?search=) semuanya opsional
     * — lihat HandlesListQueryParams. Kunci ?sort= yang sah: cc_id, cc_name,
     * cc_type, created_at, updated_at. ?search= mencari di cc_name.
     */
    public function cashCategories(Request $request): JsonResponse
    {
        return $this->respondList(
            (new CashCategory())->getCashCategoryForExternalApi(),
            $request,
            static fn ($category) => [
                'cc_id' => (int) $category->cc_id,
                'cc_name' => (string) $category->cc_name,
                'cc_type' => (string) $category->cc_type,
            ],
            sortable: [
                'cc_id' => 'cc_id',
                'cc_name' => 'cc_name',
                'cc_type' => 'cc_type',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ],
            searchable: ['cc_name'],
            tieBreaker: 'cc_id',
        );
    }

    /**
     * GET /api/external/v1/master/categories
     *
     * GitHub #171: PMO tidak punya konsep category_id internal Pegasus sama
     * sekali (beda dengan unit_id, yang bisa diresolusi lewat
     * /master/units begitu satuan tersinkron) — endpoint ini memberi PMO
     * jalan yang sama untuk meresolusi/menyimpan cache category_id sebelum
     * memanggil POST/PUT /api/external/v1/produk, yang mewajibkan
     * category_id menunjuk kategori aktif.
     *
     * Hanya baca, sama seperti cash_categories/warehouse_types di
     * controller ini — tidak ada create/update/delete kategori lewat
     * External API, kategori tetap dikelola lewat halaman admin.
     *
     * Paginasi, urutan (?sort=), dan pencarian (?search=) semuanya opsional
     * — lihat HandlesListQueryParams. Kunci ?sort= yang sah: id, nama,
     * created_at, updated_at. ?search= mencari di nama.
     */
    public function categories(Request $request): JsonResponse
    {
        return $this->respondList(
            (new Category())->getCategoryForExternalApi(),
            $request,
            static fn ($category) => [
                'id' => (int) $category->category_id,
                'nama' => (string) $category->category_name,
            ],
            sortable: [
                'id' => 'category_id',
                'nama' => 'category_name',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ],
            searchable: ['category_name'],
            tieBreaker: 'category_id',
        );
    }

    /**
     * GET /api/external/v1/master/warehouse_types
     *
     * Kontrak API-002 menyebut hanya dua field: nama dan status. status tetap
     * disertakan walau nilainya selalu 1, karena kontraknya memintanya.
     *
     * Paginasi, urutan (?sort=), dan pencarian (?search=) semuanya opsional
     * — lihat HandlesListQueryParams. Kunci ?sort= yang sah: nama, status,
     * created_at, updated_at. ?search= mencari di nama.
     */
    public function warehouseTypes(Request $request): JsonResponse
    {
        return $this->respondList(
            (new WarehouseType())->getWarehouseTypeForExternalApi(),
            $request,
            static fn ($type) => [
                'nama' => (string) $type->warehouse_type_name,
                'status' => (int) $type->status,
            ],
            sortable: [
                'nama' => 'warehouse_type_name',
                'status' => 'status',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ],
            searchable: ['warehouse_type_name'],
            tieBreaker: 'id',
        );
    }
}
