<?php

namespace Tests\Workflow;

use App\Models\CashArmada;
use App\Models\CashArmadaDetail;
use App\Models\Customer;
use App\Models\CustomerSupplyReturn;
use App\Models\CustomerSupplyReturnDetail;
use App\Models\Staff;
use App\Models\Supplies;
use App\Models\Unit;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * POST /payments/cash with potongan items (PMO issue #28): items[].kind cash/potongan/
 * potongan_barang all land in the cash record, and potongan_barang additionally creates one
 * Pengembalian document per (armada_code, ref_shipment_id), in the same transaction, with bahan
 * lines left without a warehouse for IPM staff to pick.
 */
class ExternalApiCashPaymentPotonganFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private const URL = '/api/external/v1/payments/cash';

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Potongan Test Armada';
        $customer->customer_code = 'PTG'.random_int(100000, 999999);
        $customer->customer_notes = 'Armada Potongan Test';
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function createStaff(): Staff
    {
        $staff = new Staff();
        $staff->staff_name = 'Potongan Test Sales';
        $staff->staff_code = 'PS-'.uniqid();
        $staff->external_ref_id = 'PTG-SALES-'.uniqid();
        $staff->status = 1;
        $staff->save();

        return $staff;
    }

    /** @return array{ref_supplies_id:int, ref_unit_id:int, supplies:Supplies} */
    private function createJerigen(): array
    {
        $unit = new Unit();
        $unit->unit_name = 'Jerigen Test '.uniqid();
        $unit->unit_short_name = 'JR-'.random_int(1000, 9999);
        $unit->ref_unit_id = random_int(900000, 949999);
        $unit->status = 1;
        $unit->save();

        $supplies = new Supplies();
        $supplies->ref_supplies_id = random_int(900000, 949999);
        $supplies->supplies_name = 'Jerigen Test '.uniqid();
        $supplies->supplies_default_unit = $unit->unit_id;
        $supplies->supplies_unit = json_encode([(string) $unit->unit_id]);
        $supplies->supplies_alert = 0;
        $supplies->status = 1;
        $supplies->save();

        return ['ref_supplies_id' => (int) $supplies->ref_supplies_id, 'ref_unit_id' => (int) $unit->ref_unit_id, 'supplies' => $supplies];
    }

    private function goods(array $jerigen, int $qty, array $extra = []): array
    {
        return [
            'item_type' => 1,
            'ref_id' => (string) $jerigen['ref_supplies_id'],
            'qty' => $qty,
            'satuan_id' => $jerigen['ref_unit_id'],
        ] + $extra;
    }

    public function test_armada_payment_records_all_kinds_and_creates_one_return_per_armada_and_shipment(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $otherCode = 'PTGNEW'.random_int(100000, 999999);
        $jerigen = $this->createJerigen();
        $ref = 'G-PTG-'.uniqid();

        $response = $this->postJson(self::URL, [
            'ref_payment_id' => $ref,
            'payment_type' => 1,
            'armada_code' => $armada->customer_code,
            'payment_date' => '2026-10-07',
            'ref_shipment_id' => 'SHP-A',
            'payment_amount' => 1720000,
            'armadas' => [['code' => $otherCode, 'nomor_polisi' => 'B 5678 YY']],
            'items' => [
                ['kind' => 'cash', 'type' => 1, 'amount' => 1500000, 'ref_nota_id' => '123'],
                ['kind' => 'potongan', 'type' => 1, 'amount' => 45000, 'notes' => 'Cash Diskon 3%'],
                ['kind' => 'potongan_barang', 'type' => 1, 'amount' => 105000, 'notes' => 'Jerigen 25kg', 'ref_nota_id' => '123',
                    'goods' => $this->goods($jerigen, 5)],
                ['kind' => 'potongan_barang', 'type' => 1, 'amount' => 70000, 'notes' => 'Jerigen 35kg', 'ref_nota_id' => '124',
                    'goods' => $this->goods($jerigen, 2, ['armada_code' => $otherCode, 'ref_shipment_id' => 'SHP-B'])],
            ],
        ], $headers);

        $response->assertStatus(201)
            ->assertJsonPath('data.payment_amount', 1720000)
            ->assertJsonPath('data.items.0.kind', 'cash')
            ->assertJsonPath('data.items.1.kind', 'potongan')
            ->assertJsonPath('data.items.2.kind', 'potongan_barang')
            ->assertJsonCount(2, 'data.returns')
            ->assertJsonPath('data.returns.0.armada_code', $armada->customer_code)
            ->assertJsonPath('data.returns.0.ref_shipment_id', 'SHP-A')
            ->assertJsonPath('data.returns.0.pending_warehouse_items', 1)
            ->assertJsonPath('data.returns.1.armada_code', $otherCode)
            ->assertJsonPath('data.returns.1.ref_shipment_id', 'SHP-B');

        $cash = CashArmada::where('ref_payment_id', $ref)->firstOrFail();
        $this->assertSame(1720000, (int) $cash->cr_nominal);
        $this->assertSame(
            ['cash', 'potongan', 'potongan_barang', 'potongan_barang'],
            CashArmadaDetail::where('cr_id', $cash->cr_id)->orderBy('crd_id')->pluck('crd_kind')->all(),
        );

        // Armada baru dibuat dari armadas[].
        $this->assertTrue(Customer::where('customer_code', $otherCode)->where('status', 1)->exists());

        $firstReturn = CustomerSupplyReturn::findOrFail($response->json('data.returns.0.supply_return_id'));
        $this->assertSame((int) $armada->customer_id, (int) $firstReturn->customer_id);
        $this->assertSame($ref, $firstReturn->ref_number);
        $this->assertSame(1, (int) $firstReturn->status);

        $detail = CustomerSupplyReturnDetail::where('return_id', $firstReturn->return_id)->firstOrFail();
        $this->assertSame((int) $jerigen['supplies']->supplies_id, (int) $detail->supplies_id);
        $this->assertSame(5, (int) $detail->qty);
        $this->assertNull($detail->warehouse_id, 'bahan dari potongan barang menunggu staf memilih gudang');
        $this->assertSame(123, (int) $detail->ref_nota_id);
    }

    public function test_resending_the_same_payment_replays_without_creating_new_returns(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $jerigen = $this->createJerigen();
        $payload = [
            'ref_payment_id' => 'G-PTG-'.uniqid(),
            'payment_type' => 1,
            'armada_code' => $armada->customer_code,
            'payment_date' => '2026-10-07',
            'ref_shipment_id' => 'SHP-R',
            'payment_amount' => 105000,
            'items' => [
                ['kind' => 'potongan_barang', 'type' => 1, 'amount' => 105000, 'goods' => $this->goods($jerigen, 5)],
            ],
        ];

        $first = $this->postJson(self::URL, $payload, $headers)->assertStatus(201);
        $returnsBefore = CustomerSupplyReturn::count();

        $this->postJson(self::URL, $payload, $headers)
            ->assertStatus(200)
            ->assertJsonPath('meta.idempotent_replay', true)
            ->assertJsonPath('data.returns.0.return_number', $first->json('data.returns.0.return_number'));

        $this->assertSame($returnsBefore, CustomerSupplyReturn::count());
    }

    public function test_sales_payment_requires_goods_armada_code_and_then_creates_the_return(): void
    {
        $headers = $this->externalApiHeaders();
        $staff = $this->createStaff();
        $armada = $this->createArmada();
        $jerigen = $this->createJerigen();
        $payload = [
            'ref_payment_id' => 'G-PTG-'.uniqid(),
            'payment_type' => 2,
            'staff_id' => $staff->external_ref_id,
            'payment_date' => '2026-10-07',
            'payment_amount' => 105000,
            'items' => [
                ['kind' => 'potongan_barang', 'type' => 1, 'amount' => 105000,
                    'goods' => $this->goods($jerigen, 5, ['ref_shipment_id' => 'SHP-S'])],
            ],
        ];

        $this->postJson(self::URL, $payload, $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.details', fn ($details) => isset($details['items.0.goods.armada_code']));

        $payload['items'][0]['goods']['armada_code'] = $armada->customer_code;

        $this->postJson(self::URL, $payload, $headers)
            ->assertStatus(201)
            ->assertJsonPath('data.staff_id', $staff->external_ref_id)
            ->assertJsonPath('data.returns.0.armada_code', $armada->customer_code);
    }

    public function test_unknown_bahan_rejects_the_whole_payment(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $jerigen = $this->createJerigen();
        $ref = 'G-PTG-'.uniqid();
        $goods = $this->goods($jerigen, 1);
        $goods['ref_id'] = '999999999';

        $this->postJson(self::URL, [
            'ref_payment_id' => $ref,
            'payment_type' => 1,
            'armada_code' => $armada->customer_code,
            'payment_date' => '2026-10-07',
            'ref_shipment_id' => 'SHP-X',
            'payment_amount' => 150000,
            'items' => [
                ['type' => 1, 'amount' => 100000],
                ['kind' => 'potongan_barang', 'type' => 1, 'amount' => 50000, 'goods' => $goods],
            ],
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.details', fn ($details) => isset($details['items.1.goods.ref_id']));

        $this->assertFalse(CashArmada::where('ref_payment_id', $ref)->exists());
    }

    public function test_potongan_is_rejected_on_a_non_masuk_payment(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();

        $this->postJson(self::URL, [
            'ref_payment_id' => 'G-PTG-'.uniqid().'-K',
            'payment_type' => 1,
            'armada_code' => $armada->customer_code,
            'payment_date' => '2026-10-07',
            'payment_amount' => 10000,
            'items' => [
                ['kind' => 'potongan', 'type' => 2, 'amount' => 10000],
            ],
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.details', fn ($details) => isset($details['items.0.kind']));
    }

    public function test_goods_is_only_allowed_on_potongan_barang(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $jerigen = $this->createJerigen();

        $this->postJson(self::URL, [
            'ref_payment_id' => 'G-PTG-'.uniqid(),
            'payment_type' => 1,
            'armada_code' => $armada->customer_code,
            'payment_date' => '2026-10-07',
            'payment_amount' => 10000,
            'items' => [
                ['kind' => 'cash', 'type' => 1, 'amount' => 10000, 'goods' => $this->goods($jerigen, 1)],
            ],
        ], $headers)
            ->assertStatus(422)
            ->assertJsonPath('error.details', fn ($details) => isset($details['items.0.goods']));
    }

    public function test_payload_without_kind_still_works_as_cash_only(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();

        $this->postJson(self::URL, [
            'ref_payment_id' => 'G-PTG-'.uniqid(),
            'payment_type' => 1,
            'armada_code' => $armada->customer_code,
            'payment_date' => '2026-10-07',
            'payment_amount' => 10000,
            'items' => [
                ['type' => 1, 'amount' => 10000],
            ],
        ], $headers)
            ->assertStatus(201)
            ->assertJsonPath('data.items.0.kind', 'cash')
            ->assertJsonPath('data.returns', []);
    }
}
