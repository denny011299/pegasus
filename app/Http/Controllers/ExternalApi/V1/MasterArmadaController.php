<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ExternalApi\V1\Concerns\HandlesOptionalPagination;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Data master armada untuk sistem eksternal (API-002 lanjutan).
 *
 * "Armada" bukan tabel tersendiri — kendaraan disimpan sebagai baris pada
 * tabel customers, dengan nomor polisi + nama pemilik dicatat di
 * customer_notes (lihat catatan kelas CashPaymentController, bagian
 * "armada_id diterjemahkan ke customers.customer_id"). Modul ini adalah
 * jalur CRUD penuh untuk tabel yang sama itu lewat External API.
 *
 * customer_code adalah id UNIVERSAL yang dipakai pemanggil eksternal: wajib
 * diisi sendiri saat POST, dan dipakai sebagai path parameter pada PUT/
 * DELETE — pola yang sama dengan ref_unit_id pada satuan dan external_ref_id
 * pada sales. Beda dengan keduanya: TIDAK ADA endpoint connect di sini.
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
    use HandlesOptionalPagination;

    /**
     * GET /api/external/v1/armada
     *
     * Paginasi bersifat opsional: kirim ?page= (dan ?per_page= bila perlu)
     * untuk mengaktifkannya. Tanpa itu, seluruh armada aktif dikembalikan
     * sekaligus sekali jalan — sama seperti endpoint data master lain, lihat
     * HandlesOptionalPagination.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->respondList(
            (new Customer())->getArmadaForExternalApi(),
            $request,
            fn ($customer) => $this->present($customer),
        );
    }

    /**
     * POST /api/external/v1/armada
     *
     * Selalu membuat baris pelanggan baru (id Pegasus-nya auto-increment,
     * tidak pernah ditentukan pemanggil). Bukan upsert: customer_code yang
     * sudah dipakai armada/pelanggan lain ditolak sebagai duplicate_ref_id
     * — pakai PUT untuk memperbarui armada yang customer_code-nya sudah ada.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCreatePayload($request);
        $customerCode = $data['customer_code'];

        if (Customer::where('customer_code', $customerCode)->exists()) {
            return $this->duplicateRefError($customerCode);
        }

        $customer = new Customer();
        $customer->customer_code = $customerCode;
        $customer->status = 1;
        $customer->created_by = null;
        $this->applyPayload($customer, $data);

        try {
            $customer->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // Dua permintaan dengan customer_code baru yang sama, nyaris
            // bersamaan: keduanya sama-sama tidak menemukan baris di atas,
            // lalu unique index menolak yang kalah cepat.
            if (Customer::where('customer_code', $customerCode)->exists()) {
                return $this->duplicateRefError($customerCode);
            }

            throw $e;
        }

        return ApiResponse::success($this->present($customer), [], 201);
    }

    /**
     * PUT /api/external/v1/armada/{customer_code}
     *
     * Tidak pernah membuat armada baru; customer_code yang tidak ditemukan
     * (atau ditemukan tapi statusnya nonaktif) selalu dijawab not_found.
     */
    public function update(Request $request, string $customer_code): JsonResponse
    {
        $customer = $this->findManagedByCode($customer_code);

        if ($customer === null) {
            return $this->notFoundError($customer_code);
        }

        $data = $this->validateProfilePayload($request);
        $this->applyPayload($customer, $data);
        $customer->save();

        return ApiResponse::success($this->present($customer));
    }

    /**
     * DELETE /api/external/v1/armada/{customer_code}
     *
     * Soft delete (status = 0), memakai ulang Customer::deleteCustomer() —
     * sama persis dengan yang dipakai halaman admin. customer_code TIDAK
     * dilepas oleh operasi ini, jadi kode yang sudah dihapus lewat endpoint
     * ini tidak bisa dipakai ulang lewat POST (baris lama masih
     * memegangnya, hanya berstatus nonaktif).
     */
    public function destroy(string $customer_code): JsonResponse
    {
        $customer = $this->findManagedByCode($customer_code);

        if ($customer === null) {
            return $this->notFoundError($customer_code);
        }

        (new Customer())->deleteCustomer(['customer_id' => $customer->customer_id]);

        return ApiResponse::success(['customer_code' => $customer_code]);
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
            'customer_code' => ['required', 'string', 'max:10'],
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
     * Mengikuti persis field yang sudah dikelola Customer::insertCustomer()/
     * updateCustomer() milik halaman admin — customer_pic, customer_pic_phone,
     * dan customer_notes (tempat konvensi "nomor polisi (nama)" tersimpan).
     * Ketiganya nullable di basis data, jadi ketiganya opsional di sini;
     * satu-satunya field wajib adalah customer_code (id universalnya).
     * Field lain pada tabel customers (customer_name, customer_address,
     * customer_email, sales_id, area/city/district, customer_zipcode)
     * sengaja tidak dikelola endpoint ini, mengikuti cakupan yang sama
     * dengan logika admin yang sudah ada.
     *
     * @return array<string, array<int, mixed>>
     */
    private function profileRules(): array
    {
        return [
            'customer_pic' => ['nullable', 'string', 'max:255'],
            'customer_pic_phone' => ['nullable', 'string', 'max:50'],
            'customer_notes' => ['nullable', 'string'],
        ];
    }

    private function applyPayload(Customer $customer, array $data): void
    {
        $customer->customer_pic = $data['customer_pic'] ?? null;
        $customer->customer_pic_phone = $data['customer_pic_phone'] ?? null;
        $customer->customer_notes = $data['customer_notes'] ?? null;
    }

    /**
     * Armada dikelola endpoint ini kalau statusnya aktif — tidak ada
     * penyaringan lain seperti peran pada sales, karena tabel customers
     * tidak punya kolom jenis yang membedakan "armada" dari pelanggan lain.
     */
    private function findManagedByCode(string $customerCode): ?Customer
    {
        return Customer::where('customer_code', $customerCode)
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
        return [
            'id' => (int) $customer->customer_id,
            'customer_code' => $customer->customer_code,
            'customer_pic' => $customer->customer_pic,
            'customer_pic_phone' => $customer->customer_pic_phone,
            'customer_notes' => $customer->customer_notes,
        ];
    }

    private function notFoundError(string $customerCode): JsonResponse
    {
        return ApiResponse::error(
            ApiResponse::ERROR_NOT_FOUND,
            'Armada dengan customer_code "'.$customerCode.'" tidak ditemukan.',
            404,
        );
    }

    private function duplicateRefError(string $customerCode): JsonResponse
    {
        return ApiResponse::error(
            'duplicate_ref_id',
            'customer_code "'.$customerCode.'" sudah dipakai pelanggan/armada lain.',
            422,
        );
    }
}
