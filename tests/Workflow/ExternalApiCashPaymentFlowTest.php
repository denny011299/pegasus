<?php

namespace Tests\Workflow;

use App\Models\Customer;
use App\Models\Staff;
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
            'armada_code' => $customer->customer_code,
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
            'armada_code' => $customer->customer_code,
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

    public function test_armada_and_sales_are_resolved_by_external_reference_not_internal_id(): void
    {
        $headers = $this->externalApiHeaders();
        $customer = Customer::query()->orderBy('customer_id')->firstOrFail();

        $armadaPayload = [
            'ref_payment_id' => 'TEST-REF-'.uniqid(),
            'payment_type' => 1,
            'armada_code' => $customer->customer_code,
            'payment_date' => now()->toDateString(),
            'payment_amount' => 20000,
            'items' => [
                ['amount' => 20000, 'type' => 1],
            ],
        ];

        $armadaResponse = $this->postJson('/api/external/v1/payments/cash', $armadaPayload, $headers);

        $armadaResponse->assertStatus(201);
        $armadaResponse->assertJsonPath('data.armada_code', $customer->customer_code);
        $armadaResponse->assertJsonMissingPath('data.armada_id');

        $unknownArmadaPayload = $armadaPayload;
        $unknownArmadaPayload['ref_payment_id'] = 'TEST-REF-'.uniqid();
        $unknownArmadaPayload['armada_code'] = 'DOES-NOT-EXIST';

        $this->postJson('/api/external/v1/payments/cash', $unknownArmadaPayload, $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.details.armada_code.0', 'Armada dengan code DOES-NOT-EXIST tidak ditemukan.');

        $staff = Staff::query()->whereNotNull('external_ref_id')->first();

        if ($staff) {
            $salesPayload = [
                'ref_payment_id' => 'TEST-REF-'.uniqid(),
                'payment_type' => 2,
                'staff_id' => $staff->external_ref_id,
                'payment_date' => now()->toDateString(),
                'payment_amount' => 15000,
                'items' => [
                    ['amount' => 15000, 'type' => 1],
                ],
            ];

            $salesResponse = $this->postJson('/api/external/v1/payments/cash', $salesPayload, $headers);

            $salesResponse->assertStatus(201);
            $salesResponse->assertJsonPath('data.staff_id', $staff->external_ref_id);
        }

        $unknownSalesPayload = [
            'ref_payment_id' => 'TEST-REF-'.uniqid(),
            'payment_type' => 2,
            'staff_id' => 'DOES-NOT-EXIST',
            'payment_date' => now()->toDateString(),
            'payment_amount' => 15000,
            'items' => [
                ['amount' => 15000, 'type' => 1],
            ],
        ];

        $this->postJson('/api/external/v1/payments/cash', $unknownSalesPayload, $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.details.staff_id.0', 'Sales dengan staff_id DOES-NOT-EXIST tidak ditemukan.');
    }
}
