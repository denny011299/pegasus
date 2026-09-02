<?php

namespace Tests\Unit;

use App\Support\CashOperasionalPresenter;
use PHPUnit\Framework\TestCase;

/**
 * Pure-logic tests for `CashOperasionalPresenter`'s Masuk/Keluar (debit/credit) column decision —
 * no DB, a plain stdClass row is enough since the presenter only reads named properties off it.
 *
 * GitHub #130 items 35/36/38: `ca_nominal`/`cg_nominal`/`cs_nominal` (and `cr_nominal`) are stored
 * RAW — a negative nominal on a "saldo" row means the opposite of what the aksi/type flag alone
 * says happened (a negative Pengembalian is functionally a Pengajuan, and vice versa), so the
 * column it lands in must flip too. `$user = null` throughout — RoleAccess::canAny() treats a null
 * user as "no access to anything", which only affects the `action` column, not `debit`/`credit`.
 */
class CashOperasionalPresenterSignFlipTest extends TestCase
{
    private function row(array $fields): object
    {
        return (object) array_merge([
            'status' => 1,
            'staff_id' => 1,
            'created_by' => null,
            'acc_by' => null,
            'detail' => null,
        ], $fields);
    }

    // ---- Kas Admin (item 35) ----

    public function test_admin_positive_pengajuan_is_debit(): void
    {
        $row = CashOperasionalPresenter::adminRow($this->row([
            'ca_id' => 1, 'ca_date' => '2026-09-03', 'ca_type' => 1, 'ca_aksi' => 1, 'ca_nominal' => 100000,
        ]), null);

        $this->assertSame('Rp 100.000', $row['debit']);
        $this->assertSame('Rp 0', $row['credit']);
    }

    public function test_admin_positive_pengembalian_is_credit(): void
    {
        $row = CashOperasionalPresenter::adminRow($this->row([
            'ca_id' => 1, 'ca_date' => '2026-09-03', 'ca_type' => 1, 'ca_aksi' => 2, 'ca_nominal' => 100000,
        ]), null);

        $this->assertSame('Rp 0', $row['debit']);
        $this->assertSame('(Rp 100.000)', $row['credit']);
    }

    public function test_admin_negative_pengembalian_flips_to_debit(): void
    {
        $row = CashOperasionalPresenter::adminRow($this->row([
            'ca_id' => 1, 'ca_date' => '2026-09-03', 'ca_type' => 1, 'ca_aksi' => 2, 'ca_nominal' => -100000,
        ]), null);

        $this->assertSame('Rp 100.000', $row['debit'], 'a negative pengembalian is functionally a pengajuan — must land in Masuk');
        $this->assertSame('Rp 0', $row['credit']);
    }

    public function test_admin_negative_pengajuan_flips_to_credit(): void
    {
        $row = CashOperasionalPresenter::adminRow($this->row([
            'ca_id' => 1, 'ca_date' => '2026-09-03', 'ca_type' => 1, 'ca_aksi' => 1, 'ca_nominal' => -100000,
        ]), null);

        $this->assertSame('Rp 0', $row['debit']);
        $this->assertSame('(Rp 100.000)', $row['credit']);
    }

    public function test_admin_operasional_expense_rows_are_untouched_by_the_sign_flip(): void
    {
        // ca_type==2 (aktivitas operasional/expense) rows are out of scope for item 35 — a stray
        // ca_aksi==1 default (see insertCashAdmin's operasional branch, which never overrides it)
        // must keep behaving exactly as before: no sign-based flip applied.
        $row = CashOperasionalPresenter::adminRow($this->row([
            'ca_id' => 1, 'ca_date' => '2026-09-03', 'ca_type' => 2, 'ca_aksi' => 1, 'ca_nominal' => -50000,
        ]), null);

        $this->assertSame('Rp 50.000', $row['debit'], 'unchanged: ca_type!=1 rows never get the sign flip');
        $this->assertSame('Rp 0', $row['credit']);
    }

    // ---- Kas Gudang (item 35) ----

    public function test_gudang_negative_pengembalian_flips_to_debit(): void
    {
        $row = CashOperasionalPresenter::gudangRow($this->row([
            'cg_id' => 1, 'cg_date' => '2026-09-03', 'cg_type' => 1, 'cg_aksi' => 2, 'cg_nominal' => -75000,
        ]), null);

        $this->assertSame('Rp 75.000', $row['debit']);
        $this->assertSame('Rp 0', $row['credit']);
    }

    // ---- Dompet Armada (item 36) ----

    public function test_armada_saldo_positive_pengembalian_is_credit(): void
    {
        // saldo branch never sets cr_type (defaults to 3), cr_aksi is forced to 1 by insertCashArmada().
        $row = CashOperasionalPresenter::armadaRow($this->row([
            'cr_id' => 1, 'cr_date' => '2026-09-03', 'cr_type' => 3, 'cr_aksi' => 1, 'cr_nominal' => 200000,
            'customer_id' => 1,
        ]), null);

        $this->assertSame('Rp 0', $row['debit']);
        $this->assertSame('(Rp 200.000)', $row['credit']);
    }

    public function test_armada_saldo_negative_pengembalian_flips_to_debit(): void
    {
        $row = CashOperasionalPresenter::armadaRow($this->row([
            'cr_id' => 1, 'cr_date' => '2026-09-03', 'cr_type' => 3, 'cr_aksi' => 1, 'cr_nominal' => -200000,
            'customer_id' => 1,
        ]), null);

        $this->assertSame('Rp 200.000', $row['debit'], 'a negative pengembalian dompet armada is functionally an addition — must land in Masuk');
        $this->assertSame('Rp 0', $row['credit']);
    }

    public function test_armada_operasional_setoran_is_unaffected_by_the_saldo_sign_flip(): void
    {
        // operasional/Setoran rows (cr_aksi==2, cr_type==1) must keep using cr_type exactly as
        // before — the sign-flip only applies to the saldo (cr_aksi==1) branch.
        $row = CashOperasionalPresenter::armadaRow($this->row([
            'cr_id' => 1, 'cr_date' => '2026-09-03', 'cr_type' => 1, 'cr_aksi' => 2, 'cr_nominal' => 50000,
            'customer_id' => 1,
        ]), null);

        $this->assertSame('Rp 50.000', $row['debit']);
        $this->assertSame('Rp 0', $row['credit']);
    }

    public function test_armada_cross_created_gudang_delivery_row_is_unaffected(): void
    {
        // CashGudang::acceptCashGudang() cross-creates CashArmada rows with cr_type explicitly 1
        // and cr_aksi left at its 0 default — must still resolve to Masuk via cr_type, not get
        // caught by the cr_aksi==1 sign-flip branch (which is for the saldo flow specifically).
        $row = CashOperasionalPresenter::armadaRow($this->row([
            'cr_id' => 1, 'cr_date' => '2026-09-03', 'cr_type' => 1, 'cr_aksi' => 0, 'cr_nominal' => 60000,
            'customer_id' => 1,
        ]), null);

        $this->assertSame('Rp 60.000', $row['debit']);
        $this->assertSame('Rp 0', $row['credit']);
    }

    // ---- Dompet Sales (item 38) ----

    public function test_sales_saldo_positive_pengembalian_is_credit(): void
    {
        $row = CashOperasionalPresenter::salesRow($this->row([
            'cs_id' => 1, 'cs_date' => '2026-09-03', 'cs_type' => 1, 'cs_transaction' => 2, 'cs_aksi' => 3,
            'cs_nominal' => 150000,
        ]), null);

        $this->assertSame('Rp 0', $row['debit']);
        $this->assertSame('(Rp 150.000)', $row['credit']);
    }

    public function test_sales_saldo_negative_pengembalian_flips_to_debit(): void
    {
        $row = CashOperasionalPresenter::salesRow($this->row([
            'cs_id' => 1, 'cs_date' => '2026-09-03', 'cs_type' => 1, 'cs_transaction' => 2, 'cs_aksi' => 3,
            'cs_nominal' => -150000,
        ]), null);

        $this->assertSame('Rp 150.000', $row['debit']);
        $this->assertSame('Rp 0', $row['credit']);
    }

    public function test_sales_setor_ke_bank_is_unaffected_by_the_pengembalian_sign_flip(): void
    {
        // aksi==2 (Setor ke Bank) is guarded against ever being negative at insert/update time
        // (item 39) — its own column logic (unaffected by the pengembalian-only sign flip) must
        // keep behaving exactly as before.
        $row = CashOperasionalPresenter::salesRow($this->row([
            'cs_id' => 1, 'cs_date' => '2026-09-03', 'cs_type' => 1, 'cs_transaction' => 3, 'cs_aksi' => 2,
            'cs_nominal' => 90000,
        ]), null);

        $this->assertSame('Rp 0', $row['debit']);
        $this->assertSame('(Rp 90.000)', $row['credit']);
    }

    public function test_sales_pemasukan_is_unaffected_by_the_pengembalian_sign_flip(): void
    {
        $row = CashOperasionalPresenter::salesRow($this->row([
            'cs_id' => 1, 'cs_date' => '2026-09-03', 'cs_type' => 1, 'cs_transaction' => 1, 'cs_aksi' => 1,
            'cs_nominal' => 40000,
        ]), null);

        $this->assertSame('Rp 40.000', $row['debit']);
        $this->assertSame('Rp 0', $row['credit']);
    }
}
