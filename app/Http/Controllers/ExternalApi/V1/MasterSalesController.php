<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Data master sales untuk sistem eksternal (API-002 lanjutan).
 *
 * Beda dengan MasterDataController: sales di sini boleh dibuat, diubah, dan
 * dihapus lewat External API, bukan cuma dibaca — sama alasannya dengan
 * kenapa MasterWarehouseController dipisah dari MasterDataController.
 *
 * "Sales" bukan tabel tersendiri, melainkan staf (staffs) yang perannya
 * bernama "sales" (lihat Staff::getSalesForExternalApi()). Karena itu
 * endpoint ini HANYA boleh menyentuh staf yang perannya cocok dan masih
 * berstatus aktif — lihat isManagedSales(). staff_id yang menunjuk staf lain
 * (Admin, Direksi, dst.) atau staf yang sudah dihapus lewat endpoint ini
 * diperlakukan sebagai not_found, BUKAN diizinkan mengubah/menimpa peran
 * atau profil staf itu. Ini sengaja: pemanggil luar tidak boleh bisa
 * mengubah staf yang bukan urusannya walau staff_id yang dikirim valid dan
 * memang ada di sistem.
 *
 * Staf baru yang dibuat lewat endpoint ini TIDAK mendapat staff_username
 * atau staff_password (dibiarkan null) — baris yang dibuat adalah profil
 * sinkron dari sistem luar, bukan akun login ke Pegasus. Kedua kolom itu
 * memang nullable di basis data.
 */
class MasterSalesController extends Controller
{
    /**
     * GET /api/external/v1/master/sales
     */
    public function index(): JsonResponse
    {
        $sales = (new Staff())->getSalesForExternalApi();

        return ApiResponse::success(
            $sales->map(fn ($staff) => $this->presentRow($staff))->all(),
            ['total' => $sales->count()],
        );
    }

    /**
     * POST /api/external/v1/master/sales
     *
     * Upsert seperti tipe_id pada gudang: staff_id yang sudah ada (dan
     * masih berperan sales aktif) diperbarui di tempat; staff_id yang belum
     * ada dibuatkan staf baru dengan id PERSIS yang dikirim (bukan id
     * auto-increment berikutnya), berperan Sales.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request, true);
        $staffId = (int) $data['staff_id'];

        $existing = Staff::find($staffId);

        if ($existing !== null) {
            if (! $this->isManagedSales($existing)) {
                return $this->notFoundError($staffId);
            }

            $this->applyPayload($existing, $data);
            $existing->save();

            return ApiResponse::success($this->present($existing));
        }

        $staff = new Staff();
        $staff->staff_id = $staffId;
        $staff->role_id = $this->salesRoleId();
        $staff->status = 1;
        $staff->created_by = null;
        $this->applyPayload($staff, $data);
        $staff->save();

        return ApiResponse::success($this->present($staff), [], 201);
    }

    /**
     * PUT /api/external/v1/master/sales/{staff_id}
     *
     * Beda dengan POST: staff_id di sini murni rujukan, tidak pernah
     * membuat staf baru. staff_id yang tidak ditemukan (atau ditemukan tapi
     * bukan sales aktif) selalu dijawab not_found.
     */
    public function update(Request $request, int $staff_id): JsonResponse
    {
        $staff = Staff::find($staff_id);

        if ($staff === null || ! $this->isManagedSales($staff)) {
            return $this->notFoundError($staff_id);
        }

        $data = $this->validatePayload($request, false);
        $this->applyPayload($staff, $data);
        $staff->save();

        return ApiResponse::success($this->present($staff));
    }

    /**
     * DELETE /api/external/v1/master/sales/{staff_id}
     *
     * Soft delete (status = 0), memakai ulang Staff::deletestaff() — sama
     * persis dengan yang dipakai halaman admin (UserController::deleteStaff).
     */
    public function destroy(int $staff_id): JsonResponse
    {
        $staff = Staff::find($staff_id);

        if ($staff === null || ! $this->isManagedSales($staff)) {
            return $this->notFoundError($staff_id);
        }

        (new Staff())->deletestaff(['staff_id' => $staff_id]);

        return ApiResponse::success(['staff_id' => $staff_id]);
    }

    /* ------------------------------------------------------------------ */
    /* Validasi & penyimpanan                                             */
    /* ------------------------------------------------------------------ */

    /**
     * staff_id hanya wajib pada body POST — pada PUT nilainya berasal dari
     * path, bukan body (lihat update()).
     *
     * nama_belakang dan alamat bersifat nullable: kalau tidak dikirim,
     * nilainya disimpan/ditimpa jadi null/kosong, bukan mempertahankan
     * nilai lama — body selalu dianggap representasi penuh staf ini, sama
     * seperti endpoint gudang.
     *
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, bool $requireStaffId): array
    {
        $rules = [
            'nama_depan' => ['required', 'string', 'max:120'],
            'nama_belakang' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'alamat' => ['nullable', 'string'],
        ];

        if ($requireStaffId) {
            $rules['staff_id'] = ['required', 'integer', 'min:1'];
        }

        return $request->validate($rules);
    }

    /**
     * staffs.staff_name adalah satu kolom gabungan (tidak ada kolom nama
     * depan/belakang terpisah), jadi nama_depan dan nama_belakang digabung
     * di sini dan dipisah lagi lewat splitName() saat disajikan kembali.
     * kode (staff_code), telepon (staff_phone), dan role (role_id staf yang
     * sudah ada) sengaja tidak disentuh — endpoint ini tidak mengelola
     * field-field itu.
     */
    private function applyPayload(Staff $staff, array $data): void
    {
        $namaDepan = trim($data['nama_depan']);
        $namaBelakang = trim((string) ($data['nama_belakang'] ?? ''));

        $staff->staff_name = $namaBelakang !== '' ? $namaDepan.' '.$namaBelakang : $namaDepan;
        $staff->staff_email = $data['email'];
        $staff->staff_address = $data['alamat'] ?? null;
    }

    /**
     * Sales dikelola endpoint ini kalau statusnya aktif DAN perannya cocok
     * dengan definisi "sales" yang sama dipakai GET (nama peran mengandung
     * kata "sales"). Di luar itu — staf nonaktif, atau staf berperan lain
     * seperti Admin/Direksi — dianggap di luar jangkauan endpoint ini.
     */
    private function isManagedSales(Staff $staff): bool
    {
        if ((int) $staff->status !== 1 || $staff->role_id === null) {
            return false;
        }

        return Role::query()
            ->where('role_id', $staff->role_id)
            ->where('role_name', 'like', '%sales%')
            ->exists();
    }

    /**
     * id peran "Sales" dipakai saat membuat staf baru lewat POST.
     *
     * Sengaja gagal keras (jadi 500 lewat ExceptionRenderer, bukan dijawab
     * seolah berhasil) kalau nama peran yang mengandung "sales" bukan
     * persis satu — itu berarti data master peran tidak lagi sesuai dengan
     * asumsi endpoint ini, bukan kesalahan pemanggil yang perlu divalidasi.
     */
    private function salesRoleId(): int
    {
        $roleIds = Role::query()
            ->where('role_name', 'like', '%sales%')
            ->pluck('role_id');

        if ($roleIds->count() !== 1) {
            throw new \RuntimeException(
                'Peran "sales" tidak dapat ditentukan secara unik ('.$roleIds->count().' kecocokan).',
            );
        }

        return (int) $roleIds->first();
    }

    /* ------------------------------------------------------------------ */
    /* Respons                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Bentuk respons create/update: hanya field yang dikelola endpoint ini.
     *
     * @return array<string, mixed>
     */
    private function present(Staff $staff): array
    {
        [$namaDepan, $namaBelakang] = $this->splitName((string) $staff->staff_name);

        return [
            'staff_id' => (int) $staff->staff_id,
            'nama_depan' => $namaDepan,
            'nama_belakang' => $namaBelakang,
            'email' => $staff->staff_email,
            'alamat' => $staff->staff_address,
        ];
    }

    /**
     * Bentuk respons daftar (GET): field lama (nama, kode, telepon, role)
     * dipertahankan supaya pemanggil yang sudah ada tidak putus, ditambah
     * nama_depan/nama_belakang supaya sejalan dengan bentuk create/update —
     * sama seperti gudang_id ditambahkan berdampingan dengan id pada daftar
     * gudang.
     *
     * @return array<string, mixed>
     */
    private function presentRow(Staff $staff): array
    {
        [$namaDepan, $namaBelakang] = $this->splitName((string) $staff->staff_name);

        return [
            'staff_id' => (int) $staff->staff_id,
            'nama' => (string) $staff->staff_name,
            'nama_depan' => $namaDepan,
            'nama_belakang' => $namaBelakang,
            'kode' => $staff->staff_code,
            'email' => $staff->staff_email,
            'telepon' => $staff->staff_phone,
            'alamat' => $staff->staff_address,
            'role' => (string) $staff->role_name,
        ];
    }

    /**
     * staff_name dipisah pada spasi pertama: token pertama jadi nama_depan,
     * sisanya (kalau ada) jadi nama_belakang. Untuk staf yang namanya lebih
     * dari dua kata dan tidak pernah dibuat/diubah lewat endpoint ini,
     * pemisahan ini bisa saja tidak sama dengan pembagian depan/belakang
     * yang "benar" secara manusiawi — staffs.staff_name memang cuma satu
     * kolom gabungan, tidak ada sumber lain untuk memisahkannya.
     *
     * @return array{0: string, 1: ?string}
     */
    private function splitName(string $staffName): array
    {
        $staffName = trim($staffName);

        if ($staffName === '') {
            return ['', null];
        }

        $parts = explode(' ', $staffName, 2);

        return [$parts[0], $parts[1] ?? null];
    }

    private function notFoundError(int $staffId): JsonResponse
    {
        return ApiResponse::error(
            ApiResponse::ERROR_NOT_FOUND,
            'Sales dengan staff_id '.$staffId.' tidak ditemukan.',
            404,
        );
    }
}
