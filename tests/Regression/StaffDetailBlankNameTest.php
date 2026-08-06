<?php

namespace Tests\Regression;

use App\Models\Staff;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: `resources/views/Backoffice/User/staffDetails.blade.php` (the "Detail Staff" page,
 * `GET /staffDetail/{id}`) read `$data["staff_first_name"]`/`$data["staff_last_name"]` for the
 * "Nama" field and `$data["staff_position"]` for "Jabatan" -- neither key exists on `staffs`
 * (confirmed via `Schema::getColumnListing('staffs')`: only a single combined `staff_name`
 * column, no first/last split, no `staff_position` column at all). Both fields were undefined
 * on every load, showing a blank name and blank position for every staff member.
 *
 * FIXED 2026-08-07: "Nama" now reads `staff_name` directly (the real, always-populated column).
 * "Jabatan" now reads `role_name`, which `Staff::getStaff()` already attaches to every row from
 * the `roles` table -- the same source `insertStaff.js`'s edit-mode prefill already uses for its
 * own "Jabatan" display, so this isn't a new data source, just wiring the detail page to the one
 * that already exists.
 *
 * NOT fixed here, left as a documented gap (no real column exists to back these, would be
 * guessing at schema rather than fixing a bug): Foto (`staff_image`), Departemen
 * (`staff_departement`), Tanggal Lahir (`staff_birthdate`), Tanggal Bergabung (`staff_join_date`)
 * -- see the KNOWN_ISSUES.md entry for this fix for the full list.
 */
class StaffDetailBlankNameTest extends TestCase
{
    use ActingAsStaff;

    private function aStaffWithRole(): Staff
    {
        $staff = Staff::where('status', 1)
            ->whereNotNull('role_id')
            ->orderBy('staff_id')
            ->first();

        $this->assertNotNull($staff, 'need at least 1 active staff with a role for this test');

        return $staff;
    }

    public function test_detail_staff_page_shows_the_staffs_actual_name(): void
    {
        $staff = $this->aStaffWithRole();
        $this->actingAsSuperAdminStaff();

        $response = $this->get('/staffDetail/' . $staff->staff_id);

        $response->assertStatus(200);
        $response->assertSee($staff->staff_name, false);
    }

    public function test_detail_staff_page_shows_the_staffs_role_as_jabatan(): void
    {
        $staff = $this->aStaffWithRole();
        $this->actingAsSuperAdminStaff();

        // role_name isn't a raw Staff column -- it's attached by Staff::getStaff() from a
        // roles join, the same source the page itself (via UserController::staffDetail()) uses.
        $withRole = (new Staff())->getStaff(['staff_id' => $staff->staff_id])[0];
        $this->assertNotEmpty($withRole->role_name, 'fixture staff needs a resolvable role_name');

        $response = $this->get('/staffDetail/' . $staff->staff_id);

        $response->assertStatus(200);
        $response->assertSee($withRole->role_name, false);
    }
}
