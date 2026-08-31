<?php

namespace Tests\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Internal dev-only pages added in fase 2 (routes/web.php §60-64): the
 * changelog viewer and the deployment self-check. They are not in the sidebar
 * (see cdocs/docs/modules.md §9.2) and deliberately NOT gated by
 * check.access/role_access — routes/web.php says so in a comment. checkLogin
 * is their only protection: any logged-in staff account reaches them, and
 * discovery is meant to come from reading routes/web.php.
 *
 * That makes them worth pinning precisely, because "no permission gate" is a
 * decision rather than an oversight, and the two directions fail differently:
 * if someone later wraps them in check.access the no-permission test breaks
 * (a deliberate change should show up as an edited test), and if checkLogin
 * ever falls off the guest test breaks (a genuine exposure — these pages
 * render deployment state and commit history).
 *
 * The sibling /deploy/* routes are token-gated instead of session-gated and
 * are covered separately in tests/Feature/DeployControllerTest.php.
 */
class SystemDevPagesSmokeTest extends TestCase
{
    use ActingAsStaff;

    public static function pageProvider(): array
    {
        return [
            'system/changelog' => ['/system/changelog'],
            'system/deployment-check' => ['/system/deployment-check'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_page_loads_for_super_admin(string $uri): void
    {
        $this->actingAsSuperAdminStaff();

        $this->get($uri)->assertStatus(200);
    }

    /**
     * The documented intent: no check.access middleware, so zero permissions
     * is still enough. Not a 403 — that would mean someone added a gate.
     */
    #[DataProvider('pageProvider')]
    public function test_page_loads_for_any_logged_in_staff_without_permissions(string $uri): void
    {
        $this->actingAsStaffWithNoAccess();

        $this->get($uri)->assertStatus(200);
    }

    /** checkLogin is the only thing standing between these pages and the public. */
    #[DataProvider('pageProvider')]
    public function test_page_redirects_a_guest_to_login(string $uri): void
    {
        $this->get($uri)->assertRedirect('/login');
    }
}
