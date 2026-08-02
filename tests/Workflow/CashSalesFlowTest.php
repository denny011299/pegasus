<?php

namespace Tests\Workflow;

use App\Models\Cash;
use App\Models\CashSales;
use App\Models\Staff;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/CASH_SALES_FLOW.md for the fully-traced flow this asserts against.
 * CashSales shares CashArmada's shape (approval mutates a running balance — here
 * `staffs.staff_saldo`), not CashAdmin/CashGudang's pure status-flip shape.
 *
 * The "saldo" sub-flow (added 2026-08-02) is its OWN shape, distinct from both CashAdmin's
 * (numeric `oc_transaksi` 1/2) and CashArmada's (no direction tracked at all, `cr_type` always
 * defaults to 3) — refutes the flat "mirrors CashAdmin exactly" assumption a third way. CashSales's
 * saldo branch (`insertCashSales`'s `else`, `ReportController.php:1604-1651`) dispatches on a
 * THREE-valued `cs_aksi` request field ("1"/"2"/"3"), not a 2-way split:
 *   - `cs_aksi=="1"`: a pure Setoran — no linked Cash row at all (`cs_transaction=1`), exactly
 *     mirroring the operasional branch's own `csd_type==1` Setoran case.
 *   - `cs_aksi=="2"` and `cs_aksi=="3"`: both create a linked Cash row and both fall through to the
 *     SAME `acceptCashSales()` branch (`staff_saldo -= cs_nominal`) — they are indistinguishable in
 *     every balance/aggregate effect. The only difference between them is which way the linked
 *     Cash row's own `cash_type` is flipped for a given nominal sign, and `cs_transaction` (2 vs 3,
 *     both `>= 2` so identical in `CashSales::getCashSales()`'s own `sisa_kas` aggregate too) — pure
 *     ledger-display bookkeeping, not a functional difference.
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

    private function insertSaldoCashSales(Staff $staff, string $csAksi, int $csNominal): int
    {
        $response = $this->post('/insertCashSales', [
            'staff_id' => $staff->staff_id,
            'bank_id' => 0,
            'oc_transaksi' => 'saldo',
            'photo' => '',
            'cs_aksi' => $csAksi,
            'cs_notes' => 'Workflow test saldo entry',
            'cs_nominal' => $csNominal,
        ]);
        $response->assertStatus(200);

        return (int) CashSales::orderByDesc('cs_id')->value('cs_id');
    }

    public function test_saldo_setoran_aksi_1_has_no_linked_cash_row_and_increases_staff_saldo_on_accept(): void
    {
        $this->actingAsSuperAdminStaff();

        $staff = $this->pickStaff();
        $startingSaldo = (int) $staff->staff_saldo;
        $nominal = 200000;

        $csId = $this->insertSaldoCashSales($staff, csAksi: '1', csNominal: $nominal);

        $cs = CashSales::findOrFail($csId);
        $this->assertSame(1, (int) $cs->cs_type, 'the whole saldo branch sets cs_type=1, unlike operasional\'s cs_type=2');
        $this->assertSame(1, (int) $cs->cs_transaction, 'cs_aksi=="1" sets cs_transaction=1, same code as a Setoran');
        $this->assertSame(0, (int) $cs->cash_id, 'cs_aksi=="1" creates no linked Cash row at all — same as operasional\'s own Setoran case');

        $this->post('/acceptCashSales', ['cs_id' => $csId])->assertStatus(200);

        $staff->refresh();
        $this->assertSame($startingSaldo + $nominal, (int) $staff->staff_saldo, 'cs_type==1 && cs_aksi==1 is the ONLY branch that increases staff_saldo');
    }

    public function test_saldo_aksi_2_creates_a_linked_cash_row_and_decreases_staff_saldo_on_accept(): void
    {
        $this->actingAsSuperAdminStaff();

        $staff = $this->pickStaff();
        $staff->staff_saldo = 1000000;
        $staff->save();
        $nominal = 300000;

        $csId = $this->insertSaldoCashSales($staff, csAksi: '2', csNominal: $nominal);

        $cs = CashSales::findOrFail($csId);
        $this->assertSame(1, (int) $cs->cs_type);
        $this->assertSame(3, (int) $cs->cs_transaction, 'cs_aksi=="2" sets cs_transaction=3');
        $this->assertGreaterThan(0, (int) $cs->cash_id, 'cs_aksi=="2" creates a linked Cash row');

        $cash = Cash::find($cs->cash_id);
        $this->assertSame(3, (int) $cash->cash_type, 'a non-negative cs_nominal maps aksi=="2" to cash_type 3');
        $this->assertSame(4, (int) $cash->cash_tujuan, 'cash_tujuan=4 is Sales');
        $this->assertSame($staff->staff_id, (int) $cash->person_id);

        $this->post('/acceptCashSales', ['cs_id' => $csId])->assertStatus(200);

        $staff->refresh();
        $this->assertSame(
            1000000 - $nominal,
            (int) $staff->staff_saldo,
            'neither `cs_type==1 && cs_aksi==1` nor `cs_type==2 && cs_transaction==1` match, so this falls to the else branch: staff_saldo -= cs_nominal'
        );
    }

    public function test_saldo_aksi_3_has_an_oppositely_typed_cash_row_but_the_identical_balance_effect_as_aksi_2(): void
    {
        $this->actingAsSuperAdminStaff();

        $staff = $this->pickStaff();
        $staff->staff_saldo = 1000000;
        $staff->save();
        $nominal = 300000;

        $csId = $this->insertSaldoCashSales($staff, csAksi: '3', csNominal: $nominal);

        $cs = CashSales::findOrFail($csId);
        $this->assertSame(2, (int) $cs->cs_transaction, 'cs_aksi=="3" sets cs_transaction=2, still >= 2 like aksi=="2"\'s cs_transaction=3');

        $cash = Cash::find($cs->cash_id);
        $this->assertSame(1, (int) $cash->cash_type, 'aksi=="3" flips the SAME non-negative cs_nominal to cash_type 1 — the opposite of aksi=="2"\'s cash_type 3');

        $this->post('/acceptCashSales', ['cs_id' => $csId])->assertStatus(200);

        $staff->refresh();
        $this->assertSame(
            1000000 - $nominal,
            (int) $staff->staff_saldo,
            'BUG-adjacent finding: despite the inverted Cash-row bookkeeping, aksi=="2" and aksi=="3" produce the IDENTICAL staff_saldo effect — acceptCashSales() cannot actually distinguish them'
        );
    }
}
