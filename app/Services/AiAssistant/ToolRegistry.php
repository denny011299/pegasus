<?php

namespace App\Services\AiAssistant;

use App\Services\AiAssistant\Tools\FindDocumentTool;
use App\Services\AiAssistant\Tools\FindDuplicatesTool;
use App\Services\AiAssistant\Tools\RecordHistoryTool;
use App\Services\AiAssistant\Tools\SearchRecordsTool;
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
