<?php

namespace Tests\Unit;

use App\Services\AiAssistant\TextToolCallParser;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class TextToolCallParserTest extends BaseTestCase
{
    public function test_parses_xml_style_tool_call(): void
    {
        $raw = <<<XML
<tool_call>
<function=search_records>
<parameter=table>
sales_orders
</parameter>
<parameter=filters>
{"so_invoice_no":"INV1098"}
</parameter>
</function>
</tool_call>
XML;
        $parser = new TextToolCallParser();
        $this->assertTrue($parser->looksLikeToolMarkup($raw));
        $calls = $parser->parse($raw);
        $this->assertCount(1, $calls);
        $this->assertSame('search_records', $calls[0]['function']['name']);
        $args = json_decode($calls[0]['function']['arguments'], true);
        $this->assertSame('sales_orders', $args['table']);
        $this->assertSame('INV1098', $args['filters']['so_invoice_no']);
        $this->assertSame('', $parser->stripToolMarkup($raw));
    }

    public function test_parses_pipe_bracket_tool_call(): void
    {
        $raw = "<|Tool Call Start|>[Search Records(module='transfer stok', filters=[{'code': 'ST0087'}])]<|Tool Call End|>";
        $parser = new TextToolCallParser();
        $this->assertTrue($parser->looksLikeToolMarkup($raw));
        $calls = $parser->parse($raw);
        $this->assertCount(1, $calls);
        $this->assertSame('search_records', $calls[0]['function']['name']);
        $args = json_decode($calls[0]['function']['arguments'], true);
        $this->assertSame('transfer stok', $args['module']);
        $this->assertSame('ST0087', $args['filters']['code']);
        $this->assertSame('', $parser->stripToolMarkup($raw));
    }

    public function test_parses_find_document_title_case(): void
    {
        $raw = "Find Document(code='ST0087')";
        $parser = new TextToolCallParser();
        $calls = $parser->parse($raw);
        $this->assertCount(1, $calls);
        $this->assertSame('find_document', $calls[0]['function']['name']);
        $args = json_decode($calls[0]['function']['arguments'], true);
        $this->assertSame('ST0087', $args['code']);
    }
}
