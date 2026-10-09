<?php

namespace Tests\Feature;

use App\Services\AiAssistant\ReadOnlyDb;
use App\Services\AiAssistant\Tools\FindDocumentTool;
use App\Services\AiAssistant\Tools\FindDuplicatesTool;
use App\Services\AiAssistant\Tools\RecordHistoryTool;
use App\Services\AiAssistant\Tools\SearchRecordsTool;
use App\Services\AiAssistant\Tools\SummarizeRecordsTool;
use App\Services\AiAssistant\Vocabulary;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Proves AI tool SELECT paths do not modify row counts.
 * Skips when pegasus_testing (or configured DB) is unavailable.
 */
class AiAssistantReadonlyToolsTest extends BaseTestCase
{
    private Vocabulary $vocab;

    protected function setUp(): void
    {
        try {
            parent::setUp();
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('DB unavailable: '.$e->getMessage());
        }

        config([
            'database.connections.ai_readonly' => config('database.connections.'.config('database.default')),
        ]);
        DB::purge('ai_readonly');
        $this->vocab = new Vocabulary(new ReadOnlyDb());
    }

    public function test_search_records_does_not_change_row_count(): void
    {
        if (!Schema::hasTable('categories')) {
            $this->markTestSkipped('categories table missing');
        }

        $before = (int) DB::table('categories')->count();
        $result = (new SearchRecordsTool(new ReadOnlyDb(), $this->vocab))->execute([
            'module' => 'kategori',
            'filters' => ['status' => 1],
        ]);

        $this->assertArrayHasKey('data', $result);
        $this->assertLessThanOrEqual(50, count($result['data']));
        $this->assertSame($before, (int) DB::table('categories')->count());
    }

    public function test_find_duplicates_does_not_change_row_count(): void
    {
        if (!Schema::hasTable('categories')) {
            $this->markTestSkipped('categories table missing');
        }

        $before = (int) DB::table('categories')->count();
        $result = (new FindDuplicatesTool(new ReadOnlyDb(), $this->vocab))->execute([
            'module' => 'kategori',
            'fields' => ['Kategori'],
        ]);

        $this->assertArrayHasKey('data', $result);
        $this->assertSame($before, (int) DB::table('categories')->count());
    }

    public function test_record_history_does_not_change_row_count(): void
    {
        if (!Schema::hasTable('categories')) {
            $this->markTestSkipped('categories table missing');
        }

        $row = DB::table('categories')->orderBy('category_id')->first();
        if (!$row) {
            $this->markTestSkipped('no categories rows');
        }

        $before = (int) DB::table('categories')->count();
        $result = (new RecordHistoryTool(new ReadOnlyDb(), $this->vocab))->execute([
            'module' => 'kategori',
            'ref' => $row->category_id,
        ]);

        $this->assertTrue($result['ditemukan'] ?? false);
        $this->assertSame($before, (int) DB::table('categories')->count());
    }

    public function test_summarize_and_find_document_do_not_change_row_count(): void
    {
        if (!Schema::hasTable('categories')) {
            $this->markTestSkipped('categories table missing');
        }

        $before = (int) DB::table('categories')->count();

        $sum = (new SummarizeRecordsTool(new ReadOnlyDb(), $this->vocab))->execute([
            'module' => 'kategori',
            'metric' => 'count',
        ]);
        $this->assertArrayHasKey('nilai', $sum);

        (new FindDocumentTool(new ReadOnlyDb(), $this->vocab))->execute([
            'code' => '___no_such_document___',
        ]);

        $this->assertSame($before, (int) DB::table('categories')->count());
    }

    public function test_readonly_connection_name(): void
    {
        $db = new ReadOnlyDb();
        $this->assertSame('ai_readonly', $db->connection()->getName());
    }
}
