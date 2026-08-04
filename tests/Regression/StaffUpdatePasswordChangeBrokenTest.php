<?php

namespace Tests\Regression;

use App\Models\Staff;
use Illuminate\Support\Facades\Hash;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-05): `Staff::updateStaff()` used to compare the submitted `staff_password`
 * (the form's NEW password field, per `insertStaff.blade.php`'s "Masukkan Kata Sandi" input) against
 * the record's EXISTING hash via `Hash::check()`, aborting the ENTIRE update (not just the password)
 * if they didn't match:
 *
 *   if (!empty($data["staff_password"])) {
 *       if (!Hash::check($data["staff_password"], $t->staff_password)) {
 *           return -1;
 *       }
 *   }
 *
 * Typing an intended NEW password almost always fails this check (it's compared against the OLD
 * hash), discarding every other field change in the same request too. Even in the one case where
 * it passed (re-typing the CURRENT password unchanged), the password hash itself was never
 * reassigned anywhere in the method — changing a staff member's password through this endpoint was
 * unreachable.
 *
 * Fix: blank/omitted `staff_password` now leaves the password untouched (as before); a non-empty
 * value is hashed and assigned directly (`Hash::make()`), matching `insertStaff()`'s own pattern,
 * with no comparison against the old hash and no early abort.
 */
class StaffUpdatePasswordChangeBrokenTest extends TestCase
{
    use ActingAsStaff;

    private function pickStaffWithEmail(): Staff
    {
        return Staff::where('status', 1)
            ->whereNotNull('staff_email')
            ->where('staff_email', '!=', '')
            ->orderBy('staff_id')
            ->firstOrFail();
    }

    private function updatePayload(Staff $staff, string $password, string $email): array
    {
        $names = explode(' ', $staff->staff_name, 2);

        return [
            'staff_id' => $staff->staff_id,
            'staff_first_name' => $names[0],
            'staff_last_name' => $names[1] ?? '-',
            'staff_email' => $email,
            'staff_phone' => $staff->staff_phone,
            'staff_position' => $staff->role_id,
            'staff_address' => $staff->staff_address,
            'staff_username' => $staff->staff_username,
            'staff_password' => $password,
        ];
    }

    public function test_submitting_a_new_password_actually_changes_the_hash_instead_of_aborting_the_update(): void
    {
        $this->actingAsSuperAdminStaff();

        $staff = $this->pickStaffWithEmail();
        $newPassword = 'RegressionTestPass1!';

        $response = $this->post('/updateStaff', $this->updatePayload($staff, $newPassword, 'regression-pw-test-1@example.test'));

        $response->assertOk();
        $this->assertNotEquals('-1', trim((string) $response->getContent()), 'a genuinely new password must not abort the update with -1');

        $staff->refresh();
        $this->assertSame('regression-pw-test-1@example.test', $staff->staff_email, 'the rest of the update must have been saved, not discarded');
        $this->assertTrue(Hash::check($newPassword, $staff->staff_password), 'the new password must actually be hashed and stored');
    }

    public function test_leaving_password_blank_updates_other_fields_without_touching_the_password(): void
    {
        $this->actingAsSuperAdminStaff();

        $staff = $this->pickStaffWithEmail();
        $knownPassword = 'RegressionTestPass2!';

        // First set a known password so we have something concrete to assert stays unchanged.
        $this->post('/updateStaff', $this->updatePayload($staff, $knownPassword, 'regression-pw-test-2a@example.test'))
            ->assertOk();
        $staff->refresh();
        $hashAfterFirstUpdate = $staff->staff_password;
        $this->assertTrue(Hash::check($knownPassword, $hashAfterFirstUpdate));

        // Second update: blank password, different email.
        $response = $this->post('/updateStaff', $this->updatePayload($staff, '', 'regression-pw-test-2b@example.test'));
        $response->assertOk();

        $staff->refresh();
        $this->assertSame('regression-pw-test-2b@example.test', $staff->staff_email, 'other fields must still update when password is left blank');
        $this->assertSame($hashAfterFirstUpdate, $staff->staff_password, 'leaving password blank must not touch the stored hash');
        $this->assertTrue(Hash::check($knownPassword, $staff->staff_password), 'the previously-set password must still work');
    }
}
