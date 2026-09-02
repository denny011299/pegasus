<?php

namespace Tests\Regression;

use App\Models\Bank;
use App\Models\CashCategory;
use App\Models\Category;
use App\Models\Unit;
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
 *
 * ✅ FIXED (GitHub #128, 2026-09-03): same bug in `Category::insertCategory()`/`updateCategory()`
 * (Master Kategori) and `Unit::insertUnit()`/`updateUnit()` (Master Satuan) — mirrors the fix above.
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

    public function test_inserting_a_category_with_a_duplicate_name_is_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new Category();
        $category->category_name = 'Regression Master Category Dup';
        $category->status = 1;
        $category->save();

        $response = $this->post('/insertCategory', [
            'category_name' => ' regression master category dup ',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, Category::where('category_name', 'Regression Master Category Dup')->where('status', 1)->count());
    }

    public function test_updating_a_category_into_another_categorys_name_is_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        $categoryA = new Category();
        $categoryA->category_name = 'Regression Master Category A';
        $categoryA->status = 1;
        $categoryA->save();

        $categoryB = new Category();
        $categoryB->category_name = 'Regression Master Category B';
        $categoryB->status = 1;
        $categoryB->save();

        $response = $this->post('/updateCategory', [
            'category_id' => $categoryB->category_id,
            'category_name' => 'Regression Master Category A',
        ]);

        $response->assertStatus(422);
        $categoryB->refresh();
        $this->assertSame('Regression Master Category B', $categoryB->category_name);
    }

    public function test_inserting_a_unit_with_a_duplicate_name_is_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        $unit = new Unit();
        $unit->unit_name = 'Regression Master Unit Dup';
        $unit->unit_short_name = 'RMUD';
        $unit->status = 1;
        $unit->save();

        $response = $this->post('/insertUnit', [
            'unit_name' => ' regression master unit dup ',
            'unit_short_name' => 'RMUD2',
        ]);

        $response->assertStatus(422);
        $this->assertSame(1, Unit::where('unit_name', 'Regression Master Unit Dup')->where('status', 1)->count());
    }

    public function test_updating_a_unit_into_another_units_name_is_rejected(): void
    {
        $this->actingAsSuperAdminStaff();

        $unitA = new Unit();
        $unitA->unit_name = 'Regression Master Unit A';
        $unitA->unit_short_name = 'RMUA';
        $unitA->status = 1;
        $unitA->save();

        $unitB = new Unit();
        $unitB->unit_name = 'Regression Master Unit B';
        $unitB->unit_short_name = 'RMUB';
        $unitB->status = 1;
        $unitB->save();

        $response = $this->post('/updateUnit', [
            'unit_id' => $unitB->unit_id,
            'unit_name' => 'Regression Master Unit A',
            'unit_short_name' => 'RMUB',
        ]);

        $response->assertStatus(422);
        $unitB->refresh();
        $this->assertSame('Regression Master Unit B', $unitB->unit_name);
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
