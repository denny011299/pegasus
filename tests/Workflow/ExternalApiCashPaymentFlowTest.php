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
        $storeResponse->assertJsonPath('data.0.items.0.ref_nota_id', 'NOTA-TEST-001');
        $storeResponse->assertJsonPath('data.0.items.1.ref_nota_id', null);
        $storeResponse->assertJsonMissingPath('data.0.ref_nota_id');

        $showResponse = $this->getJson('/api/external/v1/payments/cash/'.$refPaymentId, $headers);

        $showResponse->assertStatus(200);
        $showResponse->assertJsonPath('data.0.items.0.ref_nota_id', 'NOTA-TEST-001');
        $showResponse->assertJsonPath('data.0.items.1.ref_nota_id', null);

        $replayResponse = $this->postJson('/api/external/v1/payments/cash', $payload, $headers);

        $replayResponse->assertStatus(200);
        $replayResponse->assertJsonPath('meta.idempotent_replay', true);
        $replayResponse->assertJsonPath('data.0.items.0.ref_nota_id', 'NOTA-TEST-001');
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
        $armadaResponse->assertJsonPath('data.0.armada_code', $customer->customer_code);

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
        $salesResponse->assertJsonPath('data.0.staff_id', $staff->external_ref_id);

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
                ->assertJsonPath('data.0.staff_id', $staff->external_ref_id);
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

    /**
     * Type 1+2+3 → dokumen operasional (net mengurangi type 3 seperti type 2).
     * Type 3 → JUGA tetap Kas Besar Keluar 1.
     * Net operasional: 100000+25000 − 30000 − 20000 = 75000; Keluar 1 Kas Besar = 20000.
     */
    public function test_type3_goes_to_kas_besar_and_also_nets_in_operasional_masuk(): void
    {
        $headers = $this->externalApiHeaders();
        $customer = Customer::query()->where('status', 1)->orderBy('customer_id')->firstOrFail();
        $refPaymentId = 'TEST-MIX-'.uniqid();

        $payload = [
            'ref_payment_id' => $refPaymentId,
            'payment_type' => 1,
            'armada_code' => $customer->customer_code,
            'payment_date' => now()->toDateString(),
            'payment_amount' => 175000,
            'items' => [
                ['amount' => 100000, 'type' => 1, 'notes' => 'Setoran A'],
                ['amount' => 30000, 'type' => 2, 'notes' => 'Solar'],
                ['amount' => 25000, 'type' => 1, 'notes' => 'Setoran B'],
                ['amount' => 20000, 'type' => 3, 'notes' => 'Tol'],
            ],
        ];

        $response = $this->postJson('/api/external/v1/payments/cash', $payload, $headers);

        $response->assertStatus(201)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.returns', []);

        $rows = \App\Models\CashArmada::where('ref_payment_id', $refPaymentId)
            ->orderBy('ref_direction')
            ->get()
            ->keyBy(fn ($r) => (int) $r->ref_direction);

        $this->assertCount(2, $rows);
        $this->assertTrue($rows->has(1), 'harus ada dokumen operasional');
        $this->assertTrue($rows->has(3), 'harus ada dokumen Keluar 1 Kas Besar');

        $ops = $rows[1];
        $this->assertSame(75000, (int) $ops->cr_nominal);
        $this->assertSame(0, (int) $ops->cash_id);
        $this->assertSame(1, (int) $ops->status);
        $detailTypes = \App\Models\CashArmadaDetail::where('cr_id', $ops->cr_id)
            ->orderBy('crd_id')
            ->pluck('crd_type')
            ->map(fn ($v) => (int) $v)
            ->all();
        $this->assertSame([1, 2, 1, 3], $detailTypes);

        $k1 = $rows[3];
        $this->assertSame(20000, (int) $k1->cr_nominal);
        $this->assertGreaterThan(0, (int) $k1->cash_id, 'type 3 tetap buat baris Kas Besar');
        $this->assertSame(2, (int) $k1->status);
        $cash = \App\Models\Cash::find($k1->cash_id);
        $this->assertNotNull($cash);
        $this->assertSame(3, (int) $cash->cash_type);
        $this->assertSame(20000, (int) $cash->cash_nominal);

        $this->postJson('/api/external/v1/payments/cash', $payload, $headers)
            ->assertStatus(200)
            ->assertJsonPath('meta.idempotent_replay', true)
            ->assertJsonCount(2, 'data');
        $this->assertSame(2, \App\Models\CashArmada::where('ref_payment_id', $refPaymentId)->count());
    }

    /**
     * Item operasional digabung satu bucket: menambah/mengubah item pada ref yang sama = 409
     * (create-only), bukan membuat dokumen kedua.
     */
    public function test_adding_items_later_to_same_operasional_ref_is_rejected_with_409(): void
    {
        $headers = $this->externalApiHeaders();
        $customer = Customer::query()->where('status', 1)->orderBy('customer_id')->firstOrFail();
        $ref = 'TEST-ADD-'.uniqid();
        $masuk = ['amount' => 100000, 'type' => 1, 'notes' => 'Setoran'];

        $base = [
            'ref_payment_id' => $ref,
            'payment_type' => 1,
            'armada_code' => $customer->customer_code,
            'payment_date' => now()->toDateString(),
        ];

        $this->postJson('/api/external/v1/payments/cash', $base + ['items' => [$masuk]], $headers)
            ->assertStatus(201)->assertJsonCount(1, 'data');

        $this->postJson('/api/external/v1/payments/cash', $base + ['items' => [
            $masuk,
            ['amount' => 30000, 'type' => 2, 'notes' => 'Solar'],
        ]], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PAYMENT_CONTENT_MISMATCH');

        $this->assertSame(1, \App\Models\CashArmada::where('ref_payment_id', $ref)->count());
    }

    /** Isi dokumen operasional yang sudah tersimpan berbeda → 409. */
    public function test_changed_operasional_content_is_rejected_with_409(): void
    {
        $headers = $this->externalApiHeaders();
        $customer = Customer::query()->where('status', 1)->orderBy('customer_id')->firstOrFail();
        $ref = 'TEST-CONF-'.uniqid();

        $base = [
            'ref_payment_id' => $ref,
            'payment_type' => 1,
            'armada_code' => $customer->customer_code,
            'payment_date' => now()->toDateString(),
        ];

        $this->postJson('/api/external/v1/payments/cash', $base + ['items' => [
            ['amount' => 100000, 'type' => 1],
            ['amount' => 30000, 'type' => 2],
        ]], $headers)->assertStatus(201);

        $this->postJson('/api/external/v1/payments/cash', $base + ['items' => [
            ['amount' => 120000, 'type' => 1],
            ['amount' => 30000, 'type' => 2],
        ]], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PAYMENT_CONTENT_MISMATCH')
            ->assertJsonPath('error.details.type', 1);

        $this->postJson('/api/external/v1/payments/cash', $base + ['items' => [
            ['amount' => 100000, 'type' => 1],
        ]], $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.details.type', 1);

        $this->assertSame(1, \App\Models\CashArmada::where('ref_payment_id', $ref)->count());
    }

    /** payment_amount opsional; kalau dikirim tetap harus sama dengan total item (gross). Response amount = net. */
    public function test_payment_amount_is_optional_but_checked_when_sent(): void
    {
        $headers = $this->externalApiHeaders();
        $customer = Customer::query()->where('status', 1)->orderBy('customer_id')->firstOrFail();

        $payload = [
            'ref_payment_id' => 'TEST-AMT-'.uniqid(),
            'payment_type' => 1,
            'armada_code' => $customer->customer_code,
            'payment_date' => now()->toDateString(),
            'items' => [['amount' => 40000, 'type' => 1], ['amount' => 15000, 'type' => 2]],
        ];

        $this->postJson('/api/external/v1/payments/cash', $payload + ['payment_amount' => 99], $headers)
            ->assertStatus(422);

        $this->postJson('/api/external/v1/payments/cash', $payload + ['payment_amount' => 55000], $headers)
            ->assertStatus(201)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.payment_amount', 25000);
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
