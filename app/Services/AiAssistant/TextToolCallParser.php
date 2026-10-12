<?php

namespace App\Services\AiAssistant;

/**
 * Free / weaker models often emit tool calls as XML/text instead of OpenAI tool_calls.
 * Parse those into a normalized list so we can execute tools and never show raw markup to users.
 */
class TextToolCallParser
{
    public function looksLikeToolMarkup(string $content): bool
    {
        $c = strtolower($content);

        return str_contains($c, '<tool_call')
            || str_contains($c, '<function=')
            || str_contains($c, '<function ')
            || str_contains($c, 'tool call start')
            || str_contains($c, '<|tool')
            || str_contains($c, '"name": "search_records"')
            || str_contains($c, '"name":"find_document"')
            || (str_contains($c, 'search_records') && str_contains($c, 'parameter='))
            || (bool) preg_match('/\b(search records|find document|summarize records|find duplicates|record history)\s*\(/i', $content);
    }

    /**
     * @return list<array{id: string, function: array{name: string, arguments: string}}>
     */
    public function parse(string $content): array
    {
        $calls = [];

        // <|Tool Call Start|>[Search Records(module='x', filters=...)]<|Tool Call End|>
        if (preg_match_all('/(?:<\|)?Tool Call Start(?:\|>)?\s*(.*?)\s*(?:<\|)?Tool Call End(?:\|>)?/is', $content, $blocks)) {
            foreach ($blocks[1] as $block) {
                foreach ($this->parseBracketCalls($block) as $call) {
                    $calls[] = $call;
                }
            }
        }

        // Bare Title Case / snake_case calls: Search Records(...) or find_document(...)
        if ($calls === [] ) {
            foreach ($this->parseBracketCalls($content) as $call) {
                $calls[] = $call;
            }
        }

        // <tool_call> <function=name> <parameter=key>value</parameter> ... </function> </tool_call>
        if ($calls === [] && preg_match_all('/<tool_call>\s*(.*?)\s*<\/tool_call>/is', $content, $blocks)) {
            foreach ($blocks[1] as $block) {
                $call = $this->parseFunctionBlock($block);
                if ($call) {
                    $calls[] = $call;
                }
            }
        }

        // Bare <function=name>...</function> without outer tool_call
        if ($calls === [] && preg_match_all('/<function=([a-zA-Z0-9_]+)>(.*?)<\/function>/is', $content, $fns, PREG_SET_ORDER)) {
            foreach ($fns as $fn) {
                $call = $this->parseFunctionBlock($fn[0]);
                if ($call) {
                    $calls[] = $call;
                }
            }
        }

        // JSON tool call blob: {"name":"search_records","arguments":{...}}
        if ($calls === [] && preg_match_all('/\{[^{}]*"name"\s*:\s*"([a-zA-Z0-9_]+)"[^{}]*\}/s', $content, $jsons)) {
            foreach ($jsons[0] as $json) {
                $decoded = json_decode($json, true);
                if (!is_array($decoded) || empty($decoded['name'])) {
                    continue;
                }
                $args = $decoded['arguments'] ?? $decoded['parameters'] ?? [];
                if (is_string($args)) {
                    $args = json_decode($args, true) ?: [];
                }
                $calls[] = $this->normalizeCall((string) $decoded['name'], is_array($args) ? $args : []);
            }
        }

        return $calls;
    }

    public function stripToolMarkup(string $content): string
    {
        $content = preg_replace('/(?:<\|)?Tool Call Start(?:\|>)?.*?((?:<\|)?Tool Call End(?:\|>)?)/is', '', $content) ?? $content;
        $content = preg_replace('/\[?\s*(?:Search Records|Find Document|Summarize Records|Find Duplicates|Record History|search_records|find_document|summarize_records|find_duplicates|record_history)\s*\(.*?\)\s*\]?/is', '', $content) ?? $content;
        $content = preg_replace('/<tool_call>.*?<\/tool_call>/is', '', $content) ?? $content;
        $content = preg_replace('/<function=[^>]+>.*?<\/function>/is', '', $content) ?? $content;
        $content = preg_replace('/<\/?\|?[^|>]*\|?>/', '', $content) ?? $content;

        return trim($content);
    }

    /**
     * @return list<array{id: string, type: string, function: array{name: string, arguments: string}}>
     */
    private function parseBracketCalls(string $content): array
    {
        $names = 'Search Records|Find Document|Summarize Records|Find Duplicates|Record History|search_records|find_document|summarize_records|find_duplicates|record_history';
        if (!preg_match_all('/\[?\s*('.$names.')\s*\((.*?)\)\s*\]?/is', $content, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $calls = [];
        foreach ($matches as $match) {
            $args = $this->parseKwargs($match[2]);
            $calls[] = $this->normalizeCall($match[1], $args);
        }

        return $calls;
    }

    /** @return array{id: string, type: string, function: array{name: string, arguments: string}}|null */
    private function parseFunctionBlock(string $block): ?array
    {
        if (!preg_match('/<function=([a-zA-Z0-9_]+)>/i', $block, $m)) {
            return null;
        }
        $args = [];
        if (preg_match_all('/<parameter=([a-zA-Z0-9_]+)>\s*(.*?)\s*<\/parameter>/is', $block, $params, PREG_SET_ORDER)) {
            foreach ($params as $p) {
                $args[$p[1]] = $this->castScalar(trim(html_entity_decode($p[2])));
            }
        }

        return $this->normalizeCall($m[1], $args);
    }

    /**
     * Parse python/js-ish kwargs: module='x', filters=[{'a':1}], code="ST0087"
     *
     * @return array<string, mixed>
     */
    private function parseKwargs(string $raw): array
    {
        $args = [];
        $len = strlen($raw);
        $i = 0;
        while ($i < $len) {
            if (!preg_match('/\G\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*/', $raw, $m, 0, $i)) {
                break;
            }
            $key = $m[1];
            $i += strlen($m[0]);
            [$val, $i] = $this->readValue($raw, $i);
            $args[$key] = $this->decodeKwargValue($key, $val);
            // skip comma
            if (preg_match('/\G\s*,\s*/', $raw, $c, 0, $i)) {
                $i += strlen($c[0]);
            }
        }

        return $args;
    }

    /** @return array{0: string, 1: int} */
    private function readValue(string $raw, int $i): array
    {
        $len = strlen($raw);
        if ($i >= $len) {
            return ['', $i];
        }
        $ch = $raw[$i];
        if ($ch === '"' || $ch === "'") {
            $quote = $ch;
            $j = $i + 1;
            while ($j < $len && $raw[$j] !== $quote) {
                $j++;
            }

            return [substr($raw, $i, $j - $i + ($j < $len ? 1 : 0)), min($j + 1, $len)];
        }
        if ($ch === '[' || $ch === '{') {
            $open = $ch;
            $close = $ch === '[' ? ']' : '}';
            $depth = 0;
            $j = $i;
            while ($j < $len) {
                if ($raw[$j] === $open) {
                    $depth++;
                } elseif ($raw[$j] === $close) {
                    $depth--;
                    if ($depth === 0) {
                        return [substr($raw, $i, $j - $i + 1), $j + 1];
                    }
                }
                $j++;
            }

            return [substr($raw, $i), $len];
        }
        if (preg_match('/\G[^,]+/', $raw, $m, 0, $i)) {
            return [trim($m[0]), $i + strlen($m[0])];
        }

        return ['', $i];
    }

    private function decodeKwargValue(string $key, string $val): mixed
    {
        $val = trim($val);
        if (str_starts_with($val, '[') || str_starts_with($val, '{')) {
            $jsonish = preg_replace("/'/", '"', $val) ?? $val;
            $decoded = json_decode($jsonish, true);
            if (is_array($decoded)) {
                if ($key === 'filters' && array_is_list($decoded)) {
                    $flat = [];
                    foreach ($decoded as $item) {
                        if (is_array($item)) {
                            foreach ($item as $fk => $fv) {
                                $flat[$fk] = $fv;
                            }
                        }
                    }

                    return $flat !== [] ? $flat : $decoded;
                }

                return $decoded;
            }
        }

        return $this->castScalar(trim($val, " \t\n\r\0\x0B'\""));
    }

    private function castScalar(string $val): mixed
    {
        if ($val !== '' && ($val[0] === '{' || $val[0] === '[')) {
            $decoded = json_decode($val, true);

            return $decoded !== null ? $decoded : $val;
        }
        if (is_numeric($val)) {
            return str_contains($val, '.') ? (float) $val : (int) $val;
        }
        if (in_array(strtolower($val), ['true', 'false'], true)) {
            return strtolower($val) === 'true';
        }

        return $val;
    }

    /**
     * @param  array<string, mixed>  $args
     * @return array{id: string, type: string, function: array{name: string, arguments: string}}
     */
    private function normalizeCall(string $name, array $args): array
    {
        $snake = strtolower(trim(preg_replace('/\s+/', '_', $name) ?? $name));

        return [
            'id' => 'text_'.uniqid(),
            'type' => 'function',
            'function' => [
                'name' => $snake,
                'arguments' => json_encode($args, JSON_UNESCAPED_UNICODE),
            ],
        ];
    }
}
