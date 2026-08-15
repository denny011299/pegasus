<?php

namespace Tests\Regression;

use Illuminate\Support\Facades\Session;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-04): Logout used to be a bare `<a href="/login">` — grepping the whole
 * codebase found no `Session::forget()`/`flush()`/`invalidate()` call anywhere. A "logged out"
 * session stayed fully valid (including stale `role_access`, if the user's role was changed after
 * login) until natural expiry, not until the user actually logged out.
 *
 * Fix: a new `GET /logout` route (`GeneralController::logout()`) calls `Session::invalidate()`
 * (clears all session data and regenerates the session id) and `Session::regenerateToken()`, then
 * redirects to `/login`. The header/sidebar "Logout" links now point at `route('logout')` instead
 * of a plain `/login` link.
 *
 * See cdocs/testing/KNOWN_ISSUES.md's "Logout never clears the session" entry.
 */
class LogoutInvalidatesSessionTest extends TestCase
{
    use ActingAsStaff;

    public function test_logout_clears_the_session_so_authenticated_routes_are_blocked_afterward(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->assertTrue(Session::has('user'), 'the test helper must have put a session user in place');
        $this->get('/')->assertOk();

        $logoutResponse = $this->get('/logout');
        $logoutResponse->assertRedirect('/login');

        $this->assertFalse(Session::has('user'), 'logout must remove the session user');

        // A previously-authenticated route must now redirect to /login, same as never having
        // logged in at all.
        $this->get('/')->assertRedirect('/login');
    }

    public function test_logout_is_reachable_from_the_authenticated_route_group(): void
    {
        $this->actingAsSuperAdminStaff();

        // Logging out itself must not be blocked by the very session state it's about to clear.
        $this->get('/logout')->assertRedirect('/login');
    }
}
