<?php

namespace Tests\Regression;

use App\Models\Bank;
use App\Models\CashCategory;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (GitHub #122, 2026-09-02): `Bank::insertBank()`/`updateBank()` and
 * `CashCategory::insertCashCategory()`/`updateCashCategory()` had no uniqueness check at all —
 * inserting (or renaming into) a name that already existed on another active row was silently
 * accepted, so the same Bank Account / Kategori Kas name could exist multiple times. Both models
 * now reject a duplicate name (case-insensitive, trimmed, scoped to active/`status=1` rows) with a
 * 422 + message, matching the `response()->json(['message' => ...], 422)` convention used
 * elsewhere in this codebase.
 */
class DuplicateNameGuardTest extends TestCase
{
    use ActingAsStaff;

    public function test_inserting_a_bank_with_a_duplicate_name_is_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        $bank = new Bank();
        $bank->bank_kode = 'Regression Bank Dup';
        $bank->status = 1;
        $bank->save();

        $response = $this->post('/insertBank', [
            'bank_kode' => ' regression bank dup ',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, Bank::where('bank_kode', 'Regression Bank Dup')->where('status', 1)->count());
    }

    public function test_updating_a_bank_into_another_banks_name_is_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        $bankA = new Bank();
        $bankA->bank_kode = 'Regression Bank A';
        $bankA->status = 1;
        $bankA->save();

        $bankB = new Bank();
        $bankB->bank_kode = 'Regression Bank B';
        $bankB->status = 1;
        $bankB->save();

        $response = $this->post('/updateBank', [
            'bank_id' => $bankB->bank_id,
            'bank_kode' => 'Regression Bank A',
        ]);

        $response->assertStatus(422);
        $bankB->refresh();
        $this->assertSame('Regression Bank B', $bankB->bank_kode);
    }

    public function test_updating_a_bank_with_its_own_unchanged_name_still_works(): void
    {
        $this->actingAsSuperAdminStaff();

        $bank = new Bank();
        $bank->bank_kode = 'Regression Bank Self';
        $bank->status = 1;
        $bank->save();

        $response = $this->post('/updateBank', [
            'bank_id' => $bank->bank_id,
            'bank_kode' => 'Regression Bank Self',
        ]);

        $response->assertStatus(200);
    }

    public function test_inserting_a_cash_category_with_a_duplicate_name_is_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new CashCategory();
        $category->cc_name = 'Regression Category Dup';
        $category->cc_type = 'Keluar';
        $category->status = 1;
        $category->save();

        $response = $this->post('/insertCashCategory', [
            'cc_name' => ' regression category dup ',
            'cc_type' => 'Masuk',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, CashCategory::where('cc_name', 'Regression Category Dup')->where('status', 1)->count());
    }

    public function test_updating_a_cash_category_into_another_categorys_name_is_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        $categoryA = new CashCategory();
        $categoryA->cc_name = 'Regression Category A';
        $categoryA->cc_type = 'Keluar';
        $categoryA->status = 1;
        $categoryA->save();

        $categoryB = new CashCategory();
        $categoryB->cc_name = 'Regression Category B';
        $categoryB->cc_type = 'Masuk';
        $categoryB->status = 1;
        $categoryB->save();

        $response = $this->post('/updateCashCategory', [
            'cc_id' => $categoryB->cc_id,
            'cc_name' => 'Regression Category A',
            'cc_type' => 'Masuk',
        ]);

        $response->assertStatus(422);
        $categoryB->refresh();
        $this->assertSame('Regression Category B', $categoryB->cc_name);
    }

    public function test_a_soft_deleted_bank_name_can_be_reused(): void
    {
        $this->actingAsSuperAdminStaff();

        $bank = new Bank();
        $bank->bank_kode = 'Regression Bank Deleted';
        $bank->status = 0; // sudah dihapus (soft delete)
        $bank->save();

        $response = $this->post('/insertBank', [
            'bank_kode' => 'Regression Bank Deleted',
        ]);

        $response->assertStatus(200);
    }
}
