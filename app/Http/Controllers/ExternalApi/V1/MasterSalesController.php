<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
 * berstatus aktif — lihat isManagedSales(). staff_id internal yang menunjuk
 * staf lain (Admin, Direksi, dst.) atau staf yang sudah dihapus lewat
 * endpoint ini diperlakukan sebagai not_found, BUKAN diizinkan mengubah/
 * menimpa profil staf itu.
 *
 * DUA id yang berbeda dipakai di sini, jangan tertukar:
 *   - staffs.staff_id  : id asli Pegasus, auto-increment, tidak pernah
 *                        ditentukan pemanggil.
 *   - external_ref_id  : id milik sistem pemanggil sendiri (nullable,
 *                        unik). Inilah yang disebut "staff_id" pada BODY
 *                        create dan pada PATH ubah/hapus — sengaja memakai
 *                        nama yang sama dengan field di respons GET
 *                        (lihat presentRow()), tapi maknanya rujukan
 *                        eksternal, BUKAN staffs.staff_id.
 *
 * Staf baru yang dibuat lewat POST TIDAK mendapat staff_username atau
 * staff_password (dibiarkan null) — baris yang dibuat adalah profil sinkron
 * dari sistem luar, bukan akun login ke Pegasus. Kedua kolom itu memang
 * nullable di basis data.
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
     * Selalu membuat baris staf baru (id Pegasus-nya auto-increment, tidak
     * pernah ditentukan pemanggil) dengan external_ref_id = body.staff_id.
     * Bukan upsert: external_ref_id yang sudah dipakai sales lain ditolak
     * sebagai duplicate_ref_id — pakai PUT (ubah) untuk memperbarui sales
     * yang rujukannya sudah ada, atau PATCH untuk menghubungkan rujukan ke
     * staf Pegasus yang sudah ada.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCreatePayload($request);
        $refId = (string) $data['staff_id'];

        if (Staff::where('external_ref_id', $refId)->exists()) {
            return $this->duplicateRefError($refId);
        }

        $staff = new Staff();
        $staff->external_ref_id = $refId;
        $staff->role_id = $this->salesRoleId();
        $staff->status = 1;
        $staff->created_by = null;
        $this->applyPayload($staff, $data);

        try {
            $staff->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // Dua permintaan dengan external_ref_id baru yang sama, nyaris
            // bersamaan: keduanya sama-sama tidak menemukan baris di atas,
            // lalu unique index menolak yang kalah cepat.
            if (Staff::where('external_ref_id', $refId)->exists()) {
                return $this->duplicateRefError($refId);
            }

            throw $e;
        }

        return ApiResponse::success($this->present($staff), [], 201);
    }

    /**
     * PUT /api/external/v1/master/sales/{staff_id}
     *
     * {staff_id} di sini adalah external_ref_id (rujukan sistem pemanggil),
     * BUKAN id internal Pegasus — lihat catatan kelas. Tidak pernah membuat
     * sales baru; external_ref_id yang tidak ditemukan (atau ditemukan tapi
     * bukan sales aktif) selalu dijawab not_found.
     */
    public function update(Request $request, string $staff_id): JsonResponse
    {
        $staff = $this->findManagedByRef($staff_id);

        if ($staff === null) {
            return $this->notFoundByRefError($staff_id);
        }

        $data = $this->validateProfilePayload($request);
        $this->applyPayload($staff, $data);
        $staff->save();

        return ApiResponse::success($this->present($staff));
    }

    /**
     * DELETE /api/external/v1/master/sales/{staff_id}
     *
     * {staff_id} adalah external_ref_id, sama seperti PUT. Soft delete
     * (status = 0), memakai ulang Staff::deletestaff() — sama persis
     * dengan yang dipakai halaman admin (UserController::deleteStaff).
     * external_ref_id TIDAK dilepas oleh operasi ini, jadi id yang sudah
     * dihapus lewat endpoint ini tidak bisa dipakai ulang lewat POST
     * (baris lama masih memegangnya, hanya berstatus nonaktif).
     */
    public function destroy(string $staff_id): JsonResponse
    {
        $staff = $this->findManagedByRef($staff_id);

        if ($staff === null) {
            return $this->notFoundByRefError($staff_id);
        }

        (new Staff())->deletestaff(['staff_id' => $staff->staff_id]);

        return ApiResponse::success(['staff_id' => $staff_id]);
    }

    /**
     * PATCH /api/external/v1/master/sales/connect
     *
     * Bentuk jamak: satu permintaan boleh menghubungkan banyak staf
     * sekaligus lewat body.connections (array). Setiap butir berisi
     * staff_id (id internal Pegasus — KEBALIKANNYA dari PUT/DELETE, lihat
     * catatan kelas) dan map_staff_id (rujukan yang mau dipasang), dipakai
     * untuk menghubungkan staf yang sudah ada (dibuat lewat halaman admin,
     * sudah berperan Sales) dengan rujukan milik sistem pemanggil, tanpa
     * perlu membuat baris baru.
     *
     * Setiap butir diproses independen (bukan atomik satu transaksi
     * besar): butir yang datanya salah tidak menggagalkan butir lain dalam
     * permintaan yang sama. Respons berupa daftar hasil per butir, masing-
     * masing menandai berhasil/gagal sendiri-sendiri lewat success.
     *
     * Menimpa link yang sudah ada pada staf tujuan diperbolehkan. Kalau
     * map_staff_id yang dikirim sedang dipegang staf LAIN, rujukan itu
     * dilepas dulu dari staf itu (external_ref_id-nya jadi null) sebelum
     * dipasang ke staf tujuan — "dipindah", bukan ditolak sebagai duplikat
     * — supaya keunikan external_ref_id tetap terjaga tanpa pemanggil harus
     * melepas link lama secara manual lebih dulu.
     */
    public function connect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'connections' => ['required', 'array', 'min:1'],
            'connections.*.staff_id' => ['required', 'integer', 'min:1'],
            'connections.*.map_staff_id' => ['required', 'string', 'max:191'],
        ]);

        $results = array_map(
            fn (array $item) => $this->connectOne((int) $item['staff_id'], (string) $item['map_staff_id']),
            $data['connections'],
        );

        $successCount = count(array_filter($results, static fn ($r) => $r['success']));

        return ApiResponse::success($results, [
            'total' => count($results),
            'success' => $successCount,
            'failed' => count($results) - $successCount,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function connectOne(int $staffId, string $rawMapStaffId): array
    {
        $mapStaffId = trim($rawMapStaffId);

        if ($mapStaffId === '') {
            return $this->connectFailure($staffId, $rawMapStaffId, ApiResponse::ERROR_VALIDATION, 'map_staff_id tidak boleh kosong.');
        }

        $staff = Staff::find($staffId);

        if ($staff === null || ! $this->isManagedSales($staff)) {
            return $this->connectFailure(
                $staffId,
                $mapStaffId,
                ApiResponse::ERROR_NOT_FOUND,
                'Staf dengan id '.$staffId.' tidak ditemukan atau bukan sales aktif.',
            );
        }

        DB::transaction(function () use ($staff, $mapStaffId) {
            Staff::where('external_ref_id', $mapStaffId)
                ->where('staff_id', '!=', $staff->staff_id)
                ->update(['external_ref_id' => null]);

            $staff->external_ref_id = $mapStaffId;
            $staff->save();
        });

        return [
            'staff_id' => $staffId,
            'map_staff_id' => $mapStaffId,
            'success' => true,
            'data' => $this->present($staff->fresh()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function connectFailure(int $staffId, string $mapStaffId, string $code, string $message): array
    {
        return [
            'staff_id' => $staffId,
            'map_staff_id' => $mapStaffId,
            'success' => false,
            'error' => ['code' => $code, 'message' => $message],
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Validasi & penyimpanan                                             */
    /* ------------------------------------------------------------------ */

    /**
     * staff_id (POST) adalah external_ref_id, boleh dikirim sebagai angka
     * atau teks (sistem lain bisa punya id alfanumerik) — divalidasi lewat
     * closure, bukan aturan 'string', supaya angka JSON polos seperti pada
     * contoh (staff_id: 2) tetap diterima dan disimpan sebagai teks.
     *
     * @return array<string, mixed>
     */
    private function validateCreatePayload(Request $request): array
    {
        $rules = [
            'staff_id' => ['required', function (string $attribute, $value, \Closure $fail) {
                if (! is_string($value) && ! is_int($value)) {
                    $fail('staff_id wajib berupa teks atau angka.');
                }
            }],
        ] + $this->profileRules();

        return $request->validate($rules);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProfilePayload(Request $request): array
    {
        return $request->validate($this->profileRules());
    }

    /**
     * nama_belakang dan alamat bersifat nullable: kalau tidak dikirim,
     * nilainya disimpan/ditimpa jadi null/kosong, bukan mempertahankan
     * nilai lama — body selalu dianggap representasi penuh sales ini, sama
     * seperti endpoint gudang.
     *
     * @return array<string, array<int, mixed>>
     */
    private function profileRules(): array
    {
        return [
            'nama_depan' => ['required', 'string', 'max:120'],
            'nama_belakang' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'alamat' => ['nullable', 'string'],
        ];
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

    private function findManagedByRef(string $refId): ?Staff
    {
        $staff = Staff::where('external_ref_id', $refId)->first();

        return ($staff !== null && $this->isManagedSales($staff)) ? $staff : null;
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
     * Bentuk respons create/update/patch: id internal Pegasus DAN
     * external_ref_id sekaligus, supaya pemanggil yang baru membuat sales
     * lewat POST bisa tahu id internalnya tanpa panggilan GET terpisah
     * (dibutuhkan seandainya nanti perlu PATCH).
     *
     * @return array<string, mixed>
     */
    private function present(Staff $staff): array
    {
        [$namaDepan, $namaBelakang] = $this->splitName((string) $staff->staff_name);

        return [
            'id' => (int) $staff->staff_id,
            'staff_id' => $staff->external_ref_id,
            'nama_depan' => $namaDepan,
            'nama_belakang' => $namaBelakang,
            'email' => $staff->staff_email,
            'alamat' => $staff->staff_address,
        ];
    }

    /**
     * Bentuk respons daftar (GET). id = id internal Pegasus (dulu bernama
     * staff_id sebelum endpoint create/update/delete/patch ini ada);
     * staff_id sekarang berarti external_ref_id, boleh null kalau staf itu
     * belum pernah dihubungkan ke sistem eksternal mana pun.
     *
     * @return array<string, mixed>
     */
    private function presentRow(Staff $staff): array
    {
        [$namaDepan, $namaBelakang] = $this->splitName((string) $staff->staff_name);

        return [
            'id' => (int) $staff->staff_id,
            'staff_id' => $staff->external_ref_id,
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

    private function notFoundByRefError(string $refId): JsonResponse
    {
        return ApiResponse::error(
            ApiResponse::ERROR_NOT_FOUND,
            'Sales dengan staff_id (external_ref_id) "'.$refId.'" tidak ditemukan.',
            404,
        );
    }


    private function duplicateRefError(string $refId): JsonResponse
    {
        return ApiResponse::error(
            'duplicate_ref_id',
            'staff_id (external_ref_id) "'.$refId.'" sudah dipakai sales lain.',
            422,
        );
    }
}
