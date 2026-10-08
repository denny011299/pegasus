<?php

namespace Tests\Workflow;

use App\Models\Customer;
use App\Models\Staff;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * External API v1 POST/GET /payments/cash (GitHub #207, revised by #208) —
 * App\Http\Controllers\ExternalApi\V1\CashPaymentController.
 *
 * Covers: armada_code/staff_id resolved by external reference (not internal id, #207);
 * items[].ref_nota_id round-tripping per item (#208, moved from the old top-level ref_nota_id);
 * idempotency keyed on ref_payment_id + armada_code/staff_id rather than ref_payment_id alone,
 * so one ref_payment_id can legitimately produce several payments (#208); and GET show() always
 * returning a list, optionally narrowed with ?armada_code=/?staff_id=.
 */
class ExternalApiCashPaymentFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    public function test_items_ref_nota_id_is_optional_and_round_trips_through_store_and_show(): void
    {
        $headers = $this->externalApiHeaders();
        $customer = Customer::query()->where('status', 1)->orderBy('customer_id')->firstOrFail();
        $refPaymentId = 'TEST-REF-'.uniqid();

        $payload = [
            'ref_payment_id' => $refPaymentId,
            'payment_type' => 1,
            'armada_id' => $customer->customer_id,
            'payment_date' => now()->toDateString(),
            'payment_amount' => 150000,
            'items' => [
                ['amount' => 100000, 'type' => 1, 'notes' => 'Nota A', 'ref_nota_id' => 'NOTA-TEST-001'],
                ['amount' => 50000, 'type' => 1, 'notes' => 'Nota B tanpa ref'],
            ],
        ];

        $storeResponse = $this->postJson('/api/external/v1/payments/cash', $payload, $headers);

        $storeResponse->assertStatus(201);
        $storeResponse->assertJsonPath('data.items.0.ref_nota_id', 'NOTA-TEST-001');
        $storeResponse->assertJsonPath('data.items.1.ref_nota_id', null);
        $storeResponse->assertJsonMissingPath('data.ref_nota_id');

        $showResponse = $this->getJson('/api/external/v1/payments/cash/'.$refPaymentId, $headers);

        $showResponse->assertStatus(200);
        $showResponse->assertJsonPath('data.0.items.0.ref_nota_id', 'NOTA-TEST-001');
        $showResponse->assertJsonPath('data.0.items.1.ref_nota_id', null);

        $replayResponse = $this->postJson('/api/external/v1/payments/cash', $payload, $headers);

        $replayResponse->assertStatus(200);
        $replayResponse->assertJsonPath('meta.idempotent_replay', true);
        $replayResponse->assertJsonPath('data.items.0.ref_nota_id', 'NOTA-TEST-001');
    }

    public function test_armada_and_sales_are_resolved_by_external_reference_not_internal_id(): void
    {
        $headers = $this->externalApiHeaders();
        $customer = Customer::query()->where('status', 1)->orderBy('customer_id')->firstOrFail();

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

        $unknownArmadaPayload = $armadaPayload;
        $unknownArmadaPayload['ref_payment_id'] = 'TEST-REF-'.uniqid();
        $unknownArmadaPayload['armada_code'] = 'DOES-NOT-EXIST';

        $this->postJson('/api/external/v1/payments/cash', $unknownArmadaPayload, $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.details.armada_code.0', 'Armada dengan armada_code "DOES-NOT-EXIST" tidak ditemukan.');

        $staff = $this->makeStaffWithExternalRef('TEST-STAFF-'.uniqid());

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
            ->assertJsonPath('error.details.staff_id.0', 'Sales dengan staff_id (external_ref_id) DOES-NOT-EXIST tidak ditemukan.');
    }

    public function test_same_ref_payment_id_creates_separate_payments_per_sales_and_show_lists_all(): void
    {
        $headers = $this->externalApiHeaders();
        $staffs = collect([1, 2])->map(fn ($i) => $this->makeStaffWithExternalRef('TEST-STAFF-'.uniqid().'-'.$i));

        $refPaymentId = 'TEST-GROUP-'.uniqid();

        foreach ($staffs as $index => $staff) {
            $payload = [
                'ref_payment_id' => $refPaymentId,
                'payment_type' => 2,
                'staff_id' => $staff->external_ref_id,
                'payment_date' => now()->toDateString(),
                'payment_amount' => 10000 + $index,
                'items' => [
                    ['amount' => 10000 + $index, 'type' => 1],
                ],
            ];

            $this->postJson('/api/external/v1/payments/cash', $payload, $headers)
                ->assertStatus(201)
                ->assertJsonPath('data.staff_id', $staff->external_ref_id);
        }

        // Kiriman ulang untuk staff_id yang sama pada ref yang sama TETAP dianggap replay.
        $this->postJson('/api/external/v1/payments/cash', [
            'ref_payment_id' => $refPaymentId,
            'payment_type' => 2,
            'staff_id' => $staffs[0]->external_ref_id,
            'payment_date' => now()->toDateString(),
            'payment_amount' => 10000,
            'items' => [
                ['amount' => 10000, 'type' => 1],
            ],
        ], $headers)->assertStatus(200)->assertJsonPath('meta.idempotent_replay', true);

        $listResponse = $this->getJson('/api/external/v1/payments/cash/'.$refPaymentId, $headers);

        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(2, 'data');

        $filteredResponse = $this->getJson(
            '/api/external/v1/payments/cash/'.$refPaymentId.'?staff_id='.$staffs[1]->external_ref_id,
            $headers,
        );

        $filteredResponse->assertStatus(200);
        $filteredResponse->assertJsonCount(1, 'data');
        $filteredResponse->assertJsonPath('data.0.staff_id', $staffs[1]->external_ref_id);
    }

    private function makeStaffWithExternalRef(string $externalRefId): Staff
    {
        $staff = new Staff();
        $staff->staff_name = 'Test Staff '.$externalRefId;
        $staff->staff_code = 'TS-'.uniqid();
        $staff->external_ref_id = $externalRefId;
        $staff->status = 1;
        $staff->save();

        return $staff;
    }
}
