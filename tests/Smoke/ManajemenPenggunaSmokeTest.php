<?php

namespace Tests\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * "Manajemen Pengguna" sidebar group (cdocs/docs/modules.md §6): Pengguna
 * (Staff), Peran & Perizinan (Role).
 */
class ManajemenPenggunaSmokeTest extends TestCase
{
    use ActingAsStaff;

    public static function pageProvider(): array
    {
        return [
            'staff (Pengguna)' => ['/staff', 'Pengguna'],
            'role (Peran & Perizinan)' => ['/role', 'Peran & Perizinan'],
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
