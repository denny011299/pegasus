<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerProductReturn;
use App\Models\CustomerProductReturnDetail;
use App\Models\CustomerSupplyReturn;
use App\Models\CustomerSupplyReturnDetail;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplies;
use App\Models\Unit;
use App\Models\Warehouse;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #203 follow-up (2026-09-25, final decision after a same-day back-and-forth — see
 * ShipmentReturnController's class docblock): a warehouse_id-NULL detail row on a Pengembalian
 * is now ONLY possible for a produk satuan eceran line whose PMO caller didn't send gudang_id
 * (resolveProductWarehouses()) — bahan mentah and non-eceran produk lines always auto-default to
 * the main warehouse (resolveSupplyWarehouses()), same as before GitHub #203 originally shipped
 * (2026-08-17), plus gudang_id is now honored for every line type when the caller does send it.
 *
 * This still exposed two admin-side bugs, both fixed here and still relevant since a
 * warehouse_id-NULL row remains a normal (if less common) state for eceran lines:
 *
 * 1. CustomerReturnController::resolveBundle() — the query backing GET /customerReturns/{docKey}
 *    (fills the admin "Edit Pengembalian" modal) joined to `warehouses` with an INNER JOIN on
 *    warehouse_id. A NULL warehouse_id silently excluded the whole detail row from the response —
 *    so the exact rows that most need manual assignment never appeared in the modal for a staff
 *    member to fix. Fixed by switching both queries (supply and product side) to leftJoin.
 *    (Exercised here via a bahan mentah fixture built directly via Eloquent, bypassing the API,
 *    to prove the admin controller's OWN robustness independent of what the API can currently
 *    produce — a manually-edited or legacy row could still end up NULL on either side.)
 *
 * 2. The LIST query (buildUnifiedRows() -> applyWarehouseScope() -> ...
 *    applyProductDetailWarehouseFilter()) filters product detail rows by
 *    `warehouse_id = <active warehouse>` — a NULL warehouse_id never equals any specific warehouse
 *    id, so a document with an unassigned eceran line was invisible in the list under EVERY active
 *    warehouse (reported by the user 2026-09-25 against real production data: return "PKR0054",
 *    product_return_id=43, never showed up in the admin list at all, even though
 *    GET /customerReturns/{docKey} could still find it directly by doc_key). Fixed by treating a
 *    NULL warehouse_id product detail row as belonging to the MAIN warehouse for list-visibility
 *    purposes only (nothing is written to the column) — it shows up while viewing the main
 *    warehouse (where an unassigned return is expected to be managed from), not every warehouse.
 *    The supply side never needed this: a bahan mentah row is never NULL via the API, so its list
 *    query stays a plain warehouse_id match.
 *
 * Also documents that CustomerReturnController::acceptSupply()/validateSupplyDetails() already
 * rejects accept() for ANY detail row with a null warehouse_id — this was already true before
 * GitHub #203, just never exercised for a bahan mentah row.
 */
class PengembalianNullWarehouseAdminEditTest extends TestCase
{
    use ActingAsStaff;

    private function mainWarehouseId(): int
    {
        return (int) Warehouse::query()
            ->where('warehouses.status', 1)
            ->whereHas('type', fn ($q) => $q->where('status', 1)->where('is_main_warehouse', 1))
            ->orderBy('warehouses.id')
            ->value('id');
    }

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Null Warehouse Return Test Armada';
        $customer->customer_code = 'NWR'.random_int(1000, 9999);
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function staffId(): int
    {
        return (int) \Illuminate\Support\Facades\DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    private function makeSupplyReturnWithNullWarehouse(): array
    {
        $unit = Unit::where('status', 1)->first();
        $this->assertNotNull($unit, 'fixture butuh minimal 1 satuan aktif');

        $supplies = new Supplies();
        $supplies->supplies_name = 'Null Warehouse Return Test Supplies '.uniqid();
        $supplies->supplies_unit = json_encode([$unit->unit_id]);
        $supplies->supplies_default_unit = $unit->unit_id;
        $supplies->status = 1;
        $supplies->save();

        $customer = $this->createArmada();

        $record = new CustomerSupplyReturn();
        $record->return_number = 'PBM-NWR-'.uniqid();
        $record->return_group = 'PKR-NWR-'.uniqid();
        $record->customer_id = $customer->customer_id;
        $record->return_date = now()->toDateString();
        $record->proof_path = null;
        $record->status = 1;
        $record->created_by = $this->staffId();
        $record->save();

        $detail = new CustomerSupplyReturnDetail();
        $detail->return_id = $record->return_id;
        $detail->supplies_id = $supplies->supplies_id;
        $detail->unit_id = $unit->unit_id;
        $detail->warehouse_id = null;
        $detail->qty = 4;
        $detail->status = 1;
        $detail->save();

        return ['record' => $record, 'supplies' => $supplies, 'unit' => $unit];
    }

    /** A produk satuan eceran line whose warehouse was never assigned — the one case that's still possible. */
    private function makeProductReturnWithNullWarehouse(): array
    {
        $unit = Unit::where('status', 1)->first();
        $this->assertNotNull($unit, 'fixture butuh minimal 1 satuan aktif');

        $category = new Category();
        $category->category_name = 'Null Warehouse Return Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Null Warehouse Return Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Null Warehouse Return Test Variant';
        $variant->product_variant_sku = 'NWR-TEST-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->retail_unit = $unit->unit_id;
        $variant->status = 1;
        $variant->save();

        $customer = $this->createArmada();

        $record = new CustomerProductReturn();
        $record->return_number = 'PBJ-NWR-'.uniqid();
        $record->return_group = 'PKR-NWR-'.uniqid();
        $record->customer_id = $customer->customer_id;
        $record->return_date = now()->toDateString();
        $record->proof_path = null;
        $record->status = 1;
        $record->created_by = $this->staffId();
        $record->save();

        $detail = new CustomerProductReturnDetail();
        $detail->return_id = $record->return_id;
        $detail->product_variant_id = $variant->product_variant_id;
        $detail->unit_id = $unit->unit_id;
        $detail->warehouse_id = null;
        $detail->qty = 3;
        $detail->status = 1;
        $detail->save();

        return ['record' => $record, 'variant' => $variant, 'unit' => $unit];
    }

    public function test_show_returns_a_bahan_mentah_line_with_a_null_warehouse_instead_of_silently_dropping_it(): void
    {
        $this->actingAsSuperAdminStaff();
        $fx = $this->makeSupplyReturnWithNullWarehouse();

        $response = $this->getJson('/customerReturns/S:'.$fx['record']->return_id);

        $response->assertOk();
        $details = $response->json('supply_details');
        $this->assertCount(1, $details, 'BUG WOULD BE: an INNER JOIN to warehouses silently drops the row when warehouse_id is NULL');
        $this->assertSame($fx['supplies']->supplies_id, (int) $details[0]['supplies_id']);
        $this->assertNull($details[0]['warehouse_id']);
        $this->assertNull($details[0]['warehouse_name']);
    }

    public function test_accept_rejects_a_bahan_mentah_line_with_a_null_warehouse(): void
    {
        $this->actingAsSuperAdminStaff();
        $fx = $this->makeSupplyReturnWithNullWarehouse();

        $response = $this->postJson('/customerReturns/S:'.$fx['record']->return_id.'/accept');

        $response->assertStatus(422);
        $this->assertSame(1, (int) CustomerSupplyReturn::find($fx['record']->return_id)->status, 'the document must stay Pending, not be accepted with an unresolved warehouse');
    }

    public function test_accept_succeeds_once_the_warehouse_is_filled_in(): void
    {
        $this->actingAsSuperAdminStaff();
        $fx = $this->makeSupplyReturnWithNullWarehouse();
        $mainWarehouseId = $this->mainWarehouseId();
        $this->assertGreaterThan(0, $mainWarehouseId, 'fixture needs a main warehouse (is_main_warehouse=1) in the seeded data');

        CustomerSupplyReturnDetail::where('return_id', $fx['record']->return_id)
            ->update(['warehouse_id' => $mainWarehouseId]);

        $response = $this->postJson('/customerReturns/S:'.$fx['record']->return_id.'/accept');

        $response->assertOk();
        $this->assertSame(2, (int) CustomerSupplyReturn::find($fx['record']->return_id)->status, 'status 2 = accepted');
    }

    public function test_list_surfaces_an_unassigned_eceran_produk_line_while_viewing_the_main_warehouse(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse($this->mainWarehouseId());
        $fx = $this->makeProductReturnWithNullWarehouse();

        $response = $this->getJson('/customerReturns?search[value]='.$fx['record']->return_group);

        $response->assertOk();
        $rows = $response->json('data');
        $this->assertCount(1, $rows, 'BUG WOULD BE: a warehouse_id = <active warehouse> filter never matches a NULL warehouse_id, so the document never shows up while viewing the main warehouse either');
        $this->assertSame($fx['record']->return_group, $rows[0]['return_number']);
    }
}
