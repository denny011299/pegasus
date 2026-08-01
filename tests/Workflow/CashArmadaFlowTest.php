<?php

namespace Tests\Workflow;

use App\Models\CashArmada;
use App\Models\Customer;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/CASH_ARMADA_FLOW.md for the fully-traced flow this asserts against.
 * Unlike CashAdmin/CashGudang (cdocs/testing/workflows/KAS_OPERASIONAL_FLOW.md — approval is a
 * pure status flip), CashArmada's acceptCashArmada() actually mutates a running
 * customers.customer_saldo balance at approval time — the one flow among all 4 money pilots where
 * approval itself is the money calculation.
 */
class CashArmadaFlowTest extends TestCase
{
    use ActingAsStaff;

    private function pickCustomer(): Customer
    {
        return Customer::where('status', 1)->firstOrFail();
    }

    private function insertOperasionalCashArmada(Customer $customer, int $nominal, int $crdType): int
    {
        $response = $this->post('/insertCashArmada', [
            'customer_id' => $customer->customer_id,
            'oc_transaksi' => 'operasional',
            // The real frontend (Cash_Operational.js) always sends `photo` on insert, even empty
            // — insertCashArmada indexes $data['photo'] directly (not via Request's null-safe
            // magic getter), so omitting the key entirely crashes with "Undefined array key".
            'photo' => '',
            'items' => json_encode([[
                'crd_nominal' => $nominal,
                'crd_notes' => 'Workflow test line',
                'crd_type' => $crdType,
            ]]),
        ]);
        $response->assertStatus(200);

        return (int) CashArmada::orderByDesc('cr_id')->value('cr_id');
    }

    public function test_setoran_increases_customer_saldo_on_accept(): void
    {
        $this->actingAsSuperAdminStaff();

        $customer = $this->pickCustomer();
        $startingSaldo = (int) $customer->customer_saldo;
        $nominal = 200000;

        $crId = $this->insertOperasionalCashArmada($customer, $nominal, crdType: 1); // 1 = Setoran

        $cr = CashArmada::findOrFail($crId);
        $this->assertSame(1, (int) $cr->status);
        $this->assertSame(1, (int) $cr->cr_type, 'crd_type=1 (Setoran) must set cr_type=1 explicitly');
        $this->assertSame(0, (int) $cr->cash_id, 'an operasional entry has no linked Cash row');

        $this->post('/acceptCashArmada', ['cr_id' => $crId])->assertStatus(200);

        $cr->refresh();
        $this->assertSame(2, (int) $cr->status);

        $customer->refresh();
        $this->assertSame($startingSaldo + $nominal, (int) $customer->customer_saldo, 'accepting a Setoran must increase customer_saldo by cr_nominal');
    }

    public function test_ordinary_expense_decreases_customer_saldo_on_accept(): void
    {
        $this->actingAsSuperAdminStaff();

        $customer = $this->pickCustomer();
        $customer->customer_saldo = 1000000; // ample balance so this expense doesn't go negative
        $customer->save();
        $nominal = 150000;

        $crId = $this->insertOperasionalCashArmada($customer, $nominal, crdType: 2); // != 1 => ordinary expense

        $cr = CashArmada::findOrFail($crId);
        $this->assertSame(3, (int) $cr->cr_type, 'an ordinary expense leaves cr_type unset, defaulting to 3 (CashArmada::insertCashArmada\'s ?? 3)');

        $this->post('/acceptCashArmada', ['cr_id' => $crId])->assertStatus(200);

        $customer->refresh();
        $this->assertSame(1000000 - $nominal, (int) $customer->customer_saldo, 'accepting an ordinary expense (cr_type >= 2) must decrease customer_saldo by cr_nominal');
    }

    /**
     * ReportController::acceptCashArmada() has a negative-balance guard commented out
     * (`// if ($customer->customer_saldo < 0) { return ...; }`). Confirmed, not fixed — see
     * cdocs/testing/KNOWN_ISSUES.md. This test locks in the CURRENT behavior on purpose.
     */
    public function test_accepting_an_expense_that_would_make_balance_negative_is_currently_allowed(): void
    {
        $this->actingAsSuperAdminStaff();

        $customer = $this->pickCustomer();
        $customer->customer_saldo = 100;
        $customer->save();

        $crId = $this->insertOperasionalCashArmada($customer, nominal: 5000, crdType: 2);

        $response = $this->post('/acceptCashArmada', ['cr_id' => $crId]);
        $response->assertStatus(200);

        $cr = CashArmada::findOrFail($crId);
        $this->assertSame(2, (int) $cr->status, 'the disabled guard means this is accepted, not blocked');

        $customer->refresh();
        $this->assertSame(100 - 5000, (int) $customer->customer_saldo, 'customer_saldo is currently allowed to go negative');
    }

    public function test_decline_leaves_customer_saldo_untouched(): void
    {
        $this->actingAsSuperAdminStaff();

        $customer = $this->pickCustomer();
        $startingSaldo = (int) $customer->customer_saldo;

        $crId = $this->insertOperasionalCashArmada($customer, nominal: 50000, crdType: 2);

        $this->post('/declineCashArmada', ['cr_id' => $crId])->assertStatus(200);

        $cr = CashArmada::findOrFail($crId);
        $this->assertSame(3, (int) $cr->status);

        $customer->refresh();
        $this->assertSame($startingSaldo, (int) $customer->customer_saldo, 'declining must not touch customer_saldo at all — only accept does');
    }
}
