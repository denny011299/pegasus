<?php

namespace Tests\Regression;

use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Asisten AI hanya untuk role dengan role_access "Asisten AI"|view: route ditolak dan widget FAB
 * tidak dirender tanpa akses; superadmin (role_id -1) tetap bypass.
 */
class AiAssistantAccessTest extends TestCase
{
    use ActingAsStaff;

    public function test_permission_json_ids_for_ai_module_are_unique(): void
    {
        $rows = json_decode(file_get_contents(public_path('assets/json/permission.json')), true);
        $ai = collect($rows)->firstWhere('SubModules', 'Asisten AI');

        $this->assertNotNull($ai, 'modul Asisten AI harus ada di permission.json');
        $this->assertSame(1, collect($rows)->where('Id', $ai['Id'])->count(), 'Id Asisten AI tidak boleh kembar');
    }

    public function test_staff_without_access_cannot_open_ai_routes(): void
    {
        $this->actingAsStaffWithNoAccess();

        $this->get('/ai')->assertStatus(403);
        $this->get('/ai/history')->assertStatus(403);
        $this->post('/ai/chat', ['message' => 'halo'])->assertStatus(403);
    }

    public function test_widget_hidden_without_access_and_shown_with_access(): void
    {
        $this->actingAsStaffWithNoAccess();
        $this->get('/')->assertOk()->assertDontSee('id="ai-chat-root"', false);

        $this->actingAsStaffWithOnlyPermission('Asisten AI');
        $this->get('/')->assertOk()->assertSee('id="ai-chat-root"', false);
        $this->get('/ai')->assertOk();
    }

    public function test_superadmin_can_open_ai_page(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->get('/ai')->assertOk();
        $this->get('/ai/history')->assertOk();
    }
}
