<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Regression coverage for the MRP300P/SOHP duplicate-SKU data issue (see
 * cdocs/testing/KNOWN_ISSUES.md, fixed 2026-08-03). The two colliding SKUs were
 * renamed (MRP300P30 for product 66, SOHPPAIL for product 42) and
 * ProductController::validateVariantUniqueness() now rejects any NEW collision at
 * insert/update time, on purpose, rather than leaving it to a data-quality allow-list
 * (tests/Health/DuplicateSkuTest.php) that only ever notices after the fact.
 */
class ProductVariantUniquenessGuardTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE_UNIT_ID = 9;

    private function makeProductWithVariant(string $variantName, string $sku): array
    {
        $category = new Category();
        $category->category_name = 'Regression Uniqueness Test '.uniqid();
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Regression Uniqueness Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = $variantName;
        $variant->product_variant_sku = $sku;
        $variant->unit_id = self::PIECE_UNIT_ID;
        $variant->status = 1;
        $variant->save();

        return [$product, $variant];
    }

    public function test_the_two_historically_colliding_skus_are_now_disambiguated(): void
    {
        // Product 36 keeps the original MRP300P (best match for its own name); only product 66's
        // variant was renamed to MRP300P30. Same shape for SOHP (product 41 keeps it) / SOHPPAIL
        // (product 42's rename). The invariant is "each SKU now maps to exactly one product," not
        // that the original values are gone.
        $mrp30 = ProductVariant::where('product_variant_sku', 'MRP300P30')->where('status', 1)->first();
        $sohpPail = ProductVariant::where('product_variant_sku', 'SOHPPAIL')->where('status', 1)->first();

        $this->assertNotNull($mrp30, 'MRP300P30 (the renamed product 66 variant) should exist');
        $this->assertSame(66, $mrp30->product_id);
        $this->assertNotNull($sohpPail, 'SOHPPAIL (the renamed product 42 variant) should exist');
        $this->assertSame(42, $sohpPail->product_id);

        $this->assertSame(
            1,
            ProductVariant::where('product_variant_sku', 'MRP300P')->where('status', 1)->distinct('product_id')->count('product_id'),
            'MRP300P must now map to exactly one product'
        );
        $this->assertSame(
            1,
            ProductVariant::where('product_variant_sku', 'SOHP')->where('status', 1)->distinct('product_id')->count('product_id'),
            'SOHP must now map to exactly one product'
        );
    }

    public function test_insert_product_rejects_a_sku_already_used_by_another_active_variant(): void
    {
        $this->actingAsSuperAdminStaff();
        [, $existing] = $this->makeProductWithVariant('Existing Variant', 'GUARDTEST-SKU-1');

        $category = Category::first();
        $response = $this->post('/insertProduct', [
            'product_name' => 'New Product Colliding SKU '.uniqid(),
            'category_id' => $category->category_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'product_unit' => json_encode([self::PIECE_UNIT_ID]),
            'modeRelasi' => 0,
            'product_variant' => json_encode([[
                'variant_name' => 'Colliding Variant',
                'variant_sku' => 'GUARDTEST-SKU-1',
                'variant_barcode' => '',
                'variant_alert' => 0,
                'unit_id' => self::PIECE_UNIT_ID,
            ]]),
            'product_relasi' => json_encode([[]]),
        ]);

        $response->assertStatus(200);
        $this->assertNotEquals('1', trim($response->getContent(), '"'), 'insert should be rejected, not succeed');
        $this->assertStringContainsStringIgnoringCase('sudah dipakai', $response->json('message') ?? '');

        $this->assertSame(
            1,
            ProductVariant::where('product_variant_sku', 'GUARDTEST-SKU-1')->where('status', 1)->count(),
            'the colliding SKU must still belong to exactly the original variant, no new row created'
        );
    }

    public function test_insert_product_rejects_two_variants_in_the_same_batch_sharing_a_name(): void
    {
        $this->actingAsSuperAdminStaff();
        $category = Category::first();

        $response = $this->post('/insertProduct', [
            'product_name' => 'New Product Dup Name In Batch '.uniqid(),
            'category_id' => $category->category_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'product_unit' => json_encode([self::PIECE_UNIT_ID]),
            'modeRelasi' => 0,
            'product_variant' => json_encode([
                ['variant_name' => 'Sama', 'variant_sku' => 'GUARDTEST-SKU-2A', 'variant_barcode' => '', 'variant_alert' => 0, 'unit_id' => self::PIECE_UNIT_ID],
                ['variant_name' => 'Sama', 'variant_sku' => 'GUARDTEST-SKU-2B', 'variant_barcode' => '', 'variant_alert' => 0, 'unit_id' => self::PIECE_UNIT_ID],
            ]),
            'product_relasi' => json_encode([[], []]),
        ]);

        $response->assertStatus(200);
        $this->assertNotEquals('1', trim($response->getContent(), '"'));
        $this->assertStringContainsStringIgnoringCase('nama varian', $response->json('message') ?? '');

        $this->assertSame(
            0,
            Product::where('product_name', 'like', 'New Product Dup Name In Batch%')->count(),
            'no Product row should be committed when the precheck rejects the batch'
        );
    }

    public function test_update_product_rejects_renaming_a_variant_to_match_its_sibling(): void
    {
        $this->actingAsSuperAdminStaff();

        $category = new Category();
        $category->category_name = 'Regression Uniqueness Sibling Test '.uniqid();
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Regression Sibling Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variantA = new ProductVariant();
        $variantA->product_id = $product->product_id;
        $variantA->product_variant_name = 'Merah';
        $variantA->product_variant_sku = 'GUARDTEST-SKU-3A';
        $variantA->unit_id = self::PIECE_UNIT_ID;
        $variantA->status = 1;
        $variantA->save();

        $variantB = new ProductVariant();
        $variantB->product_id = $product->product_id;
        $variantB->product_variant_name = 'Biru';
        $variantB->product_variant_sku = 'GUARDTEST-SKU-3B';
        $variantB->unit_id = self::PIECE_UNIT_ID;
        $variantB->status = 1;
        $variantB->save();

        // Try to rename Biru -> Merah while resubmitting Merah unchanged, in one update.
        $response = $this->post('/updateProduct', [
            'product_id' => $product->product_id,
            'product_name' => $product->product_name,
            'category_id' => $category->category_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'product_unit' => json_encode([self::PIECE_UNIT_ID]),
            'product_variant' => json_encode([
                ['product_variant_id' => $variantA->product_variant_id, 'variant_name' => 'Merah', 'variant_sku' => 'GUARDTEST-SKU-3A', 'variant_barcode' => '', 'variant_alert' => 0, 'unit_id' => self::PIECE_UNIT_ID],
                ['product_variant_id' => $variantB->product_variant_id, 'variant_name' => 'Merah', 'variant_sku' => 'GUARDTEST-SKU-3B', 'variant_barcode' => '', 'variant_alert' => 0, 'unit_id' => self::PIECE_UNIT_ID],
            ]),
            'product_relasi' => json_encode([[], []]),
        ]);

        $response->assertStatus(200);
        $this->assertNotEquals('1', trim($response->getContent(), '"'));
        $this->assertStringContainsStringIgnoringCase('nama varian', $response->json('message') ?? '');

        $variantB->refresh();
        $this->assertSame('Biru', $variantB->product_variant_name, 'the rejected rename must not have been persisted');
    }

    public function test_update_product_allows_resubmitting_a_variant_with_its_own_unchanged_sku_and_name(): void
    {
        $this->actingAsSuperAdminStaff();
        [$product, $variant] = $this->makeProductWithVariant('Tidak Berubah', 'GUARDTEST-SKU-4');

        $response = $this->post('/updateProduct', [
            'product_id' => $product->product_id,
            'product_name' => $product->product_name,
            'category_id' => $product->category_id,
            'unit_id' => self::PIECE_UNIT_ID,
            'product_unit' => json_encode([self::PIECE_UNIT_ID]),
            'product_variant' => json_encode([
                ['product_variant_id' => $variant->product_variant_id, 'variant_name' => 'Tidak Berubah', 'variant_sku' => 'GUARDTEST-SKU-4', 'variant_barcode' => '', 'variant_alert' => 0, 'unit_id' => self::PIECE_UNIT_ID],
            ]),
            'product_relasi' => json_encode([[]]),
        ]);

        $response->assertStatus(200);
        $this->assertEquals('1', trim($response->getContent(), '"'), 'resubmitting a variant\'s own unchanged SKU/name must not be treated as a collision with itself');
    }
}
