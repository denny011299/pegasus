<?php

namespace App\Services\AiAssistant;

/**
 * Last line of defence for the "no storage wording" rule: even if a model
 * ignores the prompt, the answer that reaches the user contains no storage
 * identifiers, only app labels.
 */
class AnswerSanitizer
{
    private const TECHNICAL_WORDS = [
        'database' => 'sistem',
        'basis data' => 'sistem',
        'tabel' => 'data',
        'table' => 'data',
        'kolom' => 'informasi',
        'column' => 'informasi',
        'field' => 'informasi',
        'schema' => 'struktur data',
        'skema' => 'struktur data',
        'query' => 'pencarian',
        'sql' => 'pencarian data',
        'primary key' => 'penanda data',
        'foreign key' => 'keterkaitan data',
        'join' => 'penggabungan data',
    ];

    public function __construct(private Vocabulary $vocab) {}

    public function clean(string $answer): string
    {
        if ($answer === '') {
            return $answer;
        }

        $known = $this->knownIdentifiers();

        // Storage identifiers are snake_case; swap them for app labels.
        // Lowercase ones always go; mixed-case ones only when they are a
        // known storage name, so uppercase SKUs like ABC_123 stay intact.
        $answer = preg_replace_callback(
            '/`?\b([A-Za-z][A-Za-z0-9]*(?:_[A-Za-z0-9]+)+)\b`?/',
            function (array $m) use ($known) {
                $id = $m[1];
                if ($id === strtolower($id) || isset($known[strtolower($id)])) {
                    return $this->labelFor($id);
                }

                return $m[0];
            },
            $answer,
        ) ?? $answer;

        // Single-word storage names (plural English module names).
        $single = array_filter(array_keys($known), fn ($k) => !str_contains($k, '_') && isset(config('ai_assistant.tables')[$k]));
        if ($single !== []) {
            $answer = preg_replace_callback(
                '/`?\b('.implode('|', array_map(fn ($w) => preg_quote($w, '/'), $single)).')\b`?/i',
                fn (array $m) => $this->labelFor($m[1]),
                $answer,
            ) ?? $answer;
        }

        foreach (self::TECHNICAL_WORDS as $word => $replacement) {
            $answer = preg_replace(
                '/\b'.preg_quote($word, '/').'\b/iu',
                $replacement,
                $answer,
            ) ?? $answer;
        }

        // The chat bubble shows plain text, so markdown marks would appear raw.
        $answer = str_replace(['**', '__'], '', $answer);
        $answer = preg_replace('/^#{1,6}\s+/m', '', $answer) ?? $answer;

        // Jangan bocorkan aturan internal / kata "bug" ke user.
        $answer = $this->stripPolicyLeakage($answer);

        return trim($answer);
    }

    /**
     * Hapus narasi meta ("saya tidak akan menyebut bug…") dan ganti label teknis
     * yang tidak boleh muncul di chat staf.
     */
    private function stripPolicyLeakage(string $answer): string
    {
        $patterns = [
            '/(?im)^[^\n]*(?:saya|aku)\s+tidak\s+akan[^\n]*(?:bug|error sistem|kesalahan sistem|kesalahan user)[^\n]*\n?/u',
            '/(?im)^[^\n]*(?:tidak\s+akan|jangan)\s+menyebut(?:nya)?\s*[\"“]?bug[\"”]?[^\n]*\n?/u',
            '/(?im)^[^\n]*NEVER\s+call\s+it\s+a\s+bug[^\n]*\n?/iu',
            '/(?im)^[^\n]*Do\s+NOT\s+conclude[^\n]*\n?/iu',
            '/(?im)^[^\n]*Find\s+Document[^\n]*\n?/u',
            '/(?im)^[^\n]*via\s+Find\s+Document[^\n]*\n?/u',
        ];
        foreach ($patterns as $re) {
            $answer = preg_replace($re, '', $answer) ?? $answer;
        }

        // Ganti frasa yang merusak reputasi aplikasi/developer (urutan: frasa panjang dulu).
        $replacements = [
            'kesalahan developer' => 'ketidaksesuaian data',
            'kesalahan programmer' => 'ketidaksesuaian data',
            'salah coding' => 'ketidaksesuaian data',
            'salah kode' => 'ketidaksesuaian data',
            'kerusakan aplikasi' => 'ketidaksesuaian data',
            'aplikasi rusak' => 'ketidaksesuaian data',
            'sistem bermasalah' => 'ketidaksesuaian data',
            'sistem rusak' => 'ketidaksesuaian data',
            'cacat sistem' => 'ketidaksesuaian data',
            'cacat aplikasi' => 'ketidaksesuaian data',
            'error sistem' => 'ketidaksesuaian data',
            'kesalahan sistem' => 'ketidaksesuaian data',
            'bug aplikasi' => 'ketidaksesuaian data',
            'bug sistem' => 'ketidaksesuaian data',
            'software bug' => 'ketidaksesuaian data',
        ];
        foreach ($replacements as $from => $to) {
            $answer = preg_replace('/\b'.preg_quote($from, '/').'\b/iu', $to, $answer) ?? $answer;
        }
        $answer = preg_replace('/\bglitch\b/iu', 'ketidaksesuaian data', $answer) ?? $answer;
        $answer = preg_replace('/\bbug\b/iu', 'ketidaksesuaian data', $answer) ?? $answer;

        // Rapikan baris kosong berlebih setelah strip.
        $answer = preg_replace("/\n{3,}/", "\n\n", $answer) ?? $answer;

        return $answer;
    }

    /**
     * Every whitelisted storage name (lowercased) — module names plus
     * their fields, minus everyday words like "status" that also appear
     * in normal answers.
     *
     * @return array<string, true>
     */
    private function knownIdentifiers(): array
    {
        $known = [];
        foreach (config('ai_assistant.tables', []) as $table => $def) {
            $known[strtolower($table)] = true;
            foreach ($def['columns'] ?? [] as $column) {
                if (str_contains($column, '_') || preg_match('/[A-Z]/', $column)) {
                    $known[strtolower($column)] = true;
                }
            }
        }

        return $known;
    }

    private function labelFor(string $identifier): string
    {
        $key = strtolower($identifier);

        $modules = config('ai_vocabulary.modules', []);
        foreach ($modules as $def) {
            if ($def['table'] === $key) {
                return $def['label'];
            }
            $detail = $def['detail']['table'] ?? null;
            if ($detail === $key) {
                return 'rincian '.strtolower($def['label']);
            }
        }

        if (isset(config('ai_assistant.tables')[$key])) {
            return 'data terkait';
        }

        return $this->vocab->label($key);
    }
}
