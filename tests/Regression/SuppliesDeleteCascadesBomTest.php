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
 * QC21: hapus bahan (supplies) dari daftar bahan harus soft-delete resep BOM
 * yang memakai bahan itu di bom_details (header + semua detail resep terkait).
 */
class SuppliesDeleteCascadesBomTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9;

    /**
     * @return array{suppliesDrop: Supplies, suppliesKeep: Supplies, bomDrop: Bom, bomKeep: Bom, detailDrop: BomDetail, detailKeep: BomDetail}
     */
    private function createFixture(): array
    {
        $category = new Category();
        $category->category_name = 'QC21 Cascade Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'QC21 Cascade Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'QC21 Variant';
        $variant->product_variant_sku = 'QC21-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->unit_id = self::UNIT_ID;
        $variant->status = 1;
        $variant->save();

        $suppliesDrop = new Supplies();
        $suppliesDrop->supplies_name = 'QC21 Drop Bahan';
        $suppliesDrop->supplies_unit = json_encode([self::UNIT_ID]);
        $suppliesDrop->supplies_default_unit = self::UNIT_ID;
        $suppliesDrop->status = 1;
        $suppliesDrop->save();

        $suppliesKeep = new Supplies();
        $suppliesKeep->supplies_name = 'QC21 Keep Bahan';
        $suppliesKeep->supplies_unit = json_encode([self::UNIT_ID]);
        $suppliesKeep->supplies_default_unit = self::UNIT_ID;
        $suppliesKeep->status = 1;
        $suppliesKeep->save();

        $bomDrop = new Bom();
        $bomDrop->product_id = $variant->product_variant_id;
        $bomDrop->bom_qty = 1;
        $bomDrop->unit_id = self::UNIT_ID;
        $bomDrop->status = 1;
        $bomDrop->save();

        $detailDrop = new BomDetail();
        $detailDrop->bom_id = $bomDrop->bom_id;
        $detailDrop->supplies_id = $suppliesDrop->supplies_id;
        $detailDrop->bom_detail_qty = 1;
        $detailDrop->unit_id = self::UNIT_ID;
        $detailDrop->status = 1;
        $detailDrop->save();

        $bomKeep = new Bom();
        $bomKeep->product_id = $variant->product_variant_id;
        $bomKeep->bom_qty = 1;
        $bomKeep->unit_id = self::UNIT_ID;
        $bomKeep->status = 1;
        $bomKeep->save();

        $detailKeep = new BomDetail();
        $detailKeep->bom_id = $bomKeep->bom_id;
        $detailKeep->supplies_id = $suppliesKeep->supplies_id;
        $detailKeep->bom_detail_qty = 1;
        $detailKeep->unit_id = self::UNIT_ID;
        $detailKeep->status = 1;
        $detailKeep->save();

        return compact('suppliesDrop', 'suppliesKeep', 'bomDrop', 'bomKeep', 'detailDrop', 'detailKeep');
    }

    public function test_delete_supplies_soft_deletes_related_boms_only(): void
    {
        $this->actingAsSuperAdminStaff();
        $fx = $this->createFixture();

        $this->post('/deleteSupplies', [
            'supplies_id' => $fx['suppliesDrop']->supplies_id,
        ])->assertOk();

        $this->assertSame(0, (int) Supplies::find($fx['suppliesDrop']->supplies_id)->status);
        $this->assertSame(0, (int) Bom::find($fx['bomDrop']->bom_id)->status);
        $this->assertSame(0, (int) BomDetail::find($fx['detailDrop']->bom_detail_id)->status);

        $this->assertSame(1, (int) Supplies::find($fx['suppliesKeep']->supplies_id)->status);
        $this->assertSame(1, (int) Bom::find($fx['bomKeep']->bom_id)->status);
        $this->assertSame(1, (int) BomDetail::find($fx['detailKeep']->bom_detail_id)->status);
    }
}
