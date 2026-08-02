<?php

namespace Tests\Regression;

use App\Models\Staff;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: `SettingController::Profiles()` (`SettingController.php:12`) hardcodes
 * `(new Staff())->getStaff(["staff_id" => 1])` instead of reading `session('user')->staff_id` —
 * every logged-in user sees staff #1's data on `/profiles`, not their own. Because the real
 * frontend (`Profiles.js:70`, `param.staff_id = data.staff_id`) echoes that same hardcoded id
 * back on save, this is not just a display bug: a successful save would overwrite staff #1's
 * account regardless of who is actually logged in — potentially the admin/owner account.
 *
 * That consequence is currently MASKED by a second, separate active bug: `Staff::updateStaff()`
 * (`Staff.php:133`) unconditionally reads `$data["staff_username"]`, a field the real Profil form
 * (`Profiles.js`) never sends (it isn't even on the form) — every real save attempt crashes with a
 * 500 before the cross-account overwrite can ever happen. This test characterizes BOTH facts:
 * the hardcode (safely observable via GET) and the crash (safely reproducible — a normal
 * ErrorException, not a `dd()`/`die()` that would abort the test process).
 *
 * See cdocs/docs/flows/pengaturan/FLOW.md and cdocs/testing/KNOWN_ISSUES.md for the full writeup.
 * Confirmed 2026-08-02, deliberately deferred per this project's "queue bugs, don't fix" policy.
 */
class ProfilesHardcodedStaffIdTest extends TestCase
{
    use ActingAsStaff;

    public function test_profiles_page_never_shows_the_actual_logged_in_users_own_data(): void
    {
        // staff_id=1 in the seed snapshot happens to be status=0 (inactive) — Staff::getStaff()
        // filters status=1, so the hardcoded query returns nothing rather than a real record here.
        // That's still the bug (any OTHER staff_id is what the logged-in user actually wants to
        // see), just not directly demonstrable by asserting staff #1's own data appears. Instead,
        // assert the practically important consequence: the actually-logged-in user's own real
        // data is never shown, no matter who is logged in.
        $otherStaff = Staff::where('status', 1)
            ->where('staff_id', '!=', 1)
            ->whereNotNull('staff_email')
            ->where('staff_email', '!=', '')
            ->firstOrFail();
        $this->actingAsSuperAdminStaff(['staff_id' => $otherStaff->staff_id]);

        $response = $this->get('/profiles');
        $response->assertStatus(200);
        $response->assertDontSee($otherStaff->staff_email, false);
    }

    public function test_saving_profil_with_the_real_forms_payload_shape_crashes_before_any_overwrite(): void
    {
        $this->actingAsSuperAdminStaff();

        $staffOne = Staff::findOrFail(1);
        $originalName = $staffOne->staff_name;

        // Mirrors Profiles.js's real `param` object exactly (public/Custom_js/Backoffice/Settings/
        // Profiles.js:44-70) — deliberately omits `staff_username`, which the real form never
        // sends, and hardcodes `staff_id => 1` exactly like the real page always does.
        $response = $this->post('/updateStaff', [
            'staff_first_name' => 'Overwrite',
            'staff_last_name' => 'Attempt',
            'staff_email' => 'overwrite-attempt@example.test',
            'staff_phone' => '081200000000',
            'staff_birthdate' => '1990-01-01',
            'staff_gender' => 'L',
            'state_id' => null,
            'city_id' => null,
            'staff_blood' => null,
            'staff_password' => '',
            'staff_confirm' => '',
            'staff_position' => $staffOne->role_id,
            'staff_address' => $staffOne->staff_address,
            'staff_id' => 1,
        ]);

        $response->assertStatus(500);

        $staffOne->refresh();
        $this->assertSame($originalName, $staffOne->staff_name, 'the crash happens before save() — staff #1 must be untouched, not partially overwritten');
    }
}
