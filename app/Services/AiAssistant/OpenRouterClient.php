<?php

namespace App\Services\AiAssistant;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SplQueue;

class OpenRouterClient
{
    /**
     * Sends the conversation to the primary model, then to each fallback
     * model when the primary is unavailable (rate limited, offline, or it
     * rejects the tool payload). Authentication/quota errors are not
     * retried, since every model would fail the same way.
     *
     * A conversation that carries an image goes to the vision model list
     * (config ai_assistant.vision) instead of the main one.
     *
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @return array<string, mixed>
     */
    public function chat(array $messages, array $tools = [], bool $vision = false): array
    {
        $key = config('ai_assistant.openrouter.api_key');
        if (! $key) {
            throw new RuntimeException('OPENROUTER_API_KEY is not configured.');
        }

        $models = $this->models($vision);
        $lastError = null;
        foreach ($models as $index => $model) {
            $payload = $this->payload($model, $messages, $tools, false);

            // connect_timeout pendek supaya gagal cepat kalau jaringan macet;
            // timeout total tetap untuk jawaban model yang lama (default 5 menit).
            $response = Http::timeout((int) config('ai_assistant.openrouter.timeout', 300))
                ->connectTimeout((int) config('ai_assistant.openrouter.connect_timeout', 15))
                ->withHeaders($this->headers($key))
                ->post(rtrim((string) config('ai_assistant.openrouter.base_url'), '/').'/chat/completions', $payload);

            if ($response->successful()) {
                return $response->json();
            }

            $status = $response->status();
            $body = $response->body();
            $lastError = 'OpenRouter error: '.$status.' '.$body;

            // Key/quota problems will fail the same way on every model.
            if (in_array($status, [401, 403], true) || $index === count($models) - 1) {
                break;
            }

            // 404 = that free slug is gone; 429/502/503 = temporarily unavailable.
            Log::warning('AI model unavailable, trying fallback', [
                'model' => $model,
                'status' => $status,
            ]);
        }

        throw new RuntimeException($lastError ?? 'OpenRouter error: no model responded.');
    }

    /**
     * Streaming chat/completions. Yields:
     *   ['type' => 'delta', 'text' => string]
     *   ['type' => 'tool_call_delta']  — once when the first tool-call chunk arrives
     *   ['type' => 'done', 'response' => array]  — assembled OpenAI-shaped response
     *
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @return \Generator<int, array<string, mixed>>
     */
    public function chatStream(array $messages, array $tools = [], bool $vision = false): \Generator
    {
        $key = config('ai_assistant.openrouter.api_key');
        if (! $key) {
            throw new RuntimeException('OPENROUTER_API_KEY is not configured.');
        }

        $models = $this->models($vision);
        $lastError = null;

        foreach ($models as $index => $model) {
            try {
                yield from $this->streamOnce($key, $model, $messages, $tools);

                return;
            } catch (RuntimeException $e) {
                $lastError = $e->getMessage();
                $status = $this->statusFromError($lastError);

                if (in_array($status, [401, 403], true) || $index === count($models) - 1) {
                    break;
                }

                Log::warning('AI stream model unavailable, trying fallback', [
                    'model' => $model,
                    'status' => $status,
                ]);
            }
        }

        throw new RuntimeException($lastError ?? 'OpenRouter error: no model responded.');
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @return \Generator<int, array<string, mixed>>
     */
    private function streamOnce(string $key, string $model, array $messages, array $tools): \Generator
    {
        $url = rtrim((string) config('ai_assistant.openrouter.base_url'), '/').'/chat/completions';
        $payload = json_encode($this->payload($model, $messages, $tools, true), JSON_UNESCAPED_UNICODE);
        $timeout = (int) config('ai_assistant.openrouter.timeout', 300);
        $connect = (int) config('ai_assistant.openrouter.connect_timeout', 15);

        $chunks = new SplQueue;
        $httpStatus = 0;

        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('OpenRouter error: unable to init curl.');
        }

        $headers = [];
        foreach ($this->headers($key) as $hk => $hv) {
            $headers[] = $hk.': '.$hv;
        }
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Accept: text/event-stream';

        $curlOpts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $connect,
            CURLOPT_HEADERFUNCTION => static function ($ch, $headerLine) use (&$httpStatus) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', $headerLine, $m)) {
                    $httpStatus = (int) $m[1];
                }

                return strlen($headerLine);
            },
            CURLOPT_WRITEFUNCTION => static function ($ch, $data) use ($chunks) {
                $chunks->enqueue($data);

                return strlen($data);
            },
        ];
        $ca = ini_get('curl.cainfo') ?: ini_get('openssl.cafile');
        if (is_string($ca) && $ca !== '' && is_file($ca)) {
            $curlOpts[CURLOPT_CAINFO] = $ca;
        }
        curl_setopt_array($ch, $curlOpts);

        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);

        $lineBuf = '';
        $content = '';
        $toolCalls = [];
        $sawToolCall = false;
        $finishReason = null;
        $usage = [];
        $responseModel = $model;
        $errorBody = '';

        $running = null;
        do {
            do {
                $mrc = curl_multi_exec($mh, $running);
            } while ($mrc === CURLM_CALL_MULTI_PERFORM);

            while (! $chunks->isEmpty()) {
                $fresh = $chunks->dequeue();
                if ($httpStatus >= 400) {
                    $errorBody .= $fresh;

                    continue;
                }

                $lineBuf .= $fresh;
                while (($pos = strpos($lineBuf, "\n")) !== false) {
                    $line = rtrim(substr($lineBuf, 0, $pos), "\r");
                    $lineBuf = substr($lineBuf, $pos + 1);

                    if ($line === '' || str_starts_with($line, ':')) {
                        continue;
                    }
                    if (! str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $data = trim(substr($line, 5));
                    if ($data === '[DONE]') {
                        continue;
                    }

                    $json = json_decode($data, true);
                    if (! is_array($json)) {
                        continue;
                    }

                    if (isset($json['error'])) {
                        $msg = is_array($json['error'])
                            ? (string) ($json['error']['message'] ?? json_encode($json['error']))
                            : (string) $json['error'];
                        curl_multi_remove_handle($mh, $ch);
                        curl_multi_close($mh);
                        curl_close($ch);
                        throw new RuntimeException('OpenRouter error: '.$msg);
                    }

                    if (isset($json['model'])) {
                        $responseModel = (string) $json['model'];
                    }
                    if (isset($json['usage']) && is_array($json['usage'])) {
                        $usage = $json['usage'];
                    }

                    $delta = $json['choices'][0]['delta'] ?? [];
                    $finishReason = $json['choices'][0]['finish_reason'] ?? $finishReason;

                    if (isset($delta['content']) && $delta['content'] !== null && $delta['content'] !== '') {
                        $piece = (string) $delta['content'];
                        $content .= $piece;
                        yield ['type' => 'delta', 'text' => $piece];
                    }

                    if (! empty($delta['tool_calls']) && is_array($delta['tool_calls'])) {
                        if (! $sawToolCall) {
                            $sawToolCall = true;
                            yield ['type' => 'tool_call_delta'];
                        }
                        foreach ($delta['tool_calls'] as $tc) {
                            $idx = (int) ($tc['index'] ?? 0);
                            if (! isset($toolCalls[$idx])) {
                                $toolCalls[$idx] = [
                                    'id' => '',
                                    'type' => 'function',
                                    'function' => ['name' => '', 'arguments' => ''],
                                ];
                            }
                            if (isset($tc['id'])) {
                                $toolCalls[$idx]['id'] = (string) $tc['id'];
                            }
                            if (isset($tc['type'])) {
                                $toolCalls[$idx]['type'] = (string) $tc['type'];
                            }
                            if (isset($tc['function']['name'])) {
                                $toolCalls[$idx]['function']['name'] .= (string) $tc['function']['name'];
                            }
                            if (isset($tc['function']['arguments'])) {
                                $toolCalls[$idx]['function']['arguments'] .= (string) $tc['function']['arguments'];
                            }
                        }
                    }
                }
            }

            if ($running) {
                curl_multi_select($mh, 1.0);
            }
        } while ($running);

        // Drain leftover bytes from the write queue (error body or trailing SSE).
        while (! $chunks->isEmpty()) {
            $fresh = $chunks->dequeue();
            if ($httpStatus >= 400) {
                $errorBody .= $fresh;
            } else {
                $lineBuf .= $fresh;
            }
        }
        if ($httpStatus < 400 && $lineBuf !== '' && str_starts_with(trim($lineBuf), 'data:')) {
            $data = trim(substr(trim($lineBuf), 5));
            if ($data !== '' && $data !== '[DONE]') {
                $json = json_decode($data, true);
                if (is_array($json)) {
                    $delta = $json['choices'][0]['delta'] ?? [];
                    if (isset($delta['content']) && $delta['content'] !== '') {
                        $piece = (string) $delta['content'];
                        $content .= $piece;
                        yield ['type' => 'delta', 'text' => $piece];
                    }
                    if (isset($json['usage']) && is_array($json['usage'])) {
                        $usage = $json['usage'];
                    }
                    if (isset($json['model'])) {
                        $responseModel = (string) $json['model'];
                    }
                }
            }
        }

        $curlErr = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_multi_remove_handle($mh, $ch);
        curl_multi_close($mh);
        curl_close($ch);

        if ($curlErrno !== 0) {
            throw new RuntimeException('OpenRouter error: '.$curlErr);
        }

        if ($httpStatus >= 400) {
            throw new RuntimeException('OpenRouter error: '.$httpStatus.' '.$errorBody);
        }

        ksort($toolCalls);
        $message = ['role' => 'assistant', 'content' => $content !== '' ? $content : null];
        if ($toolCalls !== []) {
            $message['tool_calls'] = array_values($toolCalls);
        }

        yield [
            'type' => 'done',
            'response' => [
                'id' => 'stream',
                'model' => $responseModel,
                'choices' => [[
                    'index' => 0,
                    'finish_reason' => $finishReason,
                    'message' => $message,
                ]],
                'usage' => $usage,
            ],
        ];
    }

    /** @return list<string> */
    private function models(bool $vision): array
    {
        $group = $vision ? 'ai_assistant.vision' : 'ai_assistant.openrouter';

        return array_values(array_unique(array_filter(array_merge(
            [config($group.'.model')],
            config($group.'.fallback_models', []),
        ))));
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<array<string, mixed>>  $tools
     * @return array<string, mixed>
     */
    private function payload(string $model, array $messages, array $tools, bool $stream): array
    {
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => $stream,
        ];
        if ($tools !== []) {
            $payload['tools'] = $tools;
            $payload['tool_choice'] = 'auto';
        }

        return $payload;
    }

    /** @return array<string, string> */
    private function headers(string $key): array
    {
        return [
            'Authorization' => 'Bearer '.$key,
            'HTTP-Referer' => (string) config('app.url'),
            'X-Title' => (string) config('app.name', 'Pegasus Management'),
        ];
    }

    private function statusFromError(string $error): int
    {
        if (preg_match('/OpenRouter error:\s*(\d{3})/', $error, $m)) {
            return (int) $m[1];
        }

        return 0;
    }
}
