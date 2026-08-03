<?php

namespace Tests\Regression;

use App\Models\Staff;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: `SettingController::Profiles()` hardcoded `(new Staff())->getStaff(["staff_id" => 1])`
 * instead of reading `session('user')->staff_id` — every logged-in user saw staff #1's data on
 * `/profiles`, not their own. It also never indexed `[0]` on the returned Collection the way
 * `UserController::ViewUpdateStaff()` does, so `Profiles.js`'s `var data = @json($data)` was a
 * JSON *array*, not an object — `data.staff_first_name` etc. were all `undefined` client-side
 * regardless of which staff_id was queried. Because the real frontend echoed that same (wrong)
 * `staff_id` back on save via the SHARED `/updateStaff` endpoint (also gated by `Pengguna|edit`,
 * not `Profil|edit`), a successful save would have overwritten staff #1's account regardless of
 * who was actually logged in — masked until now by a separate crash: `Staff::updateStaff()`
 * unconditionally reads `$data["staff_username"]`, a field the real Profil form never sends.
 *
 * FIXED 2026-08-03: `Profiles()` now reads the session's own staff_id and indexes `[0]`. A new,
 * dedicated `SettingController::updateProfile()` / `POST /updateProfile` (gated by
 * `check.access:Profil|edit`, not the shared `Pengguna|edit`-gated `/updateStaff`) always forces
 * `staff_id`, `staff_username`, and `staff_position` (role) from the session/existing record —
 * never from the request — so Profil can change personal info but never the account being edited,
 * the login username, or the role/permissions. `/updateStaff` and `Staff::updateStaff()` itself are
 * untouched, so the real admin "Manajemen Pengguna → Edit Staff" flow (which does send
 * staff_username) still works exactly as before.
 *
 * See cdocs/docs/flows/pengaturan/FLOW.md and cdocs/testing/KNOWN_ISSUES.md for the full writeup.
 */
class ProfilesHardcodedStaffIdTest extends TestCase
{
    use ActingAsStaff;

    /** @return array{0: Staff, 1: Staff} two distinct active staff with a real email */
    private function twoDistinctStaff(): array
    {
        $staff = Staff::where('status', 1)
            ->whereNotNull('staff_email')
            ->where('staff_email', '!=', '')
            ->orderBy('staff_id')
            ->take(2)
            ->get();

        $this->assertGreaterThanOrEqual(2, $staff->count(), 'need at least 2 active staff with an email for this test');

        return [$staff[0], $staff[1]];
    }

    public function test_profiles_page_now_shows_the_actual_logged_in_users_own_data(): void
    {
        [$self] = $this->twoDistinctStaff();
        $this->actingAsSuperAdminStaff(['staff_id' => $self->staff_id]);

        $response = $this->get('/profiles');
        $response->assertStatus(200);
        $response->assertSee($self->staff_email, false);
    }

    public function test_update_profile_saves_only_the_logged_in_users_own_account(): void
    {
        [$self, $other] = $this->twoDistinctStaff();
        $this->actingAsSuperAdminStaff(['staff_id' => $self->staff_id]);

        $otherOriginalName = $other->staff_name;

        // Mirrors Profiles.js's real payload shape (public/Custom_js/Backoffice/Settings/
        // Profiles.js) — no staff_id, staff_username, or staff_position field at all, since the
        // real form never sends them any more; the server forces them from the session.
        $response = $this->post('/updateProfile', [
            'staff_first_name' => 'Updated',
            'staff_last_name' => 'Own Name',
            'staff_email' => 'updated-own-email@example.test',
            'staff_phone' => '081200000001',
            'staff_birthdate' => '1990-01-01',
            'staff_gender' => 'L',
            'state_id' => null,
            'city_id' => null,
            'staff_blood' => null,
            'staff_password' => '',
            'staff_confirm' => '',
            'staff_address' => 'Own Address',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);

        $self->refresh();
        $this->assertSame('Updated Own Name', $self->staff_name, 'the logged-in user\'s own record is updated');
        $this->assertSame('updated-own-email@example.test', $self->staff_email);

        $other->refresh();
        $this->assertSame($otherOriginalName, $other->staff_name, 'a different staff\'s record must be completely untouched');
    }

    public function test_update_profile_ignores_a_tampered_staff_id_pointing_at_a_different_account(): void
    {
        [$self, $victim] = $this->twoDistinctStaff();
        $this->actingAsSuperAdminStaff(['staff_id' => $self->staff_id]);

        $victimOriginalName = $victim->staff_name;

        $response = $this->post('/updateProfile', [
            'staff_first_name' => 'Attempted',
            'staff_last_name' => 'Overwrite',
            'staff_email' => 'attempted-overwrite@example.test',
            'staff_phone' => '081200000002',
            'staff_birthdate' => '1990-01-01',
            'staff_gender' => 'L',
            'staff_password' => '',
            'staff_confirm' => '',
            'staff_address' => 'Some Address',
            // Tampered: a malicious client trying to target someone else's account.
            'staff_id' => $victim->staff_id,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);

        $victim->refresh();
        $this->assertSame($victimOriginalName, $victim->staff_name, 'a tampered staff_id in the payload must NOT redirect the update to another account');

        $self->refresh();
        $this->assertSame('Attempted Overwrite', $self->staff_name, 'the update must always land on the session\'s own account instead');
    }

    public function test_update_profile_ignores_a_tampered_role_and_keeps_the_existing_username(): void
    {
        [$self] = $this->twoDistinctStaff();
        $this->actingAsSuperAdminStaff(['staff_id' => $self->staff_id]);

        $originalRoleId = $self->role_id;
        $originalUsername = $self->staff_username;
        $bogusRoleId = ((int) Staff::max('role_id')) + 1000;

        $response = $this->post('/updateProfile', [
            'staff_first_name' => 'No',
            'staff_last_name' => 'Escalation',
            'staff_email' => 'no-escalation@example.test',
            'staff_phone' => '081200000003',
            'staff_birthdate' => '1990-01-01',
            'staff_gender' => 'L',
            'staff_password' => '',
            'staff_confirm' => '',
            'staff_address' => 'Some Address',
            // Tampered: attempting to self-escalate role and rename the login username.
            'staff_position' => $bogusRoleId,
            'staff_username' => 'hijacked-username',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);

        $self->refresh();
        $this->assertSame($originalRoleId, $self->role_id, 'Profil must never be able to change its own role/permissions');
        $this->assertSame($originalUsername, $self->staff_username, 'Profil must never be able to change its own login username');
    }

    public function test_a_staff_with_only_profil_permission_can_save_their_own_profile(): void
    {
        $staff = $this->actingAsStaffWithOnlyPermission('Profil', ['view', 'edit']);

        $response = $this->post('/updateProfile', [
            'staff_first_name' => 'Scoped',
            'staff_last_name' => 'Permission',
            'staff_email' => 'scoped-permission@example.test',
            'staff_phone' => '081200000004',
            'staff_birthdate' => '1990-01-01',
            'staff_gender' => 'L',
            'staff_password' => '',
            'staff_confirm' => '',
            'staff_address' => 'Some Address',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);

        $this->assertDatabaseHas('staffs', ['staff_id' => $staff->staff_id, 'staff_name' => 'Scoped Permission']);

        // The shared admin endpoint must still require the separate Pengguna|edit permission —
        // Profil-only access must not also grant the ability to edit ANY staff via /updateStaff.
        $blockedResponse = $this->post('/updateStaff', [
            'staff_first_name' => 'Should',
            'staff_last_name' => 'Be Blocked',
            'staff_email' => 'blocked@example.test',
            'staff_phone' => '081200000005',
            'staff_position' => $staff->role_id,
            'staff_address' => 'Some Address',
            'staff_username' => $staff->staff_username,
            'staff_password' => '',
            'staff_id' => $staff->staff_id,
        ]);
        $blockedResponse->assertStatus(403);
    }
}
