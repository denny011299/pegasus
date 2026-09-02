<?php

namespace Tests\Regression;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplies;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * QC19: hapus produk / hapus varian dari edit produk harus soft-delete resep BOM
 * (boms + bom_details) yang terikat ke product_variant_id (kolom boms.product_id).
 */
class ProductVariantDeleteCascadesBomTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9;

    /**
     * @return array{product: Product, variantKeep: ProductVariant, variantDrop: ProductVariant, bomKeep: Bom, bomDrop: Bom, detailDrop: BomDetail}
     */
    private function createTwoVariantFixture(): array
    {
        $category = new Category();
        $category->category_name = 'QC19 Cascade Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'QC19 Cascade Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variantKeep = new ProductVariant();
        $variantKeep->product_id = $product->product_id;
        $variantKeep->product_variant_name = 'Keep';
        $variantKeep->product_variant_sku = 'QC19-KEEP-'.uniqid();
        $variantKeep->product_variant_price = 0;
        $variantKeep->unit_id = self::UNIT_ID;
        $variantKeep->status = 1;
        $variantKeep->save();

        $variantDrop = new ProductVariant();
        $variantDrop->product_id = $product->product_id;
        $variantDrop->product_variant_name = 'Drop';
        $variantDrop->product_variant_sku = 'QC19-DROP-'.uniqid();
        $variantDrop->product_variant_price = 0;
        $variantDrop->unit_id = self::UNIT_ID;
        $variantDrop->status = 1;
        $variantDrop->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'QC19 Cascade Supplies';
        $supplies->supplies_unit = json_encode([self::UNIT_ID]);
        $supplies->supplies_default_unit = self::UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        $bomKeep = new Bom();
        $bomKeep->product_id = $variantKeep->product_variant_id;
        $bomKeep->bom_qty = 1;
        $bomKeep->unit_id = self::UNIT_ID;
        $bomKeep->status = 1;
        $bomKeep->save();

        $bomDrop = new Bom();
        $bomDrop->product_id = $variantDrop->product_variant_id;
        $bomDrop->bom_qty = 1;
        $bomDrop->unit_id = self::UNIT_ID;
        $bomDrop->status = 1;
        $bomDrop->save();

        $detailDrop = new BomDetail();
        $detailDrop->bom_id = $bomDrop->bom_id;
        $detailDrop->supplies_id = $supplies->supplies_id;
        $detailDrop->bom_detail_qty = 1;
        $detailDrop->unit_id = self::UNIT_ID;
        $detailDrop->status = 1;
        $detailDrop->save();

        return compact('product', 'variantKeep', 'variantDrop', 'bomKeep', 'bomDrop', 'detailDrop');
    }

    public function test_delete_product_soft_deletes_all_variant_boms(): void
    {
        $this->actingAsSuperAdminStaff();
        $fx = $this->createTwoVariantFixture();

        $this->post('/deleteProduct', [
            'product_id' => $fx['product']->product_id,
        ])->assertOk();

        $this->assertSame(0, (int) Bom::find($fx['bomKeep']->bom_id)->status);
        $this->assertSame(0, (int) Bom::find($fx['bomDrop']->bom_id)->status);
        $this->assertSame(0, (int) BomDetail::find($fx['detailDrop']->bom_detail_id)->status);
        $this->assertSame(0, (int) ProductVariant::find($fx['variantKeep']->product_variant_id)->status);
        $this->assertSame(0, (int) ProductVariant::find($fx['variantDrop']->product_variant_id)->status);
    }

    public function test_update_product_removing_one_variant_only_cascades_that_bom(): void
    {
        $this->actingAsSuperAdminStaff();
        $fx = $this->createTwoVariantFixture();

        $this->post('/updateProduct', [
            'product_id' => $fx['product']->product_id,
            'product_name' => $fx['product']->product_name,
            'category_id' => $fx['product']->category_id,
            'product_unit' => $fx['product']->product_unit,
            'unit_id' => $fx['product']->unit_id,
            'product_variant' => json_encode([[
                'product_variant_id' => $fx['variantKeep']->product_variant_id,
                'variant_name' => $fx['variantKeep']->product_variant_name,
                'variant_sku' => $fx['variantKeep']->product_variant_sku,
                'variant_barcode' => '',
                'variant_alert' => 0,
                'unit_id' => self::UNIT_ID,
            ]]),
            'product_relasi' => json_encode([[]]),
        ])->assertOk();

        $this->assertSame(1, (int) Bom::find($fx['bomKeep']->bom_id)->status);
        $this->assertSame(0, (int) Bom::find($fx['bomDrop']->bom_id)->status);
        $this->assertSame(0, (int) BomDetail::find($fx['detailDrop']->bom_detail_id)->status);
        $this->assertSame(1, (int) ProductVariant::find($fx['variantKeep']->product_variant_id)->status);
        $this->assertSame(0, (int) ProductVariant::find($fx['variantDrop']->product_variant_id)->status);
    }
}
