<?php

namespace App\Services\AiAssistant\Tools;

interface ToolInterface
{
    public function name(): string;

    /** @return array<string, mixed> OpenAI-style function parameters schema */
    public function parametersSchema(): array;

    public function description(): string;

    /** @param array<string, mixed> $args */
    public function execute(array $args): array;
}
