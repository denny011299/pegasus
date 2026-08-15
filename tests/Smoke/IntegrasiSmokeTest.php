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
 */
class IntegrasiSmokeTest extends TestCase
{
    use ActingAsStaff;

    public static function pageProvider(): array
    {
        return [
            'synchronization' => ['/synchronization', 'Sinkronisasi'],
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
}
