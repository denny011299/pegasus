<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerProductReturn;
use App\Models\CustomerProductReturnDetail;
use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * "Pengembalian Produk Jadi" (fase 2) — an armada returns finished goods, a
 * QC/warehouse user accepts or declines, and only an ACCEPT puts stock back.
 *
 * Covers CustomerProductReturnController::accept/decline/destroy. The store
 * endpoint is deliberately NOT driven here: it requires a real uploaded proof
 * image (storeProof() rejects anything that isn't decodable JPEG/PNG/WebP) and
 * writes it into a real public/upload path that tests don't clean up. Every
 * business rule store() enforces lives in validateDetails(), which accept()
 * re-runs against the persisted rows — so the rules are reachable, and pinned
 * below, without putting a file on disk. Records are therefore built straight
 * through Eloquent, per this program's fixture convention.
 *
 * All calls go through postJson(): these endpoints are consumed by the app's
 * own jQuery/DataTables frontend over AJAX, and a ValidationException only
 * surfaces as a 422 when the request asks for JSON — a plain form POST gets a
 * 302 redirect-back instead. Asserting 422 on a plain post() would be testing
 * a request shape the real UI never sends.
 *
 * Status vocabulary (from the create migration's own column comment):
 *   0 = deleted, 1 = pending, 2 = accepted, 3 = declined.
 *
 * Worth noting against the rest of this suite: unlike Stock Opname, whose
 * repeat-approval non-idempotency is a confirmed open bug (see
 * KNOWN_ISSUES.md), this flow DOES guard re-processing — accept() and
 * decline() both re-read the row under lockForUpdate() and refuse anything
 * whose status is no longer 1. That guard is asserted here so a refactor
 * can't quietly drop it and reintroduce double-crediting.
 */
class CustomerProductReturnQcFlowTest extends TestCase
{
    use ActingAsStaff;

    /** Seeded, active unit ids — same ones StockTransferWorkflowTest relies on. */
    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;

    private int $customerId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsSuperAdminStaff();
        $this->customerId = $this->makeCustomer();
    }

    private function makeCustomer(): int
    {
        $c = new Customer();
        $c->customer_name = 'Armada Retur '.uniqid();
        // customers.customer_code is varchar(10) with a unique index
        // (2026_08_11_120000) — 3 + 7 chars is the most that fits.
        $c->customer_code = 'CPR'.strtoupper(substr(uniqid(), -7));
        $c->customer_notes = 'Armada QA';
        $c->status = 1;
        $c->save();

        return (int) $c->customer_id;
    }

    /** @return array{product: Product, variant: ProductVariant} */
    private function makeProduct(?int $retailUnit = null): array
    {
        $category = new Category();
        $category->category_name = 'CPR Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'CPR Test Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID, self::DOS_UNIT_ID]);
        $product->unit_id = self::DOS_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'CPR Variant';
        $variant->product_variant_sku = 'CPR-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->retail_unit = $retailUnit;
        $variant->status = 1;
        $variant->save();

        return compact('product', 'variant');
    }

    private function makeWarehouse(bool $isMain): int
    {
        $type = WarehouseType::create([
            'warehouse_type_name' => ($isMain ? 'Utama' : 'Eceran').' CPR '.uniqid(),
            'is_main_warehouse' => $isMain ? 1 : 0,
            'status' => 1,
        ]);

        $w = new Warehouse();
        $w->warehouse_name = 'Gudang CPR '.uniqid();
        $w->warehouse_type_id = $type->id;
        $w->status = 1;
        $w->save();

        return (int) $w->id;
    }

    private function makeReturn(array $details, int $status = 1): CustomerProductReturn
    {
        $record = CustomerProductReturn::create([
            'return_number' => 'RPJ-TEST-'.strtoupper(uniqid()),
            'customer_id' => $this->customerId,
            'return_date' => date('Y-m-d'),
            'proof_path' => 'upload/test/dummy-proof.jpg',
            'status' => $status,
            'created_by' => (int) (session('user')->staff_id ?? 0),
        ]);

        foreach ($details as $detail) {
            CustomerProductReturnDetail::create(array_merge([
                'return_id' => $record->return_id,
                'status' => 1,
            ], $detail));
        }

        return $record;
    }

    private function stockOf(int $variantId, int $unitId, int $warehouseId): ?ProductStock
    {
        return ProductStock::withoutGlobalScope('active_warehouse')
            ->where('product_variant_id', $variantId)
            ->where('unit_id', $unitId)
            ->where('warehouse_id', $warehouseId)
            ->first();
    }

    // ----------------------------------------------------------------- accept

    public function test_accepting_adds_the_returned_qty_to_the_named_warehouse(): void
    {
        ['product' => $product, 'variant' => $variant] = $this->makeProduct();
        $warehouseId = $this->makeWarehouse(isMain: true);

        $stock = new ProductStock();
        $stock->product_id = $product->product_id;
        $stock->product_variant_id = $variant->product_variant_id;
        $stock->unit_id = self::DOS_UNIT_ID;
        $stock->warehouse_id = $warehouseId;
        $stock->ps_stock = 10;
        $stock->status = 1;
        $stock->save();

        $record = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'warehouse_id' => $warehouseId,
            'qty' => 4,
        ]]);

        $this->postJson("/customerProductReturns/{$record->return_id}/accept")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSame(
            14.0,
            (float) $this->stockOf($variant->product_variant_id, self::DOS_UNIT_ID, $warehouseId)->ps_stock
        );
        $this->assertSame(2, (int) $record->fresh()->status);
    }

    /**
     * A return can legitimately name a warehouse/unit pair that has never held
     * this variant before — the row is created rather than the return failing.
     */
    public function test_accepting_creates_the_stock_row_when_none_exists_yet(): void
    {
        ['variant' => $variant] = $this->makeProduct();
        $warehouseId = $this->makeWarehouse(isMain: true);

        $this->assertNull($this->stockOf($variant->product_variant_id, self::DOS_UNIT_ID, $warehouseId));

        $record = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'warehouse_id' => $warehouseId,
            'qty' => 3,
        ]]);

        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(200);

        $created = $this->stockOf($variant->product_variant_id, self::DOS_UNIT_ID, $warehouseId);
        $this->assertNotNull($created);
        $this->assertSame(3.0, (float) $created->ps_stock);
        $this->assertSame(1, (int) $created->status);
    }

    /**
     * log_stocks is polymorphic on log_type (see the pegasus-testing skill):
     * log_type=1 means log_item_id is a product_variant_id, log_category=1
     * means an inbound movement. A return that credits stock without leaving
     * this trail would be invisible to every stock report.
     */
    public function test_accepting_writes_an_inbound_product_stock_log(): void
    {
        ['variant' => $variant] = $this->makeProduct();
        $warehouseId = $this->makeWarehouse(isMain: true);

        $record = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'warehouse_id' => $warehouseId,
            'qty' => 6,
        ]]);

        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(200);

        $log = LogStock::query()
            ->where('log_kode', $record->return_number)
            ->where('log_type', 1)
            ->where('log_item_id', $variant->product_variant_id)
            ->first();

        $this->assertNotNull($log, 'Accepting a product return must leave a log_stocks trail.');
        $this->assertSame(1, (int) $log->log_category, 'A return into stock is an inbound movement.');
        $this->assertSame(6.0, (float) $log->log_jumlah);
        $this->assertSame($warehouseId, (int) $log->warehouse_id);
    }

    public function test_each_detail_line_credits_its_own_warehouse(): void
    {
        ['variant' => $variant] = $this->makeProduct();
        $warehouseA = $this->makeWarehouse(isMain: true);
        $warehouseB = $this->makeWarehouse(isMain: true);

        $record = $this->makeReturn([
            [
                'product_variant_id' => $variant->product_variant_id,
                'unit_id' => self::DOS_UNIT_ID,
                'warehouse_id' => $warehouseA,
                'qty' => 2,
            ],
            [
                'product_variant_id' => $variant->product_variant_id,
                'unit_id' => self::DOS_UNIT_ID,
                'warehouse_id' => $warehouseB,
                'qty' => 5,
            ],
        ]);

        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(200);

        $this->assertSame(2.0, (float) $this->stockOf($variant->product_variant_id, self::DOS_UNIT_ID, $warehouseA)->ps_stock);
        $this->assertSame(5.0, (float) $this->stockOf($variant->product_variant_id, self::DOS_UNIT_ID, $warehouseB)->ps_stock);
    }

    /** Soft-deleted (status 0) lines are excluded from the credit entirely. */
    public function test_an_inactive_detail_line_is_not_credited(): void
    {
        ['variant' => $variant] = $this->makeProduct();
        $warehouseId = $this->makeWarehouse(isMain: true);

        $record = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'warehouse_id' => $warehouseId,
            'qty' => 7,
        ]]);
        CustomerProductReturnDetail::where('return_id', $record->return_id)->update(['status' => 0]);

        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(200);

        $this->assertNull($this->stockOf($variant->product_variant_id, self::DOS_UNIT_ID, $warehouseId));
        $this->assertSame(2, (int) $record->fresh()->status);
    }

    // ---------------------------------------------------------------- decline

    public function test_declining_leaves_stock_untouched(): void
    {
        ['product' => $product, 'variant' => $variant] = $this->makeProduct();
        $warehouseId = $this->makeWarehouse(isMain: true);

        $stock = new ProductStock();
        $stock->product_id = $product->product_id;
        $stock->product_variant_id = $variant->product_variant_id;
        $stock->unit_id = self::DOS_UNIT_ID;
        $stock->warehouse_id = $warehouseId;
        $stock->ps_stock = 10;
        $stock->status = 1;
        $stock->save();

        $record = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'warehouse_id' => $warehouseId,
            'qty' => 4,
        ]]);

        $this->postJson("/customerProductReturns/{$record->return_id}/decline")
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertSame(10.0, (float) $this->stockOf($variant->product_variant_id, self::DOS_UNIT_ID, $warehouseId)->ps_stock);
        $this->assertSame(3, (int) $record->fresh()->status);
    }

    // ------------------------------------------------------- re-process guard

    public function test_a_second_accept_is_rejected_and_does_not_credit_twice(): void
    {
        ['variant' => $variant] = $this->makeProduct();
        $warehouseId = $this->makeWarehouse(isMain: true);

        $record = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'warehouse_id' => $warehouseId,
            'qty' => 5,
        ]]);

        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(200);
        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(422);

        $this->assertSame(
            5.0,
            (float) $this->stockOf($variant->product_variant_id, self::DOS_UNIT_ID, $warehouseId)->ps_stock,
            'The repeat-accept guard must stop the qty being credited a second time.'
        );
    }

    public function test_an_accepted_return_cannot_then_be_declined(): void
    {
        ['variant' => $variant] = $this->makeProduct();
        $warehouseId = $this->makeWarehouse(isMain: true);

        $record = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'warehouse_id' => $warehouseId,
            'qty' => 5,
        ]]);

        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(200);
        $this->postJson("/customerProductReturns/{$record->return_id}/decline")->assertStatus(422);

        $this->assertSame(2, (int) $record->fresh()->status);
    }

    public function test_only_a_pending_return_can_be_deleted(): void
    {
        ['variant' => $variant] = $this->makeProduct();
        $warehouseId = $this->makeWarehouse(isMain: true);

        $pending = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'warehouse_id' => $warehouseId,
            'qty' => 1,
        ]]);
        $this->postJson("/customerProductReturns/{$pending->return_id}/delete")->assertStatus(200);
        $this->assertSame(0, (int) $pending->fresh()->status);

        $accepted = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'warehouse_id' => $warehouseId,
            'qty' => 1,
        ]]);
        $this->postJson("/customerProductReturns/{$accepted->return_id}/accept")->assertStatus(200);
        $this->postJson("/customerProductReturns/{$accepted->return_id}/delete")->assertStatus(422);
        $this->assertSame(2, (int) $accepted->fresh()->status);
    }

    // ------------------------------------------------- retail-warehouse rules

    /**
     * A retail warehouse only ever holds the variant's retail_unit, so a
     * return of a DOS-sized line into one is refused rather than silently
     * creating a DOS row in a retail warehouse.
     */
    public function test_accepting_into_a_retail_warehouse_rejects_a_non_retail_unit(): void
    {
        ['variant' => $variant] = $this->makeProduct(retailUnit: self::PIECE_UNIT_ID);
        $retailWarehouseId = $this->makeWarehouse(isMain: false);

        $record = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'warehouse_id' => $retailWarehouseId,
            'qty' => 2,
        ]]);

        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(422);

        $this->assertNull($this->stockOf($variant->product_variant_id, self::DOS_UNIT_ID, $retailWarehouseId));
        $this->assertSame(1, (int) $record->fresh()->status, 'A rejected accept must leave the return pending.');
    }

    public function test_accepting_into_a_retail_warehouse_succeeds_with_the_retail_unit(): void
    {
        ['variant' => $variant] = $this->makeProduct(retailUnit: self::PIECE_UNIT_ID);
        $retailWarehouseId = $this->makeWarehouse(isMain: false);

        $record = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'warehouse_id' => $retailWarehouseId,
            'qty' => 12,
        ]]);

        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(200);

        $this->assertSame(
            12.0,
            (float) $this->stockOf($variant->product_variant_id, self::PIECE_UNIT_ID, $retailWarehouseId)->ps_stock
        );
    }

    /**
     * Without a retail_unit configured there is no valid unit for a retail
     * warehouse at all, so the whole line is refused rather than defaulting.
     */
    public function test_a_variant_without_a_retail_unit_cannot_return_into_a_retail_warehouse(): void
    {
        ['variant' => $variant] = $this->makeProduct(retailUnit: null);
        $retailWarehouseId = $this->makeWarehouse(isMain: false);

        $record = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'warehouse_id' => $retailWarehouseId,
            'qty' => 2,
        ]]);

        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(422);
        $this->assertSame(1, (int) $record->fresh()->status);
    }

    public function test_accepting_a_line_for_an_inactive_variant_is_refused(): void
    {
        ['variant' => $variant] = $this->makeProduct();
        $warehouseId = $this->makeWarehouse(isMain: true);

        $record = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'warehouse_id' => $warehouseId,
            'qty' => 2,
        ]]);

        ProductVariant::query()
            ->where('product_variant_id', $variant->product_variant_id)
            ->update(['status' => 0]);

        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(422);
        $this->assertNull($this->stockOf($variant->product_variant_id, self::DOS_UNIT_ID, $warehouseId));
    }

    // ------------------------------------------------------------ permissions

    public function test_accept_requires_the_pengiriman_others_ability(): void
    {
        ['variant' => $variant] = $this->makeProduct();
        $warehouseId = $this->makeWarehouse(isMain: true);
        $record = $this->makeReturn([[
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
            'warehouse_id' => $warehouseId,
            'qty' => 2,
        ]]);

        // 'view' alone passes the route middleware but not the controller's
        // own authorizeAbility('others') check.
        $this->actingAsStaffWithOnlyPermission('Pengiriman', ['view']);
        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(403);

        $this->actingAsStaffWithOnlyPermission('Pengiriman', ['view', 'others']);
        $this->postJson("/customerProductReturns/{$record->return_id}/accept")->assertStatus(200);
    }
}
