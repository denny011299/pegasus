<?php

namespace Tests\Unit;

use App\Services\AiAssistant\AiChatService;
use App\Services\AiAssistant\AnswerSanitizer;
use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\FindDocumentTool;
use App\Services\AiAssistant\Vocabulary;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Accuracy / privacy guards for the AI assistant — no DB needed.
 */
class AiAssistantAccuracyTest extends BaseTestCase
{
    private function vocabulary(): Vocabulary
    {
        return new Vocabulary(new ReadOnlyDb());
    }

    public function test_everyday_words_are_not_read_as_document_codes(): void
    {
        $service = app(AiChatService::class);

        $this->assertNull($service->documentCodeHint('berapa stok produk kopi'));
        $this->assertNull($service->documentCodeHint('status pengiriman hari ini'));
        $this->assertNull($service->documentCodeHint('rekap produksi bulan ini'));
    }

    public function test_real_document_codes_are_detected(): void
    {
        $service = app(AiChatService::class);

        $this->assertSame('SO0012', $service->documentCodeHint('isi so0012 apa?'));
        $this->assertSame('INV1098', $service->documentCodeHint('cek INV1098'));
        $this->assertSame('SP0003', $service->documentCodeHint('opname SP0003 statusnya?'));
        $this->assertSame('PBJ0004', $service->documentCodeHint('retur PBJ0004'));
    }

    public function test_prefixes_route_to_the_right_module(): void
    {
        $prefixes = FindDocumentTool::PREFIXES;

        $this->assertSame(['stock_opnames'], $prefixes['SP']);
        $this->assertSame(['stock_opname_bahans'], $prefixes['SB']);
        $this->assertSame(['stock_transfers'], $prefixes['ST']);
        // Longer prefixes must be checked before their shorter look-alikes.
        $keys = array_keys($prefixes);
        $this->assertLessThan(array_search('SO', $keys, true), array_search('SDO', $keys, true));
        $this->assertLessThan(array_search('PO', $keys, true), array_search('PDO', $keys, true));
    }

    public function test_stock_movement_match_is_exact_per_code(): void
    {
        $pattern = '/'.str_replace('/', '\/', FindDocumentTool::exactCodePattern('SO1')).'/';

        $this->assertSame(1, preg_match($pattern, 'SO1'));
        $this->assertSame(1, preg_match($pattern, 'ST-SO1-3'));
        $this->assertSame(0, preg_match($pattern, 'SO10'));
        $this->assertSame(0, preg_match($pattern, 'SO11'));
    }

    public function test_unknown_status_is_not_guessed(): void
    {
        $label = $this->vocabulary()->statusLabel('purchase_orders_details', 7);
        $this->assertStringContainsString('belum terdaftar', (string) $label);
    }

    public function test_inactive_statuses_cover_deleted_rejected_cancelled(): void
    {
        $vocab = $this->vocabulary();

        $this->assertEqualsCanonicalizing([3, 0], $vocab->inactiveStatuses('sales_orders'));
        $this->assertEqualsCanonicalizing([0, 3, 5], $vocab->inactiveStatuses('stock_transfers'));
    }

    public function test_personal_and_financial_fields_are_hidden(): void
    {
        $db = new ReadOnlyDb();

        $this->assertNotContains('customer_email', $db->allowedColumns('customers'));
        $this->assertNotContains('customer_saldo', $db->allowedColumns('customers'));
        $this->assertNotContains('supplier_account_name', $db->allowedColumns('suppliers'));
        $this->assertNotContains('supplier_bank', $db->allowedColumns('suppliers'));
        $this->assertNotContains('staff_email', $db->allowedColumns('staffs'));
    }

    public function test_sanitizer_catches_single_word_and_mixed_case_storage_names(): void
    {
        $clean = (new AnswerSanitizer($this->vocabulary()))->clean('Lihat customers dan staffFinance_name.');

        $this->assertStringNotContainsString('customers', $clean);
        $this->assertStringNotContainsString('staffFinance_name', $clean);
    }

    public function test_guard_errors_do_not_name_storage(): void
    {
        try {
            (new ReadOnlyDb())->assertTable('password_reset_tokens');
            $this->fail('expected exception');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringNotContainsString('password_reset_tokens', $e->getMessage());
        }
    }

    public function test_markdown_marks_are_removed_for_plain_text_bubble(): void
    {
        $clean = (new AnswerSanitizer($this->vocabulary()))->clean("## Ringkasan
- 54 **Ditolak**");

        $this->assertSame("Ringkasan
- 54 Ditolak", $clean);
    }

    public function test_sanitizer_strips_bug_blame_wording(): void
    {
        $clean = (new AnswerSanitizer($this->vocabulary()))->clean(
            'Ini bug sistem dan kesalahan developer, bukan human error.'
        );

        $this->assertStringNotContainsString('bug', strtolower($clean));
        $this->assertStringNotContainsString('kesalahan developer', strtolower($clean));
        $this->assertStringContainsString('ketidaksesuaian data', strtolower($clean));
    }

    public function test_prompt_bans_blaming_system_or_developers(): void
    {
        $prompt = config('ai_assistant.system_prompt');

        $this->assertStringContainsString('REPUTATION RULE', $prompt);
        $this->assertStringContainsString('Absolute ban', $prompt);
        $this->assertStringContainsString('operasional', $prompt);
    }

    public function test_prompt_limits_scope_and_redirects_to_development_team(): void
    {
        $prompt = config('ai_assistant.system_prompt');

        $this->assertStringContainsString('di luar cakupan asisten', $prompt);
        $this->assertStringContainsString('clarifying question', $prompt);
        $this->assertStringContainsString('tim development', $prompt);
    }
}
