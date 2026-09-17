<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Errors\ErrorCatalog;
use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ExternalApi\V1\Concerns\HandlesListQueryParams;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Data master staf non-Sales untuk sistem eksternal (GitHub #177 / PMO task #4).
 *
 * MasterSalesController hanya menyentuh staf berperan "Sales" (lihat komentar
 * kelas itu) — hard-coded, sengaja begitu. Endpoint ini adalah kelas TERPISAH
 * (bukan perluasan MasterSalesController) untuk staf non-Sales.
 *
 * KEPUTUSAN PM (komentar issue #177, 2026-09-16): lingkupnya disederhanakan —
 * endpoint ini TIDAK menerima, menentukan, atau mengembalikan role sama
 * sekali. Setiap staf yang dibuat/dikelola lewat endpoint ini SELALU
 * role_id NULL — bukan "Owner" atau peran lain apa pun. "Dikelola endpoint
 * ini" karena itu berarti persis itu: staf aktif dengan role_id NULL, tanpa
 * kaitan ke tabel roles sama sekali (lihat isManagedStaff() dan
 * Staff::getRoleLessStaffForExternalApi()). Penanganan role bisa
 * dipertimbangkan lagi nanti kalau memang diperlukan — bukan bagian dari
 * versi pertama endpoint ini.
 *
 * role juga BUKAN properti yang bisa diubah lewat body sejak awal — staf
 * yang dibuat/dikelola endpoint ini TIDAK PERNAH mendapat
 * staff_username/staff_password (sama seperti sales), jadi tidak pernah
 * bisa login ke Pegasus sama sekali.
 *
 * Sisanya sengaja meniru kontrak MasterSalesController persis (dua id yang
 * dipakai, staff_username/staff_password tidak pernah diisi, dst.) supaya
 * PMO bisa memakai pola integrasi yang sama untuk kedua endpoint.
 */
class MasterStaffController extends Controller
{
    use HandlesListQueryParams;

    /**
     * GET /api/external/v1/master/staff
     *
     * Mengambil staf aktif yang role_id-nya NULL — lihat catatan kelas.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->respondList(
            (new Staff())->getRoleLessStaffForExternalApi(),
            $request,
            fn ($staff) => $this->presentRow($staff),
            sortable: [
                'id' => 'staffs.staff_id',
                'staff_id' => 'staffs.external_ref_id',
                'nama' => 'staffs.staff_name',
                'kode' => 'staffs.staff_code',
                'email' => 'staffs.staff_email',
                'telepon' => 'staffs.staff_phone',
                'alamat' => 'staffs.staff_address',
                'created_at' => 'staffs.created_at',
                'updated_at' => 'staffs.updated_at',
            ],
            searchable: [
                'staffs.staff_name',
                'staffs.staff_code',
                'staffs.staff_email',
                'staffs.staff_phone',
                'staffs.staff_address',
                'staffs.external_ref_id',
            ],
            tieBreaker: 'staffs.staff_id',
        );
    }

    /**
     * POST /api/external/v1/master/staff
     *
     * role TIDAK diterima dari body sama sekali — sekalipun dikirim, akan
     * diabaikan sepenuhnya. role_id staf yang dibuat SELALU NULL (keputusan
     * PM, lihat catatan kelas), tidak ditebak/di-resolve dari mana pun.
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
        $staff->role_id = null;
        $staff->status = 1;
        $staff->created_by = null;
        $this->applyPayload($staff, $data);

        try {
            $staff->save();
        } catch (\Illuminate\Database\QueryException $e) {
            if (Staff::where('external_ref_id', $refId)->exists()) {
                return $this->duplicateRefError($refId);
            }

            throw $e;
        }

        return ApiResponse::success($this->present($staff), [], 201);
    }

    /**
     * PUT /api/external/v1/master/staff/{staff_id}
     *
     * {staff_id} adalah external_ref_id, sama seperti /master/sales.
     *
     * Upsert: external_ref_id yang belum pernah ada membuat staf baru
     * (respons 201, role_id NULL — sama seperti POST), dipakai PMO untuk
     * langsung mengirim data staf yang belum pernah disinkronkan tanpa
     * harus tahu lebih dulu apakah staf itu sudah ada di Pegasus.
     * external_ref_id yang sudah ada tapi statusnya nonaktif DIAKTIFKAN
     * KEMBALI sekaligus diperbarui. external_ref_id yang sudah dipakai
     * staf BERPERAN (di luar jangkauan endpoint ini, mis. Sales) tetap
     * ditolak sebagai duplicate_ref_id — upsert tidak pernah mengambil
     * alih baris di luar cakupannya sendiri.
     */
    public function update(Request $request, string $staff_id): JsonResponse
    {
        $data = $this->validateProfilePayload($request);
        $staff = Staff::where('external_ref_id', $staff_id)->first();

        if ($staff === null) {
            return $this->createFromUpsert($staff_id, $data);
        }

        if ($staff->role_id !== null) {
            return $this->duplicateRefError($staff_id);
        }

        $staff->status = 1;
        $this->applyPayload($staff, $data);
        $staff->save();

        return ApiResponse::success($this->present($staff));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createFromUpsert(string $refId, array $data): JsonResponse
    {
        $staff = new Staff();
        $staff->external_ref_id = $refId;
        $staff->role_id = null;
        $staff->status = 1;
        $staff->created_by = null;
        $this->applyPayload($staff, $data);

        try {
            $staff->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // Dua permintaan PUT dengan external_ref_id baru yang sama,
            // nyaris bersamaan: keduanya sama-sama tidak menemukan baris di
            // atas, lalu unique index menolak yang kalah cepat. Perlakukan
            // sebagai upsert terhadap baris yang barusan dibuat request lain.
            $existing = Staff::where('external_ref_id', $refId)->first();

            if ($existing === null) {
                throw $e;
            }

            if ($existing->role_id !== null) {
                return $this->duplicateRefError($refId);
            }

            $existing->status = 1;
            $this->applyPayload($existing, $data);
            $existing->save();

            return ApiResponse::success($this->present($existing));
        }

        return ApiResponse::success($this->present($staff), [], 201);
    }

    /**
     * DELETE /api/external/v1/master/staff/{staff_id}
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
     * PATCH /api/external/v1/master/staff/connect
     *
     * Sama semantik dengan /master/sales/connect: staff_id di body adalah id
     * internal Pegasus, map_staff_id adalah rujukan eksternal yang mau
     * dipasang ke staf yang sudah ada (dibuat lewat halaman admin dengan
     * role_id NULL — staf yang punya role tetap TIDAK terjangkau di sini,
     * lihat isManagedStaff()).
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
            return $this->connectFailure($staffId, $rawMapStaffId, ErrorCatalog::VALIDATION_FAILED, 'map_staff_id tidak boleh kosong.');
        }

        $staff = Staff::find($staffId);

        if ($staff === null || ! $this->isManagedStaff($staff)) {
            return $this->connectFailure(
                $staffId,
                $mapStaffId,
                ErrorCatalog::NOT_FOUND,
                'Staf dengan id '.$staffId.' tidak ditemukan atau bukan staf yang dikelola endpoint ini.',
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
     * @return array<string, array<int, mixed>>
     */
    private function profileRules(): array
    {
        return [
            'nama_depan' => ['required', 'string', 'max:120'],
            'nama_belakang' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'alamat' => ['nullable', 'string'],
        ];
    }

    private function applyPayload(Staff $staff, array $data): void
    {
        $namaDepan = trim($data['nama_depan']);
        $namaBelakang = trim((string) ($data['nama_belakang'] ?? ''));

        $staff->staff_name = $namaBelakang !== '' ? $namaDepan.' '.$namaBelakang : $namaDepan;
        $staff->staff_email = $data['email'] ?? null;
        $staff->staff_address = $data['alamat'] ?? null;
    }

    /**
     * Staf dikelola endpoint ini kalau statusnya aktif DAN role_id-nya
     * NULL — lihat catatan kelas. Staf yang punya role apa pun (termasuk
     * Sales) tidak pernah terjangkau lewat endpoint ini.
     */
    private function isManagedStaff(Staff $staff): bool
    {
        return (int) $staff->status === 1 && $staff->role_id === null;
    }

    private function findManagedByRef(string $refId): ?Staff
    {
        $staff = Staff::where('external_ref_id', $refId)->first();

        return ($staff !== null && $this->isManagedStaff($staff)) ? $staff : null;
    }

    /* ------------------------------------------------------------------ */
    /* Respons                                                             */
    /* ------------------------------------------------------------------ */

    /**
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
        ];
    }

    /**
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
            ErrorCatalog::NOT_FOUND,
            'Staf dengan staff_id (external_ref_id) "'.$refId.'" tidak ditemukan.',
            404,
        );
    }

    private function duplicateRefError(string $refId): JsonResponse
    {
        return ApiResponse::error(
            ErrorCatalog::DUPLICATE_REF_ID,
            'staff_id (external_ref_id) "'.$refId.'" sudah dipakai staf lain.',
            422,
        );
    }
}
