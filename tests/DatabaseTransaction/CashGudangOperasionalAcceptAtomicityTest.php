<?php

namespace Tests\DatabaseTransaction;

use App\Models\CashArmada;
use App\Models\CashGudang;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * `CashGudang`'s "operasional" branch deliberately deviates from its structural twin `CashAdmin`
 * (see cdocs/docs/flows/kas-operasional/FLOW.md) — `acceptCashGudang()` mutates
 * `customers.customer_saldo` and auto-creates a pre-approved `CashArmada` row per detail line
 * ("Penyerahan kas dari gudang" — cash handed from the warehouse to an armada/courier on a
 * customer's behalf). Kept as explicit product-owner-confirmed behavior (2026-08-05, pending final
 * confirmation with whoever owns Kas Operasional/Cash Armada) — this test only hardens the
 * mechanics: both the customer_saldo mutation loop (`ReportController::acceptCashGudang()`) and the
 * CashArmada-creation loop (`CashGudang::acceptCashGudang()`) now run inside one
 * `DB::transaction()`, with a precheck (no mutation) that every detail row's `customer_id` resolves
 * to a real Customer before anything is touched.
 */
class CashGudangOperasionalAcceptAtomicityTest extends TestCase
{
    use ActingAsStaff;

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    private function insertOperasionalCashGudang(array $items): int
    {
        $response = $this->post('/insertCashGudang', [
            'staff_id' => $this->staffId(),
            'jenis_input' => 'operasional',
            'photo' => '',
            'items' => json_encode($items),
        ]);
        $response->assertStatus(200);

        return (int) CashGudang::orderByDesc('cg_id')->firstOrFail()->cg_id;
    }

    public function test_approving_a_valid_operasional_entry_mutates_saldo_and_creates_armada_rows_atomically(): void
    {
        $this->actingAsSuperAdminStaff();

        $customerA = Customer::where('status', 1)->orderBy('customer_id')->first();
        $customerB = Customer::where('status', 1)->orderBy('customer_id')->skip(1)->first();
        $startingA = $customerA->customer_saldo;
        $startingB = $customerB->customer_saldo;

        $armadaCountBefore = CashArmada::count();

        $cgId = $this->insertOperasionalCashGudang([
            ['customer_id' => $customerA->customer_id, 'cgd_nominal' => 50000, 'cgd_notes' => 'Test A'],
            ['customer_id' => $customerB->customer_id, 'cgd_nominal' => 75000, 'cgd_notes' => 'Test B'],
        ]);

        $response = $this->post('/acceptCashGudang', ['cg_id' => $cgId]);
        $response->assertOk();

        $cg = CashGudang::findOrFail($cgId);
        $this->assertSame(2, (int) $cg->status, 'a valid approval must still flip status to approved');

        $customerA->refresh();
        $customerB->refresh();
        $this->assertSame($startingA + 50000, (int) $customerA->customer_saldo);
        $this->assertSame($startingB + 75000, (int) $customerB->customer_saldo);

        $this->assertSame(
            $armadaCountBefore + 2,
            CashArmada::count(),
            'one pre-approved CashArmada row must be created per detail line'
        );
        $this->assertDatabaseHas('cash_armadas', [
            'customer_id' => $customerA->customer_id,
            'cr_nominal' => 50000,
            'cr_type' => 1,
            'status' => 2,
        ]);
    }

    public function test_approving_an_operasional_entry_with_an_invalid_customer_is_rejected_with_zero_mutation(): void
    {
        $this->actingAsSuperAdminStaff();

        $customerA = Customer::where('status', 1)->orderBy('customer_id')->first();
        $startingA = $customerA->customer_saldo;
        $bogusCustomerId = 999999;

        $armadaCountBefore = CashArmada::count();

        $cgId = $this->insertOperasionalCashGudang([
            ['customer_id' => $customerA->customer_id, 'cgd_nominal' => 50000, 'cgd_notes' => 'Test A'],
            ['customer_id' => $bogusCustomerId, 'cgd_nominal' => 20000, 'cgd_notes' => 'Test bogus'],
        ]);

        $response = $this->post('/acceptCashGudang', ['cg_id' => $cgId]);
        $response->assertOk();
        $response->assertJson(['status' => 0, 'header' => 'Gagal ACC']);

        $cg = CashGudang::findOrFail($cgId);
        $this->assertSame(1, (int) $cg->status, 'a rejected approval must leave the document pending');

        $customerA->refresh();
        $this->assertSame($startingA, (int) $customerA->customer_saldo, 'a rejected approval must not touch any customer_saldo, not even the valid row');

        $this->assertSame(
            $armadaCountBefore,
            CashArmada::count(),
            'a rejected approval must not create any CashArmada rows at all'
        );
    }
}
