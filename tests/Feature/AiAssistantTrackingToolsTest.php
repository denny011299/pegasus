<?php

namespace Tests\Feature;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\DataAnomaliesTool;
use App\Services\AiAssistant\Tools\DocumentTraceTool;
use App\Services\AiAssistant\Tools\ItemStockTool;
use App\Services\AiAssistant\Tools\PeriodReportTool;
use App\Services\AiAssistant\Tools\ReorderTool;
use App\Services\AiAssistant\Tools\StaffActivityTool;
use App\Services\AiAssistant\Vocabulary;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

/**
 * Tool tracking v2: kartu stok, kejanggalan + perkiraan penyebab, aktivitas staf,
 * rantai dokumen, laporan periode, saran beli ulang. Data uji di transaksi
 * (rollback); ai_readonly memakai PDO yang sama supaya baris uji terlihat.
 */
class AiAssistantTrackingToolsTest extends BaseTestCase
{
    private const SKU = 'ZZAITRK1L';

    private ReadOnlyDb $db;

    private Vocabulary $vocab;

    private int $variantId;

    private int $unitId;

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
        $this->db = new ReadOnlyDb();
        $this->vocab = new Vocabulary($this->db);
        $this->seedTrackingData();
    }

    protected function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }

    public function test_item_stock_shows_current_stock_and_movements(): void
    {
        $result = (new ItemStockTool($this->db, $this->vocab))->execute(['item' => self::SKU]);

        $this->assertStringStartsWith('12 ', $result['stok_sekarang'][0]['Stok']);
        $this->assertSame('ZZLOG1', $result['mutasi_terakhir'][0]['Kode']);
    }

    public function test_transfer_not_received_has_operational_causes_summing_100(): void
    {
        $result = (new DataAnomaliesTool($this->db, $this->vocab))->execute(['cek' => 'transfer belum diterima', 'limit' => 30]);
        $section = $result['transfer_belum_diterima'];

        $this->assertContains('ST-ZZPR1-1', array_column($section['data'], 'Kode'));
        $this->assertSame('Gudang tujuan lupa klik Terima', $section['perkiraan_penyebab'][0]['Penyebab']);
        $this->assertSame(100, array_sum(array_map(fn ($c) => (int) $c['Peluang'], $section['perkiraan_penyebab'])));
        $this->assertArrayHasKey('alasan_bila_diminta', $section);
    }

    public function test_trace_document_climbs_from_transfer_to_production(): void
    {
        $result = (new DocumentTraceTool($this->db, $this->vocab))->execute(['code' => 'ST-ZZPR1-1']);

        $this->assertSame(['Produksi', 'Transfer Hasil Produksi'], array_column($result['alur'], 'Tahap'));
        $this->assertSame('ZZPR1', $result['alur'][0]['Kode']);
    }

    public function test_period_report_compares_with_previous_period(): void
    {
        $result = (new PeriodReportTool($this->db, $this->vocab))->execute(['periode' => 'bulan lalu', 'modul' => 'transfer']);

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-01 s\/d /', $result['dibanding']);
        $this->assertArrayHasKey('ringkasan', $result);
    }

    public function test_staff_activity_asks_when_name_unknown(): void
    {
        $result = (new StaffActivityTool($this->db, $this->vocab))->execute(['staff' => 'zz-tidak-ada-staf']);

        $this->assertStringContainsString('tidak ditemukan', $result['pesan']);
    }

    public function test_reorder_unknown_item_returns_message(): void
    {
        $result = (new ReorderTool($this->db, $this->vocab))->execute(['item' => 'zz-bahan-tidak-ada']);

        $this->assertStringContainsString('tidak ditemukan', $result['pesan']);
    }

    private function seedTrackingData(): void
    {
        $this->unitId = (int) DB::table('units')->insertGetId(['unit_name' => 'ZZPCS', 'unit_short_name' => 'ZZP', 'status' => 1]);
        $productId = (int) DB::table('products')->insertGetId([
            'product_name' => 'CAIRAN ZZTRACK',
            'category_id' => 1,
            'product_unit' => (string) $this->unitId,
            'unit_id' => $this->unitId,
            'status' => 1,
        ]);
        $this->variantId = (int) DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'product_variant_name' => '1 liter',
            'product_variant_sku' => self::SKU,
            'status' => 1,
        ]);
        DB::table('product_stocks')->insert([
            'product_variant_id' => $this->variantId,
            'product_id' => $productId,
            'unit_id' => $this->unitId,
            'warehouse_id' => 1,
            'ps_stock' => 12,
        ]);
        DB::table('log_stocks')->insert([
            'log_date' => now(),
            'log_kode' => 'ZZLOG1',
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $this->variantId,
            'log_jumlah' => 12,
            'unit_id' => $this->unitId,
            'warehouse_id' => 1,
            'log_notes' => 'Uji masuk',
        ]);

        $prId = (int) DB::table('productions')->insertGetId([
            'production_date' => now()->subDays(10)->toDateString(),
            'production_code' => 'ZZPR1',
            'production_created_by' => 1,
            'warehouse_id' => 1,
            'status' => 2,
        ]);
        // status 2 = Kirim, 10 hari tidak diterima → "lupa klik Terima" naik ke atas.
        DB::table('stock_transfers')->insert([
            'transfer_code' => 'ST-ZZPR1-1',
            'transfer_date' => now()->subDays(10)->toDateString(),
            'from_warehouse_id' => 1,
            'to_warehouse_id' => 2,
            'sender_id' => 1,
            'source_type' => 'production',
            'source_id' => $prId,
            'status' => 2,
        ]);
    }
}
