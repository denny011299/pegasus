<?php

namespace App\Services\AiAssistant;

use App\Services\AiAssistant\Tools\DashboardSummaryTool;
use App\Services\AiAssistant\Tools\DataAnomaliesTool;
use App\Services\AiAssistant\Tools\DocumentTraceTool;
use App\Services\AiAssistant\Tools\FindDocumentTool;
use App\Services\AiAssistant\Tools\FindDuplicatesTool;
use App\Services\AiAssistant\Tools\FindItemTool;
use App\Services\AiAssistant\Tools\ItemActivityTool;
use App\Services\AiAssistant\Tools\ItemStockTool;
use App\Services\AiAssistant\Tools\PartnerInsightTool;
use App\Services\AiAssistant\Tools\PeriodReportTool;
use App\Services\AiAssistant\Tools\RecordHistoryTool;
use App\Services\AiAssistant\Tools\ReorderTool;
use App\Services\AiAssistant\Tools\SearchRecordsTool;
use App\Services\AiAssistant\Tools\StaffActivityTool;
use App\Services\AiAssistant\Tools\SummarizeRecordsTool;
use App\Services\AiAssistant\Tools\ToolInterface;
use InvalidArgumentException;

class ToolRegistry
{
    /** @var array<string, ToolInterface> */
    private array $tools = [];

    /** @var list<array<string, mixed>>|null Cached OpenAI tool schema (stable for the request). */
    private ?array $openAiToolsCache = null;

    public function __construct(ReadOnlyDb $db, Vocabulary $vocab)
    {
        foreach ([
            new FindDocumentTool($db, $vocab),
            new SearchRecordsTool($db, $vocab),
            new SummarizeRecordsTool($db, $vocab),
            new FindDuplicatesTool($db, $vocab),
            new RecordHistoryTool($db, $vocab),
            new FindItemTool($db, $vocab),
            new ItemActivityTool($db, $vocab),
            new DashboardSummaryTool(),
            new ItemStockTool($db, $vocab),
            new DataAnomaliesTool($db, $vocab),
            new StaffActivityTool($db, $vocab),
            new DocumentTraceTool($db, $vocab),
            new PeriodReportTool($db, $vocab),
            new PartnerInsightTool($db, $vocab),
            new ReorderTool($db, $vocab),
        ] as $tool) {
            $this->tools[$tool->name()] = $tool;
        }
    }

    /** @return list<array<string, mixed>> */
    public function openAiTools(): array
    {
        if ($this->openAiToolsCache !== null) {
            return $this->openAiToolsCache;
        }

        $out = [];
        foreach ($this->tools as $tool) {
            $out[] = [
                'type' => 'function',
                'function' => [
                    'name' => $tool->name(),
                    'description' => $tool->description(),
                    'parameters' => $tool->parametersSchema(),
                ],
            ];
        }

        return $this->openAiToolsCache = $out;
    }

    public function get(string $name): ToolInterface
    {
        if (!isset($this->tools[$name])) {
            throw new InvalidArgumentException("Unknown tool: {$name}");
        }

        return $this->tools[$name];
    }

    /** @return list<string> */
    public function names(): array
    {
        return array_keys($this->tools);
    }
}
