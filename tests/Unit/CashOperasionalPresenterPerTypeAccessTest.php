<?php

namespace Tests\Unit;

use App\Support\CashOperasionalPresenter;
use PHPUnit\Framework\TestCase;

/**
 * GitHub #196 item 28: a role scoped only to a per-type submodule ("Kas Operasional Gudang",
 * "Kas Armada", ...) could already reach its `/getCash*` route (the `check.access.any` middleware
 * in routes/web.php recognizes those exact names), but the presenter's action-column buttons used
 * to check only the generic "Kas"/"Kas Operasional" modules — so ACC/Tolak (and even the view/edit/
 * delete icons) silently never rendered for such a role. No DB needed; a plain stdClass row plus a
 * user with a hand-built role_access payload is enough.
 */
class CashOperasionalPresenterPerTypeAccessTest extends TestCase
{
    private function user(string $module, array $akses): object
    {
        return (object) [
            'role_id' => 5,
            'role_access' => json_encode([
                ['name' => $module, 'akses' => $akses],
            ]),
        ];
    }

    private function pendingOperasionalRow(array $fields): object
    {
        return (object) array_merge([
            'status' => 1,
            'staff_id' => 1,
            'created_by' => null,
            'acc_by' => null,
            'detail' => null,
        ], $fields);
    }

    public function test_gudang_row_shows_acc_tolak_for_role_scoped_to_gudang_submodule_only(): void
    {
        $user = $this->user('Kas Operasional Gudang', ['view', 'others']);
        $row = CashOperasionalPresenter::gudangRow($this->pendingOperasionalRow([
            'cg_id' => 1, 'cg_date' => '2026-09-20', 'cg_type' => 2, 'cg_aksi' => 2, 'cg_nominal' => 20000,
            'cash_id' => 1,
        ]), $user);

        $this->assertStringContainsString('btn_acc', $row['action']);
        $this->assertStringContainsString('btn_decline', $row['action']);
    }

    public function test_armada_row_shows_acc_tolak_for_role_scoped_to_armada_submodule_only(): void
    {
        $user = $this->user('Kas Armada', ['view', 'others']);
        $row = CashOperasionalPresenter::armadaRow($this->pendingOperasionalRow([
            'cr_id' => 1, 'cr_date' => '2026-09-20', 'cr_type' => 2, 'cr_aksi' => 2, 'cr_nominal' => 20000,
            'cash_id' => 1,
        ]), $user);

        $this->assertStringContainsString('btn_acc', $row['action']);
    }

    public function test_sales_row_shows_acc_tolak_for_role_scoped_to_sales_submodule_only(): void
    {
        $user = $this->user('Kas Operasional Sales', ['view', 'others']);
        $row = CashOperasionalPresenter::salesRow($this->pendingOperasionalRow([
            'cs_id' => 1, 'cs_date' => '2026-09-20', 'cs_type' => 2, 'cs_aksi' => 1, 'cs_nominal' => 20000,
            'cash_id' => 1,
        ]), $user);

        $this->assertStringContainsString('btn_acc', $row['action']);
    }

    public function test_admin_row_still_hides_acc_tolak_without_others_permission(): void
    {
        $user = $this->user('Kas Operasional Admin', ['view']);
        $row = CashOperasionalPresenter::adminRow($this->pendingOperasionalRow([
            'ca_id' => 1, 'ca_date' => '2026-09-20', 'ca_type' => 2, 'ca_aksi' => 2, 'ca_nominal' => 20000,
            'cash_id' => 1,
        ]), $user);

        $this->assertStringNotContainsString('btn_acc', $row['action']);
        $this->assertStringContainsString('btn_view_admin', $row['action']);
    }

    public function test_generic_kas_operasional_module_still_grants_access_to_every_type(): void
    {
        $user = $this->user('Kas Operasional', ['view', 'others']);
        $row = CashOperasionalPresenter::gudangRow($this->pendingOperasionalRow([
            'cg_id' => 1, 'cg_date' => '2026-09-20', 'cg_type' => 2, 'cg_aksi' => 2, 'cg_nominal' => 20000,
            'cash_id' => 1,
        ]), $user);

        $this->assertStringContainsString('btn_acc', $row['action']);
    }
}
