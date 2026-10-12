<?php

namespace Tests\Unit;

use App\Services\AiAssistant\AnswerSanitizer;
use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\ToolRegistry;
use App\Services\AiAssistant\Tools\SearchRecordsTool;
use App\Services\AiAssistant\Tools\SummarizeRecordsTool;
use App\Services\AiAssistant\Vocabulary;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Whitelist / tool-surface guards — no DatabaseTransactions (works without pegasus_testing).
 */
class AiAssistantToolGuardTest extends BaseTestCase
{
    private function vocabulary(): Vocabulary
    {
        return new Vocabulary(new ReadOnlyDb());
    }

    public function test_tools_reject_non_whitelisted_table(): void
    {
        $tool = new SearchRecordsTool(new ReadOnlyDb(), $this->vocabulary());
        $this->expectException(\InvalidArgumentException::class);
        $tool->execute(['module' => 'password_reset_tokens', 'filters' => []]);
    }

    public function test_tools_reject_hidden_password_column(): void
    {
        $tool = new SearchRecordsTool(new ReadOnlyDb(), $this->vocabulary());
        $this->expectException(\InvalidArgumentException::class);
        $tool->execute([
            'module' => 'staffs',
            'fields' => ['staff_password'],
        ]);
    }

    public function test_summarize_rejects_unknown_metric(): void
    {
        $tool = new SummarizeRecordsTool(new ReadOnlyDb(), $this->vocabulary());
        $this->expectException(\InvalidArgumentException::class);
        $tool->execute(['module' => 'pengiriman', 'metric' => 'delete']);
    }

    public function test_registry_only_exposes_readonly_tools(): void
    {
        $names = (new ToolRegistry(new ReadOnlyDb(), $this->vocabulary()))->names();
        sort($names);
        $this->assertSame(
            [
                'dashboard_summary', 'data_anomalies', 'find_document', 'find_duplicates', 'find_item', 'item_activity',
                'item_stock', 'partner_insight', 'period_report', 'record_history', 'reorder_suggestion', 'search_records',
                'staff_activity', 'summarize_records', 'trace_document',
            ],
            $names,
        );
    }

    public function test_no_tool_class_exposes_write_api(): void
    {
        foreach ([
            \App\Services\AiAssistant\Tools\FindDocumentTool::class,
            \App\Services\AiAssistant\Tools\SearchRecordsTool::class,
            \App\Services\AiAssistant\Tools\SummarizeRecordsTool::class,
            \App\Services\AiAssistant\Tools\FindDuplicatesTool::class,
            \App\Services\AiAssistant\Tools\RecordHistoryTool::class,
            \App\Services\AiAssistant\Tools\FindItemTool::class,
            \App\Services\AiAssistant\Tools\ItemActivityTool::class,
            \App\Services\AiAssistant\Tools\DashboardSummaryTool::class,
            \App\Services\AiAssistant\Tools\ItemStockTool::class,
            \App\Services\AiAssistant\Tools\DataAnomaliesTool::class,
            \App\Services\AiAssistant\Tools\StaffActivityTool::class,
            \App\Services\AiAssistant\Tools\DocumentTraceTool::class,
            \App\Services\AiAssistant\Tools\PeriodReportTool::class,
            \App\Services\AiAssistant\Tools\PartnerInsightTool::class,
            \App\Services\AiAssistant\Tools\ReorderTool::class,
        ] as $class) {
            $methods = get_class_methods($class);
            foreach (['insert', 'update', 'delete', 'upsert', 'truncate'] as $write) {
                $this->assertNotContains($write, $methods, "{$class} must not expose {$write}()");
            }
        }
    }

    /** Persentase penyebab selalu total 100 dan hanya penyebab operasional. */
    public function test_cause_estimates_sum_to_100_and_never_blame_system(): void
    {
        $probe = new class
        {
            use \App\Services\AiAssistant\Tools\Concerns\CauseEstimates;

            public function run(string $type, array $boost = []): array
            {
                return $this->estimateCauses($type, $boost);
            }

            public function rowBoost(float $p, float $s): array
            {
                return $this->opnameRowBoost($p, $s);
            }
        };

        foreach (['stok_minus', 'pending_lama', 'transfer_belum_diterima', 'selisih_opname', 'hutang_jatuh_tempo'] as $type) {
            $list = $probe->run($type, ['Salah satuan' => 45])['perkiraan_penyebab'];
            $this->assertSame(100, array_sum(array_map(fn ($c) => (int) $c['Peluang'], $list)), $type);
            foreach ($list as $c) {
                $this->assertDoesNotMatchRegularExpression('/bug|error sistem|kesalahan sistem|glitch/i', $c['Penyebab']);
            }
        }

        $this->assertSame(['Salah satuan' => 45], $probe->rowBoost(1000, 1));
        $this->assertArrayHasKey('Salah hitung / salah ketik qty (human error)', $probe->rowBoost(10000, 43));
    }

    public function test_answers_never_expose_storage_wording(): void
    {
        $clean = (new AnswerSanitizer($this->vocabulary()))->clean(
            'Diambil dari tabel sales_orders kolom so_invoice_no lewat query SQL ke database.'
        );

        foreach (['sales_orders', 'so_invoice_no', 'tabel', 'kolom', 'SQL', 'database'] as $leak) {
            $this->assertStringNotContainsStringIgnoringCase($leak, $clean);
        }
        $this->assertStringContainsString('No. Invoice', $clean);
    }

    public function test_sanitizer_keeps_product_codes(): void
    {
        $clean = (new AnswerSanitizer($this->vocabulary()))->clean('SKU RCHK_5LH qty 7.');
        $this->assertStringContainsString('RCHK_5LH', $clean);
    }
}
