<?php

namespace Tests\Regression;

use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Confirmed 2026-08-09, not fixed (documented per policy, same shape as
 * PurchaseOrderPelunasanIsNoOpTest): /insertStockAlert, /deleteStockAlert,
 * /insertStockAlertSupplies, and /deleteStockAlertSupplies are all wired up as real routes behind
 * check.access:...|create / ...|delete, but their controller methods
 * (StockAlert::insertStockAlert()/deleteStockAlert(), StockAlertSupplies's equivalents) always
 * return `{success: false, message: 'Not implemented'}` regardless of input — there is no insert
 * or delete concept for a stock alert row (a "Peringatan Stok" setting lives on the product/supply
 * itself, only ever created implicitly and only ever mutated via updateStockAlert/updateMinOrder).
 * The Blade UI for both pages never calls these four endpoints; only a direct HTTP call would hit
 * them. See KNOWN_ISSUES.md.
 */
class StockAlertInsertDeleteAreNoOpsTest extends TestCase
{
    use ActingAsStaff;

    public function test_insertStockAlert_is_always_not_implemented(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->post('/insertStockAlert', ['product_variant_id' => 1])
            ->assertJson(['success' => false, 'message' => 'Not implemented']);
    }

    public function test_deleteStockAlert_is_always_not_implemented(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->post('/deleteStockAlert', ['product_variant_id' => 1])
            ->assertJson(['success' => false, 'message' => 'Not implemented']);
    }

    public function test_insertStockAlertSupplies_is_always_not_implemented(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->post('/insertStockAlertSupplies', ['supplies_id' => 1])
            ->assertJson(['success' => false, 'message' => 'Not implemented']);
    }

    public function test_deleteStockAlertSupplies_is_always_not_implemented(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->post('/deleteStockAlertSupplies', ['supplies_id' => 1])
            ->assertJson(['success' => false, 'message' => 'Not implemented']);
    }
}
