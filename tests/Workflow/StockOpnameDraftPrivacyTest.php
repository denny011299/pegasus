<?php

namespace Tests\Workflow;

use App\Models\ProductStock;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #115: a draft must (1) never leak system stock — only what the staff has typed
 * themselves — and (2) stay invisible/uneditable to every staff except its own creator (or a
 * super admin), including a direct-by-URL open of its detail page (the listing's own filter,
 * StockOpname::getStockOpname(), is intentionally skipped for a by-id lookup — see its own
 * comment — so the guard has to live in the controller instead).
 */
class StockOpnameDraftPrivacyTest extends TestCase
{
    use ActingAsStaff;

    private function pickFixtureStock(): ProductStock
    {
        return ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1) // Gudang Pusat (main), seeded 2026-08-01
            ->where('ps_stock', '>', 10)
            ->firstOrFail();
    }

    private function categoryId(): int
    {
        return (int) DB::table('categories')->where('status', 1)->value('category_id');
    }

    private function insertDraft(ProductStock $stock, int $realQty): int
    {
        $response = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => session('user')->staff_id,
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Draft privacy test',
            'is_draft' => 1,
            'item' => json_encode([[
                'product_id' => $stock->product_id,
                'product_variant_id' => $stock->product_variant_id,
                'units' => [[
                    'unit_id' => $stock->unit_id,
                    'system_qty' => $stock->ps_stock,
                    'real_qty' => $realQty,
                ]],
                'stod_system' => $stock->ps_stock.' pcs',
                'stod_real' => $realQty.' pcs',
                'stod_selisih' => ($realQty - $stock->ps_stock).' pcs',
                'stod_notes' => null,
            ]]),
        ]);
        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);

        return (int) $response->json('sto_id');
    }

    public function test_draft_detail_page_never_exposes_system_stock(): void
    {
        $owner = $this->actingAsStaffWithOnlyPermission('Stok Opname Produk', ['view', 'create', 'edit']);

        $stock = $this->pickFixtureStock();
        $realQty = $stock->ps_stock - 3; // deliberately different from system, so a leak is obvious
        $stoId = $this->insertDraft($stock, $realQty);

        $sto = StockOpname::find($stoId);
        $this->assertTrue((bool) $sto->is_draft);

        $page = $this->get('/detailStockOpname/'.$stoId);
        $page->assertStatus(200);

        // Pull the `var data = {...};` payload the blade hands the page's JS (CreateStockOpname.js
        // reads item[].units[].system_qty/live_qty straight from this) instead of eyeballing raw
        // HTML text, which can coincidentally contain the same digits for unrelated reasons (ids,
        // dates, csrf tokens, ...).
        preg_match('/var data = (\{.*?\});\s*\n\s*var mode/s', $page->getContent(), $m);
        $this->assertNotEmpty($m, 'could not find the data payload in the rendered page');
        $payload = json_decode($m[1], true);

        $this->assertTrue($payload['is_draft']);
        $unit = $payload['item'][0]['units'][0];
        $this->assertSame($realQty, $unit['real_qty'], 'the value the staff actually typed must still show');
        $this->assertNull($unit['system_qty'], 'a draft must never carry a system stock value at all');
        $this->assertNull($unit['live_qty'], 'a draft must never carry a live stock value either');

        $owner_id = $owner->staff_id;
        $this->assertSame($owner_id, $sto->created_by);
    }

    public function test_other_staff_cannot_open_another_staffs_draft_by_url(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Opname Produk', ['view', 'create', 'edit'], ['staff_id' => 555001]);
        $stock = $this->pickFixtureStock();
        $stoId = $this->insertDraft($stock, $stock->ps_stock - 1);

        // A different staff, same module access, but not the creator.
        $this->actingAsStaffWithOnlyPermission('Stok Opname Produk', ['view', 'create', 'edit'], ['staff_id' => 555002]);

        $this->get('/detailStockOpname/'.$stoId)->assertStatus(404);
        $this->get('/generateStockOpname/'.$stoId)->assertStatus(404);

        $this->post('/updateStockOpname', [
            'sto_id' => $stoId,
            'item' => json_encode([]),
        ])->assertJson(['status' => -1]);

        $this->post('/submitStockOpname', ['sto_id' => $stoId])
            ->assertJson(['status' => -1]);
    }

    public function test_owner_can_still_open_their_own_draft_by_url(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Opname Produk', ['view', 'create', 'edit'], ['staff_id' => 555003]);
        $stock = $this->pickFixtureStock();
        $stoId = $this->insertDraft($stock, $stock->ps_stock - 1);

        $this->get('/detailStockOpname/'.$stoId)->assertStatus(200);
    }

    public function test_super_admin_can_open_any_staffs_draft(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Opname Produk', ['view', 'create', 'edit'], ['staff_id' => 555004]);
        $stock = $this->pickFixtureStock();
        $stoId = $this->insertDraft($stock, $stock->ps_stock - 1);

        $this->actingAsSuperAdminStaff();
        $this->get('/detailStockOpname/'.$stoId)->assertStatus(200);
    }

    /**
     * 2026-09-04: a draft has no snapshot at all (identity/system stock only freeze at publish,
     * GitHub #115) -- the Print/PDF icon is already hidden client-side for a draft row
     * (Stock_Opname.js's `item.is_draft` check), but that alone is only UI hiding, not
     * enforcement. Hitting /generateStockOpname/{id} directly must 404 for a DRAFT regardless of
     * who's asking -- even the document's own owner (detailStockOpname()'s "am I allowed to see
     * this draft at all" check on its own is NOT enough here, since the owner legitimately passes
     * that and would otherwise reach a PDF of half-typed data).
     */
    public function test_owner_cannot_generate_pdf_of_their_own_draft(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Opname Produk', ['view', 'create', 'edit'], ['staff_id' => 555005]);
        $stock = $this->pickFixtureStock();
        $stoId = $this->insertDraft($stock, $stock->ps_stock - 1);

        $this->get('/generateStockOpname/'.$stoId)->assertStatus(404);
    }

    public function test_super_admin_cannot_generate_pdf_of_a_draft_either(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Opname Produk', ['view', 'create', 'edit'], ['staff_id' => 555006]);
        $stock = $this->pickFixtureStock();
        $stoId = $this->insertDraft($stock, $stock->ps_stock - 1);

        $this->actingAsSuperAdminStaff();
        $this->get('/generateStockOpname/'.$stoId)->assertStatus(404);
    }

    /** Once the draft is submitted (out of draft), the PDF must work again -- the guard is is_draft-only. */
    public function test_pdf_works_again_once_the_draft_is_submitted(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Opname Produk', ['view', 'create', 'edit'], ['staff_id' => 555007]);
        $stock = $this->pickFixtureStock();
        $stoId = $this->insertDraft($stock, $stock->ps_stock - 1);

        $this->post('/submitStockOpname', ['sto_id' => $stoId, 'rollup_decision' => 'skip'])->assertStatus(200);

        $response = $this->get('/generateStockOpname/'.$stoId);
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
