<?php

namespace Tests\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * "Pengaturan" sidebar group (cdocs/docs/modules.md §8): Profil, Pengaturan.
 * Currently commented out of sidebar.blade.php so it's not reachable via the
 * menu, but the routes/permissions are still live — smoke-tested anyway so a
 * regression here isn't invisible just because the link is hidden.
 */
class PengaturanSmokeTest extends TestCase
{
    use ActingAsStaff;

    public static function pageProvider(): array
    {
        return [
            'profiles (Profil)' => ['/profiles', 'Profil'],
            'settings (Pengaturan)' => ['/settings', 'Pengaturan'],
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
