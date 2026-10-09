<?php

namespace App\Http\Controllers;

use App\Services\AiAssistant\AiChatService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiChatController extends Controller
{
    /**
     * One conversation per login session: it survives moving between menus
     * and is gone once the session ends (logout invalidates it, expiry
     * drops it), so the next login starts a fresh chat.
     */
    private const SESSION_KEY = 'ai_conversation_id';

    /** Full-page chat (open-in-new-tab); no floating widget chrome. */
    public function page()
    {
        return view('Backoffice.Ai.Chat');
    }

    public function history(AiChatService $service)
    {
        $staffId = $this->staffId();
        if (! $staffId) {
            return response()->json(['status' => -1, 'message' => 'Unauthorized'], 401);
        }

        $conversationId = Session::get(self::SESSION_KEY);

        return response()->json([
            'status' => 1,
            'messages' => $conversationId ? $service->history($conversationId, $staffId) : [],
        ]);
    }

    /**
     * Serve stored chat attachment for the owning staff only (private disk).
     */
    public function historyImage(int $id)
    {
        $staffId = $this->staffId();
        if (! $staffId) {
            abort(401);
        }

        $row = DB::table('ai_chat_logs')
            ->where('id', $id)
            ->where('staff_id', $staffId)
            ->first(['image_path']);

        if (! $row || ! is_string($row->image_path) || $row->image_path === '') {
            abort(404);
        }

        // Path harus di bawah folder vision storage_dir — cegah path traversal via DB.
        $dir = trim((string) config('ai_assistant.vision.storage_dir', 'ai_chat'), '/');
        $path = str_replace('\\', '/', $row->image_path);
        if ($path !== $row->image_path || str_contains($path, '..') || ! str_starts_with($path, $dir.'/')) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path);
    }

    public function chat(Request $request, AiChatService $service)
    {
        $this->extendRuntime();

        $message = trim((string) $request->input('message', ''));

        $image = null;
        if ($request->hasFile('image')) {
            $image = $this->imageDataUrl($request->file('image'));
            if ($image === null) {
                return response()->json([
                    'status' => -1,
                    'message' => 'Gambar tidak bisa dibaca. Gunakan JPG, PNG, atau WEBP maksimal '.((int) config('ai_assistant.vision.max_kb', 5120) / 1024).' MB.',
                ], 422);
            }
            if ($message === '') {
                $message = 'Tolong baca gambar ini.';
            }
        }

        if ($message === '') {
            return response()->json(['status' => -1, 'message' => 'Pesan kosong'], 422);
        }

        $staffId = $this->staffId();
        if (! $staffId) {
            return response()->json(['status' => -1, 'message' => 'Unauthorized'], 401);
        }

        // Server owns the conversation id; a client-sent id is ignored so no
        // one can read another staff's chat by guessing it.
        $conversationId = Session::get(self::SESSION_KEY);
        if (! $conversationId) {
            $conversationId = (string) Str::uuid();
            Session::put(self::SESSION_KEY, $conversationId);
        }

        // Default: SSE streaming (ChatGPT-style). ?stream=0 => JSON lama.
        $wantStream = $request->input('stream', '1') !== '0'
            && ! $request->boolean('no_stream');

        if ($wantStream) {
            return $this->streamChat($service, $message, $conversationId, $staffId, $image);
        }

        try {
            $result = $service->chat($message, $conversationId, $staffId, $image);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => -1,
                'message' => $this->friendlyError($e->getMessage(), $image !== null),
            ], 500);
        }

        return response()->json([
            'status' => 1,
            'conversation_id' => $result['conversation_id'],
            'answer' => $result['answer'],
        ]);
    }

    private function streamChat(
        AiChatService $service,
        string $message,
        string $conversationId,
        int $staffId,
        ?string $image,
    ): StreamedResponse {
        // Session harus selesai ditulis sebelum stream panjang (hindari lock).
        if (session()->isStarted()) {
            session()->save();
        }

        return response()->stream(function () use ($service, $message, $conversationId, $staffId, $image) {
            // Stream jalan setelah controller return — set ulang di sini
            // (php.ini max_execution_time=30 sering masih aktif di built-in server).
            $this->extendRuntime();

            // Matikan buffer supaya chunk langsung ke browser.
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            @ini_set('implicit_flush', '1');
            @ini_set('zlib.output_compression', '0');

            $send = function (string $event, array $data) {
                echo 'event: '.$event."\n";
                echo 'data: '.json_encode($data, JSON_UNESCAPED_UNICODE)."\n\n";
                if (function_exists('flush')) {
                    flush();
                }
            };

            try {
                foreach ($service->chatStream($message, $conversationId, $staffId, $image) as $ev) {
                    $event = (string) ($ev['event'] ?? 'message');
                    $data = is_array($ev['data'] ?? null) ? $ev['data'] : [];
                    $send($event, $data);
                    if ($event === 'done' || $event === 'error') {
                        break;
                    }
                }
            } catch (\Throwable $e) {
                report($e);
                $send('error', [
                    'message' => $this->friendlyError($e->getMessage(), $image !== null),
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    private function extendRuntime(): void
    {
        // php.ini lokal sering max_execution_time=30; itu yang bikin SSE AI
        // mati meski OPENROUTER_TIMEOUT/AI_MAX_EXECUTION_SECONDS=300 di .env.
        // 0 = unlimited di sisi PHP; batas nyata tetap di curl OpenRouter.
        $configured = (int) config('ai_assistant.max_execution_seconds', 300);
        $seconds = $configured > 0 ? max($configured, 300) : 0;
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');
        // Cadangan kalau host menolak unlimited.
        if ((int) ini_get('max_execution_time') !== 0) {
            @set_time_limit($seconds);
            @ini_set('max_execution_time', (string) $seconds);
        }
        ignore_user_abort(true);
    }

    private function friendlyError(string $raw, bool $hadImage): string
    {
        return match (true) {
            // Free tier daily cap (50 requests/day without credits).
            str_contains($raw, 'free-models-per-day') => 'Batas pemakaian harian asisten AI sudah tercapai. Silakan coba lagi besok, atau hubungi tim development untuk menambah kuota.',
            str_contains($raw, 'Maximum execution time') || str_contains($raw, 'timed out') || str_contains($raw, 'cURL error 28') => 'Jawaban AI terlalu lama (timeout). Coba pertanyaan lebih spesifik, atau ulangi beberapa saat lagi.',
            $hadImage && (str_contains($raw, 'OpenRouter error: 404') || str_contains($raw, 'image')) => 'Model pembaca gambar sedang tidak tersedia. Silakan coba lagi nanti atau kirim pertanyaan tanpa gambar.',
            str_contains($raw, 'Key limit exceeded'),
            str_contains($raw, 'OpenRouter error: 402'),
            str_contains($raw, 'OpenRouter error: 429') => 'Layanan AI sedang mencapai batas pemakaian. Silakan coba lagi beberapa saat lagi, atau hubungi tim development jika masih berulang.',
            default => 'Asisten belum bisa memproses pertanyaan ini. Silakan coba lagi, dan hubungi tim development jika masih berulang.',
        };
    }

    /**
     * Validated upload => base64 data URL for the model (in-memory).
     * Persistence for history UI happens in AiChatService::persistImage().
     */
    private function imageDataUrl(?UploadedFile $file): ?string
    {
        if ($file === null || ! $file->isValid()) {
            return null;
        }
        if ($file->getSize() > (int) config('ai_assistant.vision.max_kb', 5120) * 1024) {
            return null;
        }

        // Trust the bytes, not the extension or the browser-sent type.
        $info = @getimagesize($file->getRealPath());
        $mime = $info['mime'] ?? null;
        if ($mime === null || ! in_array($mime, config('ai_assistant.vision.mimes', []), true)) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($file->getRealPath()));
    }

    private function staffId(): ?int
    {
        $user = Session::get('user');
        $staffId = $user ? (int) ($user->staff_id ?? 0) : 0;

        return $staffId ?: null;
    }
}
