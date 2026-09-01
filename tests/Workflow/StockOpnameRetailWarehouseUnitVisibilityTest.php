<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Tests\Support\ActingAsStaff;
use Tests\Support\ResolvesTestWarehouses;
use Tests\TestCase;

/**
 * Bug dilaporkan user (multi-gudang, 2026-08-31): saat gudang aktif adalah Gudang Eceran (bukan
 * Gudang Utama), halaman Buat Stock Opname (CreateStockOpname.js lewat GET /getProductVariant,
 * ProductVariant::getProductVariant()) menawarkan SEMUA satuan varian (mis. DOS dan Piece),
 * padahal StockController::getStock() (list Stok Produk) sudah lebih dulu membatasi gudang eceran
 * hanya ke retail_unit varian itu sendiri. Perbaikannya menyamakan aturan ini di
 * ProductVariant::getProductVariant().
 *
 * Gudang di sini di-resolve dari data snapshot yang sedang dimuat (bisa okeh8644 atau seed
 * minimal, lihat memory pegasus-testing-db-multiwarehouse-drift) -- bukan id yang di-hardcode --
 * supaya test ini tidak tergantung dataset mana yang aktif. Route /getProductVariant digerbangi
 * `check.access:Daftar Produk|view`, jadi gudang yang dipakai harus benar-benar mengizinkan modul
 * itu di `sidebar_menus`-nya.
 */
class StockOpnameRetailWarehouseUnitVisibilityTest extends TestCase
{
    use ActingAsStaff;
    use ResolvesTestWarehouses;

    private const DOS_UNIT_ID = 7;
    private const PIECE_UNIT_ID = 9;

    private function resolveActiveMainWarehouseId(): int
    {
        $id = Warehouse::query()
            ->where('status', 1)
            ->whereHas('type', fn ($q) => $q->where('is_main_warehouse', 1))
            ->get()
            ->first(fn ($w) => $w->allowsSidebarMenu('Daftar Produk'))
            ?->id;

        if (! $id) {
            $this->fail('No active main warehouse allowing "Daftar Produk" exists in the loaded test data.');
        }

        return (int) $id;
    }

    /** @return array{0: ProductVariant, 1: int, 2: int} varian, gudang utama id, gudang eceran id */
    private function makeFixture(): array
    {
        $mainWarehouseId = $this->resolveActiveMainWarehouseId();
        $retailWarehouseId = $this->resolveActiveRetailWarehouseId('Daftar Produk');

        $category = new Category();
        $category->category_name = 'Opname Retail Visibility Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Opname Retail Visibility Test Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::DOS_UNIT_ID, self::PIECE_UNIT_ID]);
        $product->unit_id = self::DOS_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Varian Uji Retail Visibility';
        $variant->product_variant_sku = 'RETVIS-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->retail_unit = self::PIECE_UNIT_ID;
        $variant->unit_id = self::DOS_UNIT_ID;
        $variant->status = 1;
        $variant->save();

        foreach ([$mainWarehouseId, $retailWarehouseId] as $warehouseId) {
            foreach ([self::DOS_UNIT_ID, self::PIECE_UNIT_ID] as $unitId) {
                $s = new ProductStock();
                $s->product_id = $product->product_id;
                $s->product_variant_id = $variant->product_variant_id;
                $s->unit_id = $unitId;
                $s->warehouse_id = $warehouseId;
                $s->ps_stock = 10;
                $s->status = 1;
                $s->save();
            }
        }

        return [$variant, $mainWarehouseId, $retailWarehouseId];
    }

    public function test_retail_warehouse_only_shows_the_variants_retail_unit(): void
    {
        $this->actingAsSuperAdminStaff();
        [$variant, , $retailWarehouseId] = $this->makeFixture();

        $this->withActiveWarehouse($retailWarehouseId);

        $rows = (new ProductVariant())->getProductVariant(['product_variant_id' => $variant->product_variant_id]);
        $row = collect($rows)->first();

        $this->assertNotNull($row, 'variant harus tetap muncul di daftar');
        $unitIds = collect($row->stock)->pluck('unit_id')->map(fn ($u) => (int) $u)->all();
        $this->assertSame([self::PIECE_UNIT_ID], $unitIds, 'gudang eceran hanya boleh menawarkan retail_unit varian ini');
    }

    public function test_main_warehouse_shows_every_unit(): void
    {
        $this->actingAsSuperAdminStaff();
        [$variant, $mainWarehouseId] = $this->makeFixture();

        $this->withActiveWarehouse($mainWarehouseId);

        $rows = (new ProductVariant())->getProductVariant(['product_variant_id' => $variant->product_variant_id]);
        $row = collect($rows)->first();

        $unitIds = collect($row->stock)->pluck('unit_id')->map(fn ($u) => (int) $u)->sort()->values()->all();
        $this->assertSame([self::DOS_UNIT_ID, self::PIECE_UNIT_ID], $unitIds, 'gudang utama harus menawarkan semua satuan');
    }

    /** Varian tanpa retail_unit di gudang eceran tidak boleh menawarkan satuan apa pun (bukan malah semuanya). */
    public function test_retail_warehouse_hides_everything_when_variant_has_no_retail_unit(): void
    {
        $this->actingAsSuperAdminStaff();
        [$variant, , $retailWarehouseId] = $this->makeFixture();
        $variant->retail_unit = null;
        $variant->save();

        $this->withActiveWarehouse($retailWarehouseId);

        $rows = (new ProductVariant())->getProductVariant(['product_variant_id' => $variant->product_variant_id]);
        $row = collect($rows)->first();

        $this->assertCount(0, $row->stock);
    }

    /** getProductVariantBulk dipakai halaman detail/PDF — harus sama aturannya dengan getProductVariant. */
    public function test_bulk_respects_retail_unit_for_document_warehouse(): void
    {
        $this->actingAsSuperAdminStaff();
        [$variant, , $retailWarehouseId] = $this->makeFixture();

        $bulk = (new ProductVariant())->getProductVariantBulk(
            [$variant->product_variant_id],
            $retailWarehouseId
        );
        $row = $bulk->get($variant->product_variant_id);

        $this->assertNotNull($row);
        $unitIds = collect($row->stock)->pluck('unit_id')->map(fn ($u) => (int) $u)->all();
        $this->assertSame([self::PIECE_UNIT_ID], $unitIds);
    }

    /** Menembus endpoint sungguhan yang dipakai CreateStockOpname.js. */
    public function test_wired_into_get_product_variant_endpoint(): void
    {
        $this->actingAsSuperAdminStaff();
        [$variant, , $retailWarehouseId] = $this->makeFixture();

        $this->withActiveWarehouse($retailWarehouseId);

        $response = $this->get('/getProductVariant?search_product='.urlencode($variant->product_variant_sku));
        $response->assertStatus(200);

        $row = collect($response->json())->first();
        $this->assertNotNull($row, 'variant harus tetap muncul di daftar');
        $unitIds = collect($row['stock'])->pluck('unit_id')->map(fn ($u) => (int) $u)->all();
        $this->assertSame([self::PIECE_UNIT_ID], $unitIds);
    }
}
