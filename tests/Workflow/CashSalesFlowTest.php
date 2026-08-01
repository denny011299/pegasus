<?php

namespace Tests\Workflow;

use App\Models\CashSales;
use App\Models\Staff;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/CASH_SALES_FLOW.md for the fully-traced flow this asserts against.
 * CashSales shares CashArmada's shape (approval mutates a running balance — here
 * `staffs.staff_saldo`), not CashAdmin/CashGudang's pure status-flip shape.
 */
class CashSalesFlowTest extends TestCase
{
    use ActingAsStaff;

    private function pickStaff(): Staff
    {
        return Staff::where('status', 1)->firstOrFail();
    }

    private function insertOperasionalCashSales(Staff $staff, int $nominal, int $csdType): int
    {
        $response = $this->post('/insertCashSales', [
            'staff_id' => $staff->staff_id,
            'bank_id' => 0,
            'oc_transaksi' => 'operasional',
            'photo' => '',
            'items' => json_encode([[
                'csd_nominal' => $nominal,
                'csd_notes' => 'Workflow test line',
                'csd_type' => $csdType,
            ]]),
        ]);
        $response->assertStatus(200);

        return (int) CashSales::orderByDesc('cs_id')->value('cs_id');
    }

    public function test_setoran_shaped_entry_increases_staff_saldo_on_accept(): void
    {
        $this->actingAsSuperAdminStaff();

        $staff = $this->pickStaff();
        $startingSaldo = (int) $staff->staff_saldo;
        $nominal = 200000;

        $csId = $this->insertOperasionalCashSales($staff, $nominal, csdType: 1); // 1 = Setoran

        $cs = CashSales::findOrFail($csId);
        $this->assertSame(1, (int) $cs->status);
        $this->assertSame(2, (int) $cs->cs_type, 'operasional branch always explicitly sets cs_type=2');
        $this->assertSame(1, (int) $cs->cs_transaction, 'csd_type=1 (Setoran) sets cs_transaction=1');
        $this->assertSame(0, (int) $cs->cash_id, 'an operasional entry has no linked Cash row');

        $this->post('/acceptCashSales', ['cs_id' => $csId])->assertStatus(200);

        $cs->refresh();
        $this->assertSame(2, (int) $cs->status);

        $staff->refresh();
        $this->assertSame($startingSaldo + $nominal, (int) $staff->staff_saldo, 'accepting a Setoran-shaped entry (cs_type=2, cs_transaction=1) must increase staff_saldo');
    }

    public function test_ordinary_expense_decreases_staff_saldo_on_accept(): void
    {
        $this->actingAsSuperAdminStaff();

        $staff = $this->pickStaff();
        $staff->staff_saldo = 1000000; // ample balance so this expense doesn't go negative
        $staff->save();
        $nominal = 150000;

        $csId = $this->insertOperasionalCashSales($staff, $nominal, csdType: 2); // != 1 => ordinary expense

        $cs = CashSales::findOrFail($csId);
        $this->assertSame(2, (int) $cs->cs_transaction, 'csd_type is copied straight into cs_transaction for non-Setoran entries');

        $this->post('/acceptCashSales', ['cs_id' => $csId])->assertStatus(200);

        $staff->refresh();
        $this->assertSame(1000000 - $nominal, (int) $staff->staff_saldo, 'accepting an ordinary expense (cs_transaction != 1) must decrease staff_saldo');
    }

    public function test_decline_leaves_staff_saldo_untouched(): void
    {
        $this->actingAsSuperAdminStaff();

        $staff = $this->pickStaff();
        $startingSaldo = (int) $staff->staff_saldo;

        $csId = $this->insertOperasionalCashSales($staff, nominal: 50000, csdType: 2);

        $this->post('/declineCashSales', ['cs_id' => $csId])->assertStatus(200);

        $cs = CashSales::findOrFail($csId);
        $this->assertSame(3, (int) $cs->status);

        $staff->refresh();
        $this->assertSame($startingSaldo, (int) $staff->staff_saldo, 'declining must not touch staff_saldo at all — only accept does');
    }

    public function test_accepting_an_already_accepted_entry_is_blocked_and_does_not_double_apply(): void
    {
        $this->actingAsSuperAdminStaff();

        $staff = $this->pickStaff();
        $staff->staff_saldo = 1000000;
        $staff->save();
        $nominal = 100000;

        $csId = $this->insertOperasionalCashSales($staff, $nominal, csdType: 2);

        $this->post('/acceptCashSales', ['cs_id' => $csId])->assertStatus(200);
        $staff->refresh();
        $this->assertSame(1000000 - $nominal, (int) $staff->staff_saldo);

        $response = $this->post('/acceptCashSales', ['cs_id' => $csId]);
        $response->assertStatus(200);
        $response->assertJson(['status' => -2]);

        $staff->refresh();
        $this->assertSame(1000000 - $nominal, (int) $staff->staff_saldo, 'a repeat accept must be blocked by the status!=1 guard and not subtract staff_saldo a second time');
    }
}
