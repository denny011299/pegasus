<?php

namespace Tests\Regression;

use App\Models\Cash;
use App\Models\CashSales;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Kas Besar shows ACC for users with Kas|others, but acceptCashSales route
 * did not include Kas in its check.access.any list — 403 despite visible buttons.
 */
class CashBesarAcceptPermissionTest extends TestCase
{
    use ActingAsStaff;

    public function test_kas_others_can_accept_sales_linked_cash_from_besar_page(): void
    {
        $pending = Cash::where('status', 1)
            ->where('cash_tujuan', 4)
            ->orderByDesc('cash_id')
            ->first();

        if (! $pending) {
            $this->markTestSkipped('No pending sales-linked cash row in DB.');
        }

        $cs = CashSales::where('cash_id', $pending->cash_id)->where('status', 1)->first();
        if (! $cs) {
            $this->markTestSkipped('No pending CashSales for cash_id '.$pending->cash_id);
        }

        $this->actingAsStaffWithOnlyPermission('Kas', ['others']);

        $this->post('/acceptCashSales', ['cash_id' => $pending->cash_id])
            ->assertStatus(200);
    }
}
