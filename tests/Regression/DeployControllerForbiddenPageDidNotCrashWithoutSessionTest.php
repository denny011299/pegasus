<?php

namespace Tests\Regression;

use Tests\TestCase;

/**
 * Confirmed, fixed 2026-08-09 (found while adding test coverage for the DeployController module,
 * #34, which shipped with no tests): DeployController's token guard used `abort(403)`, which
 * renders resources/views/errors/403.blade.php through the app's normal admin layout. That
 * layout's header partial reads `Session::get('user')["role_name"]` unconditionally
 * (layout/partials/header.blade.php:743) — safe everywhere else in the app because check.access
 * (which is what normally produces a 403) always runs after checkLogin, so a session always
 * exists by then. DeployController is deliberately outside checkLogin (deploy can happen before
 * any session is valid), making it the first place that can 403 with NO session at all — which
 * crashed with a 500 ("Trying to access array offset on null") instead of showing 403.
 *
 * Fixed by having DeployController's guard()/requireConfirmation() return a plain text response
 * directly instead of going through abort()/the shared admin layout — see
 * App\Http\Controllers\DeployController. Full behavioral coverage lives in
 * tests/Feature/DeployControllerTest.php; this file is the permanent "never delete" record per
 * the Regression folder's own rule.
 */
class DeployControllerForbiddenPageDidNotCrashWithoutSessionTest extends TestCase
{
    public function test_deploy_route_without_a_token_returns_403_without_a_session_instead_of_crashing(): void
    {
        putenv('DEPLOY_TOKEN');
        unset($_ENV['DEPLOY_TOKEN'], $_SERVER['DEPLOY_TOKEN']);

        // No session('user') at all — the exact state that used to 500.
        $response = $this->get('/deploy/migrate');

        $response->assertStatus(403);
    }
}
