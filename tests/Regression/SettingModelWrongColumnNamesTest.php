<?php

namespace Tests\Regression;

use App\Models\Setting;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Bug: App\Models\Setting::getSetting()/updateSetting() referenced a
 * `setting_name` column and filtered `where('status', '=', 1)` — neither
 * exists on the real `settings` table (`setting_key`, no `status` column at
 * all). This was pre-existing, known debt: app/ExternalApi/Support/SettingStore.php
 * already documented the exact mismatch and deliberately bypassed the model
 * to avoid it. The /settings admin page 500'd unconditionally as a result.
 *
 * Fixed 2026-08-01 by renaming the column references in Setting.php to
 * `setting_key` and dropping the nonexistent `status` filter (there was
 * nothing to filter on — every row is implicitly "active").
 */
class SettingModelWrongColumnNamesTest extends TestCase
{
    use ActingAsStaff;

    public function test_settings_page_loads_for_super_admin(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->get('/settings')->assertStatus(200);
    }

    public function test_get_and_update_setting_round_trip(): void
    {
        (new Setting())->updateSetting('app_test_regression_key', 'hello');

        $values = (new Setting())->getSetting(['select' => ['app_test_regression_key']]);

        $this->assertSame('hello', $values['app_test_regression_key']);
    }
}
