<?php

namespace Tests\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * "Integrasi" sidebar group (cdocs/docs/modules.md §7): Pusat Sinkronisasi,
 * Aplikasi Eksternal, Dokumentasi API Eksternal, Log API Eksternal. This is
 * the admin UI for the External API platform, not the third-party-facing
 * /api/external/v1 endpoints themselves (see the external-api-endpoint
 * skill / pegasus-external-api-platform memory for those).
 *
 * /api-docs is handled outside the generic provider on purpose: it is the
 * deliberately PUBLIC twin of /externalApiDocumentation (routes/web.php §28-35),
 * sitting behind throttle:60,1 with no checkLogin and no check.access at all.
 * The provider's three cases all assume a permission gate, so asserting "200
 * for a guest" there would be meaningless — the interesting property is the
 * contrast with its admin twin, which is what the dedicated tests below pin.
 */
class IntegrasiSmokeTest extends TestCase
{
    use ActingAsStaff;

    public static function pageProvider(): array
    {
        return [
            'synchronization' => ['/synchronization', 'Sinkronisasi'],
            'externalApiStatus' => ['/externalApiStatus', 'Status API Eksternal'],
            'externalApplication' => ['/externalApplication', 'Aplikasi Eksternal'],
            'externalApiDocumentation' => ['/externalApiDocumentation', 'Dokumentasi API Eksternal'],
            'externalApiLog' => ['/externalApiLog', 'Log API Eksternal'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_page_loads_for_super_admin(string $uri, string $module): void
    {
        $this->actingAsSuperAdminStaff();

        $this->get($uri)->assertStatus(200);
    }

    #[DataProvider('pageProvider')]
    public function test_page_blocks_staff_without_permission(string $uri, string $module): void
    {
        $this->actingAsStaffWithNoAccess();

        $this->get($uri)->assertStatus(403);
    }

    #[DataProvider('pageProvider')]
    public function test_page_loads_for_staff_with_only_that_permission(string $uri, string $module): void
    {
        $this->actingAsStaffWithOnlyPermission($module);

        $this->get($uri)->assertStatus(200);
    }

    /**
     * The whole point of /api-docs: a third-party integrator with no account
     * can read the endpoint reference. If this ever starts redirecting to
     * /login, the public docs have silently been taken offline.
     */
    public function test_public_api_docs_loads_for_a_guest(): void
    {
        $this->get('/api-docs')->assertStatus(200);
    }

    /**
     * Same page, logged in but with zero permissions — proves the route
     * really has no check.access gate rather than merely happening to pass
     * for whoever the previous test's session belonged to.
     */
    public function test_public_api_docs_loads_for_staff_without_any_permission(): void
    {
        $this->actingAsStaffWithNoAccess();

        $this->get('/api-docs')->assertStatus(200);
    }

    /**
     * The contrast that makes the pair meaningful: the admin twin renders the
     * same documentation but stays permission-gated. A regression that made
     * /externalApiDocumentation public would otherwise go unnoticed, since
     * every other test here only ever asserts the positive direction.
     */
    public function test_the_admin_docs_twin_is_still_gated_for_a_guest(): void
    {
        $this->get('/externalApiDocumentation')->assertRedirect('/login');
    }

    /**
     * renderApiDocumentation() aborts 404 on an unknown group slug — both for
     * a group that does not exist and for one that exists but is not
     * published publicly (filterPublicGroup() returning null). Asserting the
     * unknown-slug case keeps that guard from decaying into a 500.
     */
    public function test_public_api_docs_404s_on_an_unknown_group(): void
    {
        $this->get('/api-docs/kelompok-yang-tidak-ada')->assertStatus(404);
    }
}
