<?php

namespace Tests\Workflow;

use App\Models\CashArmada;
use App\Models\CashSales;
use App\Models\Customer;
use App\Models\CustomerSupplyReturn;
use App\Models\Staff;
use App\Models\Supplies;
use App\Models\Unit;
use Tests\Support\ActingAsExternalApiClient;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #216: POST /payments/cash menerima ref_payment_number (No. Dokumen Pembayaran PMO,
 * mis. PB/BUD/121026/003) — opsional, tidak unik, bukan kunci idempotensi. Disimpan di
 * cash_armadas/cash_sales beserta dokumen Pengembalian dari potongan barangnya, dikembalikan di
 * response, dan tampil + bisa dicari di Kas Operasional (Dompet Armada/Sales) dan Pengembalian.
 */
class ExternalApiCashPaymentRefNumberFlowTest extends TestCase
{
    use ActingAsExternalApiClient;
    use ActingAsStaff;

    private const URL = '/api/external/v1/payments/cash';

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'RefNo Test Armada';
        $customer->customer_code = 'RFN'.random_int(100000, 999999);
        $customer->customer_notes = 'Armada RefNo Test';
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function createStaff(): Staff
    {
        $staff = new Staff();
        $staff->staff_name = 'RefNo Test Sales';
        $staff->staff_code = 'RFN-'.uniqid();
        $staff->external_ref_id = 'RFN-SALES-'.uniqid();
        $staff->status = 1;
        $staff->save();

        return $staff;
    }

    /** @return array{ref_supplies_id:int, ref_unit_id:int} */
    private function createJerigen(): array
    {
        $unit = new Unit();
        $unit->unit_name = 'Jerigen RefNo '.uniqid();
        $unit->unit_short_name = 'JR-'.random_int(1000, 9999);
        $unit->ref_unit_id = random_int(950000, 989999);
        $unit->status = 1;
        $unit->save();

        $supplies = new Supplies();
        $supplies->ref_supplies_id = random_int(950000, 989999);
        $supplies->supplies_name = 'Jerigen RefNo '.uniqid();
        $supplies->supplies_default_unit = $unit->unit_id;
        $supplies->supplies_unit = json_encode([(string) $unit->unit_id]);
        $supplies->supplies_alert = 0;
        $supplies->status = 1;
        $supplies->save();

        return ['ref_supplies_id' => (int) $supplies->ref_supplies_id, 'ref_unit_id' => (int) $unit->ref_unit_id];
    }

    private function mainWarehouseId(): int
    {
        return (int) \Illuminate\Support\Facades\DB::table('warehouses as w')
            ->join('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
            ->where('wt.is_main_warehouse', 1)->where('w.status', 1)
            ->orderBy('w.id')->value('w.id');
    }

    private function uniqueNumber(): string
    {
        return 'PB/RFN/121026/'.random_int(100000, 999999);
    }

    public function test_armada_payment_stores_and_returns_the_number_on_cash_and_return(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $jerigen = $this->createJerigen();
        $ref = 'G-RFN-'.uniqid();
        $number = $this->uniqueNumber();

        $response = $this->postJson(self::URL, [
            'ref_payment_id' => $ref,
            'ref_payment_number' => $number,
            'payment_type' => 1,
            'armada_code' => $armada->customer_code,
            'payment_date' => '2026-10-12',
            'ref_shipment_id' => 'SHP-RFN',
            'payment_amount' => 150000,
            'items' => [
                ['kind' => 'cash', 'type' => 1, 'amount' => 100000],
                ['kind' => 'potongan_barang', 'type' => 1, 'amount' => 50000, 'goods' => [
                    'item_type' => 1, 'ref_id' => (string) $jerigen['ref_supplies_id'], 'qty' => 2, 'satuan_id' => $jerigen['ref_unit_id'],
                ]],
            ],
        ], $headers);

        $response->assertStatus(201)->assertJsonPath('data.ref_payment_number', $number);

        $cash = CashArmada::where('ref_payment_id', $ref)->firstOrFail();
        $this->assertSame($number, $cash->ref_payment_number);

        $return = CustomerSupplyReturn::findOrFail($response->json('data.returns.0.supply_return_id'));
        $this->assertSame($number, $return->ref_payment_number);
        $this->assertSame($ref, $return->ref_number, 'ref_number tetap ref_payment_id seperti sebelumnya');

        $this->getJson('/api/external/v1/payments/cash/'.$ref, $headers)
            ->assertOk()
            ->assertJsonPath('data.0.ref_payment_number', $number);

        // --- Tampil & bisa dicari di halaman admin ---------------------------------------
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse($this->mainWarehouseId());

        $cashList = $this->getJson('/getCashArmada?'.http_build_query(['draw' => 1, 'search' => ['value' => $number]]))
            ->assertOk();
        $this->assertSame([(int) $cash->cr_id], array_column($cashList->json('data'), 'cr_id'));
        $this->assertSame($number, $cashList->json('data.0.ref_payment_number'));

        $returnList = $this->getJson('/customerReturns?'.http_build_query(['draw' => 1, 'search' => ['value' => $number]]))
            ->assertOk();
        $this->assertCount(1, $returnList->json('data'));
        $this->assertSame($number, $returnList->json('data.0.ref_payment_number'));

        $this->getJson('/customerReturns/'.urlencode($response->json('data.returns.0.return_number')))
            ->assertOk()
            ->assertJsonPath('ref_payment_number', $number);
    }

    public function test_sales_payment_without_the_number_or_with_blank_stores_null(): void
    {
        $headers = $this->externalApiHeaders();
        $staff = $this->createStaff();

        foreach (['G-RFN-NONE-'.uniqid() => [], 'G-RFN-BLANK-'.uniqid() => ['ref_payment_number' => '  ']] as $ref => $extra) {
            $this->postJson(self::URL, [
                'ref_payment_id' => $ref,
                'payment_type' => 2,
                'staff_id' => $staff->external_ref_id,
                'payment_date' => '2026-10-12',
                'payment_amount' => 10000,
                'items' => [['type' => 1, 'amount' => 10000]],
            ] + $extra, $headers)
                ->assertStatus(201)
                ->assertJsonPath('data.ref_payment_number', null);

            $this->assertNull(CashSales::where('ref_payment_id', $ref)->value('ref_payment_number'));
        }
    }

    public function test_the_number_is_not_unique_and_a_replay_does_not_change_it(): void
    {
        $headers = $this->externalApiHeaders();
        $staffA = $this->createStaff();
        $staffB = $this->createStaff();
        $ref = 'G-RFN-'.uniqid();
        $number = $this->uniqueNumber();

        $payload = fn (Staff $staff, ?string $num) => [
            'ref_payment_id' => $ref,
            'ref_payment_number' => $num,
            'payment_type' => 2,
            'staff_id' => $staff->external_ref_id,
            'payment_date' => '2026-10-12',
            'payment_amount' => 20000,
            'items' => [['type' => 1, 'amount' => 20000]],
        ];

        // Satu dokumen PMO, dua sales penerima -> dua transaksi IPM dengan nomor yang sama.
        $this->postJson(self::URL, $payload($staffA, $number), $headers)->assertStatus(201);
        $this->postJson(self::URL, $payload($staffB, $number), $headers)->assertStatus(201);
        $this->assertSame(2, CashSales::where('ref_payment_number', $number)->count());

        // Kiriman ulang dengan nomor lain tidak mengubah apa pun.
        $this->postJson(self::URL, $payload($staffA, 'PB/RFN/OTHER/001'), $headers)
            ->assertOk()
            ->assertJsonPath('meta.idempotent_replay', true)
            ->assertJsonPath('data.ref_payment_number', $number);
        $this->assertSame(2, CashSales::where('ref_payment_number', $number)->count());
    }

    public function test_number_longer_than_64_characters_is_rejected(): void
    {
        $staff = $this->createStaff();

        $this->postJson(self::URL, [
            'ref_payment_id' => 'G-RFN-'.uniqid(),
            'ref_payment_number' => str_repeat('X', 65),
            'payment_type' => 2,
            'staff_id' => $staff->external_ref_id,
            'payment_date' => '2026-10-12',
            'payment_amount' => 10000,
            'items' => [['type' => 1, 'amount' => 10000]],
        ], $this->externalApiHeaders())
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED');
    }
}
