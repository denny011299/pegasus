<?php

namespace Tests\Regression;

use App\Support\AppMaintenance;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\ActingAsExternalApiClient;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Ported from main `a0a7019` (rubenyw, no tests added upstream): a global toggle
 * (`APP_MAINTENANCE_MODE` in `.env` or `php artisan app:maintenance on|off|status`) that logs out
 * every session and blocks all `web`-group access, via `App\Http\Middleware\EnforceAppMaintenance`
 * appended in `bootstrap/app.php`.
 *
 * fase2-specific concern this suite adds (no counterpart on main, which has no External API
 * platform): the middleware is registered ONLY in the `web` middleware group, never `api` --
 * confirmed here rather than assumed, since a global toggle silently also blocking third-party
 * integrations (External API consumers) would be a much bigger incident than a blocked admin
 * page. See routes/api.php's own docblock: External API routes are deliberately outside the web
 * session/middleware stack entirely.
 */
class AppMaintenanceModeTest extends TestCase
{
    use ActingAsStaff;
    use ActingAsExternalApiClient;

    protected function tearDown(): void
    {
        config(['maintenance.enabled' => false]);
        AppMaintenance::disableFileFlag();
        parent::tearDown();
    }

    public function test_web_requests_pass_through_normally_when_maintenance_is_off(): void
    {
        config(['maintenance.enabled' => false]);
        $this->actingAsSuperAdminStaff();

        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_a_normal_web_page_is_blocked_with_503_when_maintenance_is_on(): void
    {
        $this->actingAsSuperAdminStaff();
        config(['maintenance.enabled' => true]);

        $response = $this->get('/');

        $response->assertStatus(503);
        $response->assertViewIs('maintenance');
    }

    public function test_an_ajax_request_gets_a_json_503_instead_of_the_html_view(): void
    {
        $this->actingAsSuperAdminStaff();
        config(['maintenance.enabled' => true]);

        $response = $this->get('/', ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(503);
        $response->assertJson(['status' => -1, 'maintenance' => true]);
    }

    public function test_the_health_check_route_is_never_blocked(): void
    {
        config(['maintenance.enabled' => true]);

        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    public function test_maintenance_invalidates_the_active_session(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->assertNotNull(session('user'));

        config(['maintenance.enabled' => true]);
        $this->get('/');

        $this->assertNull(session('user'), 'the session must be logged out, not just refused access to the current page');
    }

    /**
     * fase2-specific: External API routes live in the `api` middleware group (routes/api.php),
     * never `web` -- EnforceAppMaintenance is only appended to `web` in bootstrap/app.php, so a
     * global maintenance toggle must NOT reach third-party integrations at all.
     */
    public function test_external_api_routes_are_not_affected_by_maintenance_mode(): void
    {
        config(['maintenance.enabled' => true]);

        $headers = $this->externalApiHeaders();
        $response = $this->get('/api/external/v1/master/units', $headers);

        $response->assertStatus(200);
        $this->assertFalse(
            str_contains((string) $response->getContent(), '"maintenance":true'),
            'External API must never see the maintenance response shape'
        );
    }

    public function test_artisan_command_toggles_the_file_flag(): void
    {
        AppMaintenance::disableFileFlag();
        $this->assertFalse(AppMaintenance::fileFlagEnabled());

        Artisan::call('app:maintenance', ['action' => 'on']);
        $this->assertTrue(AppMaintenance::fileFlagEnabled());
        $this->assertTrue(AppMaintenance::enabled());

        Artisan::call('app:maintenance', ['action' => 'off']);
        $this->assertFalse(AppMaintenance::fileFlagEnabled());
    }

    public function test_artisan_command_status_action_does_not_change_anything(): void
    {
        AppMaintenance::disableFileFlag();

        $exitCode = Artisan::call('app:maintenance', ['action' => 'status']);

        $this->assertSame(0, $exitCode);
        $this->assertFalse(AppMaintenance::fileFlagEnabled());
    }
}
