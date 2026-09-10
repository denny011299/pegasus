<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Trading bahan mentah: ACC PO menambah stok product_variant (roll-up),
 * bukan supplies_stocks. Data lama = supplies_kind supply.
 */
class SuppliesTradingPurchaseReceiptTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE = 9;
    private const DOS = 7;

    public function test_trading_po_acc_credits_linked_product_variant_not_supplies_stock(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(1);

        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $category = new Category();
        $category->category_name = 'Trading Cat '.uniqid();
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Trading Link Prod '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE, self::DOS]);
        $product->unit_id = self::PIECE;
        $product->status = 1;
        if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'product_kind')) {
            $product->product_kind = Product::KIND_PRODUCT;
        }
        $product->save();

        $pv = new ProductVariant();
        $pv->product_id = $product->product_id;
        $pv->product_variant_name = 'Trading PV';
        $pv->product_variant_sku = 'TRD-PV-'.uniqid();
        $pv->product_variant_price = 0;
        $pv->status = 1;
        $pv->save();

        $pr = new ProductRelation();
        $pr->product_variant_id = $pv->product_variant_id;
        $pr->pr_unit_id_1 = self::DOS;
        $pr->pr_unit_value_1 = 1;
        $pr->pr_unit_id_2 = self::PIECE;
        $pr->pr_unit_value_2 = 12;
        $pr->pr_default = 0;
        $pr->status = 1;
        $pr->save();

        $piecePs = new ProductStock();
        $piecePs->warehouse_id = 1;
        $piecePs->product_id = $product->product_id;
        $piecePs->product_variant_id = $pv->product_variant_id;
        $piecePs->unit_id = self::PIECE;
        $piecePs->ps_stock = 0;
        $piecePs->status = 1;
        $piecePs->save();

        $dosPs = new ProductStock();
        $dosPs->warehouse_id = 1;
        $dosPs->product_id = $product->product_id;
        $dosPs->product_variant_id = $pv->product_variant_id;
        $dosPs->unit_id = self::DOS;
        $dosPs->ps_stock = 0;
        $dosPs->status = 1;
        $dosPs->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Trading Bahan '.uniqid();
        $supplies->supplies_unit = json_encode([self::PIECE, self::DOS]);
        $supplies->supplies_default_unit = self::PIECE;
        $supplies->status = 1;
        if (Supplies::hasKindColumn()) {
            $supplies->supplies_kind = Supplies::KIND_TRADING;
            $supplies->trading_product_variant_id = $pv->product_variant_id;
        }
        $supplies->save();

        $sv = new SuppliesVariant();
        $sv->supplies_id = $supplies->supplies_id;
        $sv->supplier_id = $supplier->supplier_id;
        $sv->supplies_variant_name = 'Trading SV';
        $sv->supplies_variant_sku = 'TRD-SV-'.uniqid();
        $sv->supplies_variant_price = 1000;
        $sv->supplies_variant_barcode = 'TRD-BC-'.uniqid();
        $sv->supplies_variant_stock = 0;
        $sv->status = 1;
        $sv->save();

        $ssPiece = new SuppliesStock();
        $ssPiece->supplies_id = $supplies->supplies_id;
        $ssPiece->unit_id = self::PIECE;
        $ssPiece->warehouse_id = 1;
        $ssPiece->ss_stock = 0;
        $ssPiece->status = 1;
        $ssPiece->save();

        $this->assertTrue(Supplies::hasKindColumn(), 'migration supplies_kind harus sudah jalan');

        $poId = (int) $this->post('/insertPurchaseOrder', [
            'po_supplier' => $supplier->supplier_id,
            'po_date' => now()->toDateString(),
            'po_total' => 24000,
            'jenis_discount' => 1,
            'po_desc' => 'Trading PO test',
            'po_img' => json_encode([]),
            'po_detail' => json_encode([[
                'supplies_variant_id' => $sv->supplies_variant_id,
                'supplies_name' => $supplies->supplies_name,
                'supplies_variant_name' => $sv->supplies_variant_name,
                'supplies_variant_sku' => $sv->supplies_variant_sku,
                'qty' => 24,
                'supplies_variant_price' => 1000,
                'unit_id_select' => self::PIECE,
            ]]),
        ])->json();

        $this->post('/accPO', [
            'data' => [
                'po_id' => $poId,
                'po_supplier' => $supplier->supplier_id,
                'items' => [[
                    'supplies_variant_id' => $sv->supplies_variant_id,
                    'unit_id' => self::PIECE,
                    'pod_sku' => $sv->supplies_variant_sku,
                    'pod_qty' => 24,
                ]],
            ],
        ])->assertStatus(200);

        $ssPiece->refresh();
        $piecePs->refresh();
        $dosPs->refresh();

        $this->assertSame(0, (int) $ssPiece->ss_stock, 'Trading tidak menambah stok bahan mentah');
        $this->assertSame(2, (int) $dosPs->ps_stock, '24 Piece harus roll-up jadi 2 DOS di produk');
        $this->assertSame(0, (int) $piecePs->ps_stock);
    }
}
