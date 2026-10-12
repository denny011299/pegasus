<?php

namespace Tests\Feature;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\DashboardSummaryTool;
use App\Services\AiAssistant\Tools\FindItemTool;
use App\Services\AiAssistant\Tools\ItemActivityTool;
use App\Services\AiAssistant\Tools\SearchRecordsTool;
use App\Services\AiAssistant\Vocabulary;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

/**
 * find_item / item_activity / dashboard_summary: saran barang saat ketikan
 * tidak persis, rekap per barang per modul, dan angka dashboard.
 * Data uji dibuat dalam transaksi lalu di-rollback; ai_readonly dipaksa
 * memakai PDO yang sama supaya baris uji terlihat.
 */
class AiAssistantItemToolsTest extends BaseTestCase
{
    private const SKU = 'ZZAITEST5L';

    private Vocabulary $vocab;

    private int $variantId;

    protected function setUp(): void
    {
        try {
            parent::setUp();
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('DB unavailable: '.$e->getMessage());
        }

        config(['database.connections.ai_readonly' => config('database.connections.'.config('database.default'))]);
        DB::purge('ai_readonly');
        DB::beginTransaction();
        DB::connection('ai_readonly')->setPdo(DB::connection()->getPdo());
        $this->vocab = new Vocabulary(new ReadOnlyDb());
        $this->seedItem();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_exact_sku_is_found(): void
    {
        $result = $this->findItem(['keyword' => strtolower(self::SKU)]);

        $this->assertTrue($result['ditemukan']);
        $this->assertTrue($result['cocok_persis']);
        $this->assertSame(self::SKU, $result['barang'][0]['SKU']);
    }

    public function test_misspelled_name_returns_suggestions_instead_of_empty(): void
    {
        $result = $this->findItem(['keyword' => 'cairan zzuji pegasos']);

        $this->assertFalse($result['ditemukan']);
        $this->assertContains(self::SKU, array_column($result['saran'], 'SKU'));
    }

    public function test_misspelled_sku_returns_suggestions(): void
    {
        $result = $this->findItem(['keyword' => 'ZZAIETST5L']);

        $this->assertFalse($result['ditemukan']);
        $this->assertContains(self::SKU, array_column($result['saran'], 'SKU'));
    }

    public function test_item_activity_counts_today_stock_transfers(): void
    {
        $result = (new ItemActivityTool(new ReadOnlyDb(), $this->vocab))->execute([
            'item' => self::SKU,
            'modul' => 'transfer stok',
            'date_from' => now()->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        $this->assertTrue($result['ditemukan']);
        $this->assertCount(1, $result['rekap']);
        $this->assertSame('Transfer Stok', $result['rekap'][0]['modul']);
        $this->assertSame(2, $result['rekap'][0]['jumlah_dokumen']);
        $this->assertStringStartsWith('7 ', $result['rekap'][0]['total_qty']);
        $this->assertArrayHasKey('Qty Barang', $result['rekap'][0]['dokumen'][0]);
    }

    public function test_item_activity_excludes_deleted_transfer_and_other_days(): void
    {
        $yesterday = now()->subDay()->toDateString();
        $result = (new ItemActivityTool(new ReadOnlyDb(), $this->vocab))->execute([
            'item' => self::SKU,
            'modul' => 'transfer stok',
            'date_from' => $yesterday,
            'date_to' => $yesterday,
        ]);

        $this->assertSame(0, $result['rekap'][0]['jumlah_dokumen']);
    }

    public function test_item_activity_suggests_when_item_unknown(): void
    {
        $result = (new ItemActivityTool(new ReadOnlyDb(), $this->vocab))->execute([
            'item' => 'ZZAIETST5L',
            'modul' => 'pengiriman',
        ]);

        $this->assertFalse($result['ditemukan']);
        $this->assertNotEmpty($result['saran']);
    }

    public function test_search_records_on_product_gives_suggestions_when_empty(): void
    {
        $result = (new SearchRecordsTool(new ReadOnlyDb(), $this->vocab))->execute([
            'module' => 'varian produk',
            'search' => 'ZZAIETST5L',
        ]);

        $this->assertSame(0, $result['total_cocok']);
        $this->assertContains(self::SKU, array_column($result['saran'] ?? [], 'SKU'));
    }

    public function test_dashboard_summary_returns_kpi_without_links(): void
    {
        $result = (new DashboardSummaryTool())->execute(['bagian' => 'ringkasan', 'periode' => 'bulan']);

        $this->assertSame('ringkasan', $result['bagian']);
        $this->assertArrayHasKey('kpi', $result['data']);
        $this->assertStringNotContainsString('http', json_encode($result));
    }

    private function findItem(array $args): array
    {
        return (new FindItemTool(new ReadOnlyDb(), $this->vocab))->execute($args);
    }

    private function seedItem(): void
    {
        $unitId = (int) DB::table('units')->insertGetId(['unit_name' => 'ZZDOS', 'unit_short_name' => 'ZZD', 'status' => 1]);
        $productId = (int) DB::table('products')->insertGetId([
            'product_name' => 'CAIRAN ZZUJI PEGASUS',
            'category_id' => 1,
            'product_unit' => (string) $unitId,
            'unit_id' => $unitId,
            'status' => 1,
        ]);
        $this->variantId = (int) DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'product_variant_name' => '4 x 5 liter',
            'product_variant_sku' => self::SKU,
            'status' => 1,
        ]);

        // 2 transfer hari ini (3 + 4) + 1 transfer dihapus (status 0) yang tidak boleh terhitung.
        foreach ([[3, 1], [4, 1], [9, 0]] as $i => [$qty, $status]) {
            $stId = (int) DB::table('stock_transfers')->insertGetId([
                'transfer_code' => 'ZZST'.$i,
                'transfer_date' => now()->toDateString(),
                'from_warehouse_id' => 1,
                'to_warehouse_id' => 2,
                'sender_id' => 1,
                'status' => $status,
            ]);
            DB::table('stock_transfer_details')->insert([
                'st_id' => $stId,
                'product_id' => $productId,
                'product_variant_id' => $this->variantId,
                'unit_id' => $unitId,
                'qty' => $qty,
                'status' => 1,
            ]);
        }
    }
}
