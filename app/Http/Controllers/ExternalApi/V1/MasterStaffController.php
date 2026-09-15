<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Errors\ErrorCatalog;
use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ExternalApi\V1\Concerns\HandlesListQueryParams;
use App\Models\Role;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Data master staf non-Sales untuk sistem eksternal (GitHub #177 / PMO task #4).
 *
 * MasterSalesController hanya menyentuh staf berperan "Sales" (lihat komentar
 * kelas itu) — hard-coded, sengaja begitu. Endpoint ini adalah kelas TERPISAH
 * (bukan perluasan MasterSalesController) untuk staf yang perannya BUKAN
 * Sales, karena syarat "peran mana yang dikelola" berbeda: di sini daftar
 * peran yang diizinkan datang dari config('externalapi.staff_sync_roles'),
 * dicocokkan PERSIS (bukan LIKE seperti "%sales%"), supaya menambah peran
 * baru cukup mengubah config, tanpa risiko menangkap peran lain yang
 * namanya kebetulan mengandung kata yang sama.
 *
 * BELUM DIKONFIRMASI PM: hanya "Owner" yang sudah disepakati per issue #177.
 * Daftar peran lain menyusul lewat config, bukan lewat perubahan endpoint.
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
     * Sama seperti /master/sales, tapi mengambil staf dari peran-peran di
     * config('externalapi.staff_sync_roles') alih-alih peran ber-"sales".
     */
    public function index(Request $request): JsonResponse
    {
        return $this->respondList(
            (new Staff())->getStaffByRolesForExternalApi($this->syncRoles()),
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
                'role' => 'roles.role_name',
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
     * body.role wajib diisi dan harus persis salah satu nama peran di
     * config('externalapi.staff_sync_roles') (tanpa peduli besar/kecil
     * huruf) — endpoint ini tidak menebak peran seperti salesRoleId(),
     * karena bisa ada lebih dari satu peran yang dikelola.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCreatePayload($request);
        $refId = (string) $data['staff_id'];

        if (Staff::where('external_ref_id', $refId)->exists()) {
            return $this->duplicateRefError($refId);
        }

        $role = $this->resolveRole($data['role']);

        $staff = new Staff();
        $staff->external_ref_id = $refId;
        $staff->role_id = $role->role_id;
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
     * {staff_id} adalah external_ref_id, sama seperti /master/sales. Tidak
     * mengubah role — role hanya ditentukan saat POST/connect, sama seperti
     * /master/sales tidak membiarkan PUT mengubah role sales.
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
     * dipasang ke staf yang sudah ada (dibuat lewat halaman admin, sudah
     * berperan salah satu dari staff_sync_roles).
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
            'role' => ['required', 'string'],
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
     * @return array<int, string>
     */
    private function syncRoles(): array
    {
        return (array) config('externalapi.staff_sync_roles', []);
    }

    /**
     * body.role harus cocok PERSIS (tanpa peduli besar/kecil huruf) dengan
     * salah satu peran di config('externalapi.staff_sync_roles') DAN harus
     * ada di tabel roles — dijawab validation_failed kalau tidak, karena ini
     * kesalahan pemanggil (peran yang dikirim salah/belum didukung), bukan
     * kegagalan server seperti salesRoleId().
     */
    private function resolveRole(string $roleName): Role
    {
        $allowed = array_map('strtolower', $this->syncRoles());

        if (! in_array(strtolower($roleName), $allowed, true)) {
            abort(ApiResponse::error(
                ErrorCatalog::VALIDATION_FAILED,
                'role "'.$roleName.'" belum didukung endpoint ini.',
                422,
            ));
        }

        $role = Role::query()->whereRaw('LOWER(role_name) = ?', [strtolower($roleName)])->first();

        if ($role === null) {
            abort(ApiResponse::error(
                ErrorCatalog::VALIDATION_FAILED,
                'role "'.$roleName.'" tidak ditemukan di data master peran.',
                422,
            ));
        }

        return $role;
    }

    /**
     * Staf dikelola endpoint ini kalau statusnya aktif DAN perannya persis
     * salah satu dari config('externalapi.staff_sync_roles').
     */
    private function isManagedStaff(Staff $staff): bool
    {
        if ((int) $staff->status !== 1 || $staff->role_id === null) {
            return false;
        }

        $allowed = array_map('strtolower', $this->syncRoles());

        return Role::query()
            ->where('role_id', $staff->role_id)
            ->whereRaw('LOWER(role_name) IN ('.implode(',', array_fill(0, count($allowed), '?')).')', $allowed)
            ->exists();
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
            'role' => (string) $staff->role_name,
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
