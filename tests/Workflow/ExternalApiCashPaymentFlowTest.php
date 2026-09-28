<?php

namespace Tests\Workflow;

use App\Models\Customer;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 POST/GET /payments/cash (GitHub #207) —
 * App\Http\Controllers\ExternalApi\V1\CashPaymentController.
 *
 * Only proves the ref_nota_id addition from #207: it is optional, stored, round-trips through
 * both store() and show(), and survives an idempotent replay unchanged. The rest of the
 * endpoint's contract (armada_id/staff_id resolution, item-direction validation, auto_accept) is
 * pre-existing behavior, not re-tested here.
 */
class ExternalApiCashPaymentFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    public function test_ref_nota_id_is_optional_and_round_trips_through_store_and_show(): void
    {
        $headers = $this->externalApiHeaders();
        $customer = Customer::query()->orderBy('customer_id')->firstOrFail();

        $payload = [
            'ref_payment_id' => 'TEST-REF-'.uniqid(),
            'ref_nota_id' => 'NOTA-TEST-001',
            'payment_type' => 1,
            'armada_id' => $customer->customer_id,
            'payment_date' => now()->toDateString(),
            'payment_amount' => 50000,
            'items' => [
                ['amount' => 50000, 'type' => 1, 'notes' => 'Pelunasan nota'],
            ],
        ];

        $storeResponse = $this->postJson('/api/external/v1/payments/cash', $payload, $headers);

        $storeResponse->assertStatus(201);
        $storeResponse->assertJsonPath('data.ref_nota_id', 'NOTA-TEST-001');

        $showResponse = $this->getJson(
            '/api/external/v1/payments/cash/'.$payload['ref_payment_id'],
            $headers,
        );

        $showResponse->assertStatus(200);
        $showResponse->assertJsonPath('data.ref_nota_id', 'NOTA-TEST-001');

        $replayResponse = $this->postJson('/api/external/v1/payments/cash', $payload, $headers);

        $replayResponse->assertStatus(200);
        $replayResponse->assertJsonPath('meta.idempotent_replay', true);
        $replayResponse->assertJsonPath('data.ref_nota_id', 'NOTA-TEST-001');
    }

    public function test_ref_nota_id_is_null_when_not_sent(): void
    {
        $headers = $this->externalApiHeaders();
        $customer = Customer::query()->orderBy('customer_id')->firstOrFail();

        $payload = [
            'ref_payment_id' => 'TEST-REF-'.uniqid(),
            'payment_type' => 1,
            'armada_id' => $customer->customer_id,
            'payment_date' => now()->toDateString(),
            'payment_amount' => 50000,
            'items' => [
                ['amount' => 50000, 'type' => 1, 'notes' => 'Pelunasan nota'],
            ],
        ];

        $storeResponse = $this->postJson('/api/external/v1/payments/cash', $payload, $headers);

        $storeResponse->assertStatus(201);
        $storeResponse->assertJsonPath('data.ref_nota_id', null);
    }
}
