<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Errors\ErrorCatalog;
use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ExternalApi\V1\Concerns\HandlesListQueryParams;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Data master armada untuk sistem eksternal (API-002 lanjutan).
 *
 * "Armada" bukan tabel tersendiri — kendaraan disimpan sebagai baris pada
 * tabel customers, dengan nomor polisi dicatat di customer_notes (lihat
 * catatan kelas CashPaymentController, bagian "armada_id diterjemahkan ke
 * customers.customer_id"). Modul ini adalah jalur CRUD penuh untuk tabel
 * yang sama itu lewat External API.
 *
 * Nama field pada API sengaja TIDAK sama dengan nama kolomnya, karena
 * kolom-kolom itu dinamai untuk pelanggan sedangkan modul ini bicara
 * kendaraan. Pemetaannya dipusatkan di FIELD_MAP:
 *
 *   code            -> customer_code
 *   pic             -> customer_pic
 *   pic_phone       -> customer_pic_phone
 *   nomor_polisi    -> customer_notes
 *   category        -> customer_category
 *   merk_model      -> customer_merk_model
 *   tahun_kendaraan -> customer_tahun_kendaraan
 *   lokasi          -> customer_lokasi
 *
 * code adalah id UNIVERSAL yang dipakai pemanggil eksternal: wajib diisi
 * sendiri saat POST, dan dipakai sebagai path parameter pada PUT/DELETE —
 * pola yang sama dengan ref_unit_id pada satuan dan external_ref_id pada
 * sales. Beda dengan keduanya: TIDAK ADA endpoint connect di sini.
 * Alasannya: customer_code SELALU sudah terisi untuk setiap pelanggan
 * (di-generate otomatis lewat Customer::generateCustomerID() saat dibuat
 * lewat halaman admin), jadi tidak pernah ada baris "belum tersambung" yang
 * perlu dihubungkan belakangan seperti pada sales/satuan yang kolom
 * rujukannya nullable dan sering kosong. Kolom ini sekarang unik
 * (customers_customer_code_unique) supaya lookupnya selalu tidak ambigu.
 *
 * Tidak ada konsep "peran" untuk armada (beda dengan sales) — satu-satunya
 * syarat "dikelola" endpoint ini adalah status aktif, sama seperti
 * getCustomer() yang sudah ada.
 */
class MasterArmadaController extends Controller
{
    use HandlesListQueryParams;

    /**
     * Nama field API => nama kolom di tabel customers.
     *
     * Urutannya menentukan urutan field pada respons.
     *
     * @var array<string, string>
     */
    private const FIELD_MAP = [
        'code' => 'customer_code',
        'pic' => 'customer_pic',
        'pic_phone' => 'customer_pic_phone',
        'nomor_polisi' => 'customer_notes',
        'category' => 'customer_category',
        'merk_model' => 'customer_merk_model',
        'tahun_kendaraan' => 'customer_tahun_kendaraan',
        'lokasi' => 'customer_lokasi',
    ];

    /**
     * GET /api/external/v1/armada
     *
     * Paginasi, urutan (?sort=), dan pencarian (?search=) semuanya opsional
     * — lihat HandlesListQueryParams. Kunci ?sort= yang sah: id, code, pic,
     * pic_phone, nomor_polisi, category, merk_model, tahun_kendaraan,
     * lokasi, created_at, updated_at. ?search= mencari di code, pic,
     * pic_phone, dan nomor_polisi.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->respondList(
            (new Customer())->getArmadaForExternalApi(),
            $request,
            fn ($customer) => $this->present($customer),
            sortable: [
                'id' => 'customer_id',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ] + self::FIELD_MAP,
            searchable: ['customer_code', 'customer_pic', 'customer_pic_phone', 'customer_notes'],
            tieBreaker: 'customer_id',
        );
    }

    /**
     * POST /api/external/v1/armada
     *
     * Selalu membuat baris pelanggan baru (id Pegasus-nya auto-increment,
     * tidak pernah ditentukan pemanggil). Bukan upsert: code yang sudah
     * dipakai armada/pelanggan lain ditolak sebagai duplicate_ref_id —
     * pakai PUT untuk memperbarui armada yang code-nya sudah ada.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCreatePayload($request);
        $code = $data['code'];

        if (Customer::where('customer_code', $code)->exists()) {
            return $this->duplicateRefError($code);
        }

        $customer = new Customer();
        $customer->customer_code = $code;
        $customer->status = 1;
        $customer->created_by = null;
        $this->applyPayload($customer, $data);

        try {
            $customer->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // Dua permintaan dengan code baru yang sama, nyaris bersamaan:
            // keduanya sama-sama tidak menemukan baris di atas, lalu unique
            // index menolak yang kalah cepat.
            if (Customer::where('customer_code', $code)->exists()) {
                return $this->duplicateRefError($code);
            }

            throw $e;
        }

        return ApiResponse::success($this->present($customer), [], 201);
    }

    /**
     * PUT /api/external/v1/armada/{code}
     *
     * Tidak pernah membuat armada baru; code yang tidak ditemukan (atau
     * ditemukan tapi statusnya nonaktif) selalu dijawab not_found.
     */
    public function update(Request $request, string $code): JsonResponse
    {
        $customer = $this->findManagedByCode($code);

        if ($customer === null) {
            return $this->notFoundError($code);
        }

        $data = $this->validateProfilePayload($request);
        $this->applyPayload($customer, $data);
        $customer->save();

        return ApiResponse::success($this->present($customer));
    }

    /**
     * DELETE /api/external/v1/armada/{code}
     *
     * Soft delete (status = 0), memakai ulang Customer::deleteCustomer() —
     * sama persis dengan yang dipakai halaman admin. code TIDAK dilepas oleh
     * operasi ini, jadi kode yang sudah dihapus lewat endpoint ini tidak
     * bisa dipakai ulang lewat POST (baris lama masih memegangnya, hanya
     * berstatus nonaktif).
     */
    public function destroy(string $code): JsonResponse
    {
        $customer = $this->findManagedByCode($code);

        if ($customer === null) {
            return $this->notFoundError($code);
        }

        (new Customer())->deleteCustomer(['customer_id' => $customer->customer_id]);

        return ApiResponse::success(['code' => $code]);
    }

    /* ------------------------------------------------------------------ */
    /* Validasi & penyimpanan                                             */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function validateCreatePayload(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:10'],
        ] + $this->profileRules());
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProfilePayload(Request $request): array
    {
        return $request->validate($this->profileRules());
    }

    /**
     * Field profil armada — semuanya nullable di basis data, jadi semuanya
     * opsional di sini; satu-satunya field wajib adalah code (id
     * universalnya). Field lain pada tabel customers (customer_name,
     * customer_address, customer_email, sales_id, area/city/district,
     * customer_zipcode) sengaja tidak dikelola endpoint ini, mengikuti
     * cakupan yang sama dengan logika admin yang sudah ada.
     *
     * @return array<string, array<int, mixed>>
     */
    private function profileRules(): array
    {
        return [
            'pic' => ['nullable', 'string', 'max:255'],
            'pic_phone' => ['nullable', 'string', 'max:50'],
            'nomor_polisi' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'merk_model' => ['nullable', 'string', 'max:255'],
            'tahun_kendaraan' => ['nullable', 'string', 'max:20'],
            'lokasi' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function applyPayload(Customer $customer, array $data): void
    {
        foreach (self::FIELD_MAP as $field => $column) {
            // code hanya diisi saat pembuatan, tidak pernah lewat payload.
            if ($field === 'code') {
                continue;
            }

            $customer->{$column} = $data[$field] ?? null;
        }
    }

    /**
     * Armada dikelola endpoint ini kalau statusnya aktif — tidak ada
     * penyaringan lain seperti peran pada sales, karena tabel customers
     * tidak punya kolom jenis yang membedakan "armada" dari pelanggan lain.
     */
    private function findManagedByCode(string $code): ?Customer
    {
        return Customer::where('customer_code', $code)
            ->where('status', 1)
            ->first();
    }

    /* ------------------------------------------------------------------ */
    /* Respons                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function present(Customer $customer): array
    {
        $result = ['id' => (int) $customer->customer_id];

        foreach (self::FIELD_MAP as $field => $column) {
            $result[$field] = $customer->{$column};
        }

        return $result;
    }

    private function notFoundError(string $code): JsonResponse
    {
        return ApiResponse::error(
            ErrorCatalog::NOT_FOUND,
            'Armada dengan code "'.$code.'" tidak ditemukan.',
            404,
        );
    }

    private function duplicateRefError(string $code): JsonResponse
    {
        return ApiResponse::error(
            ErrorCatalog::DUPLICATE_REF_ID,
            'code "'.$code.'" sudah dipakai pelanggan/armada lain.',
            422,
        );
    }
}
