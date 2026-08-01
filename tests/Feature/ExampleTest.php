<?php

namespace Tests\Feature;

use Tests\Support\ActingAsStaff;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use ActingAsStaff;

    /**
     * Dashboard sits behind checkLogin (Session::get('user')), so a guest
     * request redirects to /login instead of rendering — there is no
     * unauthenticated 200 response on this app.
     */
    public function test_guest_is_redirected_away_from_dashboard(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_staff_can_load_dashboard(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
