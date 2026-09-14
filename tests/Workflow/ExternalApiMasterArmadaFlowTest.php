<?php

namespace Tests\Workflow;

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
}
