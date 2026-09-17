<?php

namespace Tests\Workflow;

use App\Models\Customer;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 POST /armada (GitHub #171) —
 * App\Http\Controllers\ExternalApi\V1\MasterArmadaController::store(). Focused on the
 * customer_code/code length limit specifically: PMO's oms_vehicle.kode is varchar(64) and not
 * guaranteed to fit 10 characters, so `code` must accept up to 64 (customers.customer_code was
 * widened to match in 2026_09_15_090000_widen_customers_customer_code_column).
 */
class ExternalApiMasterArmadaFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    public function test_a_request_without_an_api_key_is_rejected(): void
    {
        $this->postJson('/api/external/v1/armada', ['code' => 'ARMTEST01'])->assertStatus(401);
    }

    public function test_store_accepts_a_code_longer_than_ten_characters(): void
    {
        $code = 'ARM-'.strtoupper(substr(uniqid(), -20)); // > 10 chars, well under 64

        $response = $this->postJson('/api/external/v1/armada', [
            'code' => $code,
        ], $this->externalApiHeaders());

        $response->assertStatus(201)->assertJson([
            'success' => true,
            'data' => ['code' => $code],
        ]);
        $this->assertDatabaseHas('customers', ['customer_code' => $code]);
    }

    public function test_store_rejects_a_code_longer_than_sixty_four_characters(): void
    {
        $code = str_repeat('A', 65);

        $response = $this->postJson('/api/external/v1/armada', [
            'code' => $code,
        ], $this->externalApiHeaders());

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_store_marks_the_row_as_synced_via_external_api(): void
    {
        $code = 'ARM-'.strtoupper(substr(uniqid(), -20));

        $this->postJson('/api/external/v1/armada', [
            'code' => $code,
        ], $this->externalApiHeaders())->assertStatus(201);

        $customer = Customer::where('customer_code', $code)->firstOrFail();
        $this->assertNotNull($customer->external_api_synced_at, 'external_api_synced_at must be set so the admin UI can flag this row as PMO/API-managed');
    }

    public function test_put_upsert_marks_the_row_as_synced_via_external_api(): void
    {
        $code = 'ARM-'.strtoupper(substr(uniqid(), -20));
        $headers = $this->externalApiHeaders();

        // Unknown code -> upsert creates it.
        $this->putJson('/api/external/v1/armada/'.$code, [
            'pic' => 'Agus',
        ], $headers)->assertStatus(201);

        $customer = Customer::where('customer_code', $code)->firstOrFail();
        $this->assertNotNull($customer->external_api_synced_at);

        // Re-touch the timestamp: bump it back, then confirm PUT refreshes it.
        $customer->external_api_synced_at = now()->subDays(3);
        $customer->save();

        $this->putJson('/api/external/v1/armada/'.$code, [
            'pic' => 'Budi',
        ], $headers)->assertStatus(200);

        $this->assertTrue($customer->fresh()->external_api_synced_at->gt(now()->subMinute()));
    }
}
