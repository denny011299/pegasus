<?php

namespace Tests\Regression;

use App\Models\CashCategory;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-06): `CashCategory::updateCashCategory()`/`deleteCashCategory()` had no
 * null-guard on `CashCategory::find($data["cc_id"])` — an invalid/already-deleted `cc_id` crashed
 * 500 ("Attempt to assign property 'cc_name' on null" / "... 'status' on null") instead of failing
 * cleanly. Same shape as several other simple master-data CRUD models in this codebase
 * (`Bank::updateBank()`/`deleteBank()` has the identical gap, out of scope for this fix) — mirrors
 * the quiet null-guard pattern already used in `StockOpname::updateStockOpname()`/
 * `deleteStockOpname()`.
 */
class CashCategoryInvalidIdCrashesTest extends TestCase
{
    use ActingAsStaff;

    public function test_updating_an_invalid_cc_id_fails_cleanly_instead_of_crashing(): void
    {
        $this->actingAsSuperAdminStaff();

        $bogusId = 999999;
        $this->assertNull(CashCategory::find($bogusId), 'precondition: id must not exist');

        $response = $this->post('/updateCashCategory', [
            'cc_id' => $bogusId,
            'cc_name' => 'Should not be applied',
            'cc_type' => 'Masuk',
        ]);

        $response->assertStatus(200);
    }

    public function test_deleting_an_invalid_cc_id_fails_cleanly_instead_of_crashing(): void
    {
        $this->actingAsSuperAdminStaff();

        $bogusId = 999999;
        $this->assertNull(CashCategory::find($bogusId), 'precondition: id must not exist');

        $response = $this->post('/deleteCashCategory', [
            'cc_id' => $bogusId,
        ]);

        $response->assertStatus(200);
    }

    public function test_updating_a_real_category_still_works(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new CashCategory();
        $category->cc_name = 'Regression Test Category';
        $category->cc_type = 'Keluar';
        $category->status = 1;
        $category->save();

        $response = $this->post('/updateCashCategory', [
            'cc_id' => $category->cc_id,
            'cc_name' => 'Regression Test Category Renamed',
            'cc_type' => 'Masuk',
        ]);
        $response->assertStatus(200);

        $category->refresh();
        $this->assertSame('Regression Test Category Renamed', $category->cc_name);
        $this->assertSame('Masuk', $category->cc_type);
    }

    public function test_deleting_a_real_category_still_soft_deletes_it(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new CashCategory();
        $category->cc_name = 'Regression Test Category To Delete';
        $category->cc_type = 'Keluar 1';
        $category->status = 1;
        $category->save();

        $response = $this->post('/deleteCashCategory', [
            'cc_id' => $category->cc_id,
        ]);
        $response->assertStatus(200);

        $category->refresh();
        $this->assertSame(0, (int) $category->status);
    }
}
