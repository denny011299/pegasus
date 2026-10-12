<?php

namespace App\Services\AiAssistant\Tools\Concerns;

/**
 * Saran "apakah maksudnya…" saat kata kunci tidak ketemu persis: kode dokumen
 * (PO567 → PO0567, salah ketik satu digit/huruf) dan nama (staf, customer,
 * pemasok, bahan). Hasilnya hanya saran — tool tidak boleh memakainya sebelum
 * user memilih. Requires $this->db and $this->vocab.
 */
trait FuzzySuggest
{
    /**
     * @param  array<int|string, string>  $labels  id => teks yang dibandingkan
     * @return list<array{id: int|string, label: string, score: int}>
     */
    protected function fuzzyRank(string $keyword, array $labels, int $max = 5, int $min = 55): array
    {
        $kw = $this->fuzzyNorm($keyword);
        if ($kw === '') {
            return [];
        }
        $tokens = explode(' ', $kw);
        $out = [];
        foreach ($labels as $id => $label) {
            $hay = $this->fuzzyNorm((string) $label);
            if ($hay === '') {
                continue;
            }
            $score = $this->fuzzyScore($kw, $tokens, $hay);
            if ($score >= $min) {
                $out[] = ['id' => $id, 'label' => trim((string) $label), 'score' => $score];
            }
        }
        usort($out, fn ($a, $b) => [$b['score'], strlen($a['label'])] <=> [$a['score'], strlen($b['label'])]);
        // Nama kembar (staf dobel akun) cukup tampil sekali.
        $unique = [];
        foreach ($out as $o) {
            $unique[mb_strtolower($o['label'])] ??= $o;
        }

        return array_slice(array_values($unique), 0, $max);
    }

    /**
     * Kode dokumen mirip di tabel-tabel kandidat. Nomor sama dengan nol di depan
     * berbeda (PO567 vs PO0567) dianggap paling mirip.
     *
     * @param  list<string>  $tables
     * @return list<array{Kode: string, Modul: string, Tanggal?: string, Kemiripan: string}>
     */
    protected function suggestDocumentCodes(string $code, array $tables, int $max = 5): array
    {
        $code = strtoupper(trim($code));
        if (!preg_match('/^([A-Z]*)[\s\-\/]*0*(\d+)/', $code, $m) && !preg_match('/^([A-Z]+)/', $code, $m)) {
            return [];
        }
        $prefix = $m[1] ?? '';
        $number = $m[2] ?? '';

        $found = [];
        $seen = [];
        foreach ($tables as $table) {
            // Satu dokumen bisa punya beberapa kode (SO: No. Invoice + No. SO); cek semuanya.
            foreach ($this->vocab->codeFields($table) as $field) {
                $this->collectCodeSuggestions($table, $field, $code, $prefix, $number, $found, $seen);
            }
        }
        usort($found, fn ($a, $b) => $b['_s'] <=> $a['_s']);

        return array_column(array_slice($found, 0, $max), 'row');
    }

    private function collectCodeSuggestions(string $table, string $field, string $code, string $prefix, string $number, array &$found, array &$seen): void
    {
        $dateCol = $this->vocab->dateField($table);
        // Hanya kode berawalan sama; tanpa awalan → kode yang memuat angkanya.
        $rows = $this->db->table($table)
            ->when($prefix !== '', fn ($q) => $q->where($field, 'like', $prefix.'%'))
            ->when($prefix === '' && $number !== '', fn ($q) => $q->where($field, 'like', '%'.$number.'%'))
            ->orderByDesc($this->db->primaryKey($table))
            ->limit(5000)
            ->get(array_values(array_filter([$field, $dateCol])));
        foreach ($rows as $r) {
            $candidate = strtoupper((string) $r->{$field});
            if (isset($seen[$table.$candidate])) {
                continue;
            }
            $score = $this->codeScore($code, $prefix, $number, $candidate);
            if ($score >= 60) {
                $seen[$table.$candidate] = true;
                $found[] = ['_s' => $score, 'row' => array_filter([
                    'Kode' => (string) $r->{$field},
                    'Modul' => $this->vocab->moduleLabel($table),
                    'Tanggal' => $dateCol ? substr((string) $r->{$dateCol}, 0, 10) : null,
                    'Kemiripan' => $score.'%',
                ])];
            }
        }
    }

    /** Standar balasan "tidak ketemu" + saran, dipakai semua tool. */
    protected function notFoundWithSuggestions(string $what, string $keyword, array $suggestions): array
    {
        return $suggestions === []
            ? ['ditemukan' => false, 'pesan' => $what.' "'.$keyword.'" tidak ditemukan dan tidak ada yang mirip. Minta user mengecek ejaan/kode.']
            : [
                'ditemukan' => false,
                'pesan' => $what.' "'.$keyword.'" tidak ditemukan persis. Tanyakan "Apakah maksudnya salah satu ini?" lalu cari ulang setelah user memilih.',
                'saran' => $suggestions,
            ];
    }

    private function codeScore(string $code, string $prefix, string $number, string $candidate): int
    {
        if ($number !== '' && preg_match('/^([A-Z]*)[\s\-\/]*0*(\d+)/', $candidate, $c)
            && $c[1] === $prefix && $c[2] === $number) {
            return 100;
        }
        $a = preg_replace('/[^A-Z0-9]/', '', $code) ?? $code;
        $b = preg_replace('/[^A-Z0-9]/', '', $candidate) ?? $candidate;

        return $a === '' || $b === '' ? 0 : (int) round((1 - levenshtein($a, $b) / max(strlen($a), strlen($b))) * 100);
    }

    /** @param list<string> $tokens */
    private function fuzzyScore(string $kw, array $tokens, string $hay): int
    {
        if (str_contains($hay, $kw)) {
            return 95;
        }
        $best = $this->fuzzyRatio($kw, $hay);
        $hayTokens = explode(' ', $hay);
        $sum = 0.0;
        foreach ($tokens as $t) {
            $tokenBest = 0.0;
            foreach ($hayTokens as $h) {
                $tokenBest = max($tokenBest, $this->fuzzyRatio($t, $h));
            }
            $sum += $tokenBest;
        }

        return (int) round(max($best, $sum / max(1, count($tokens))));
    }

    private function fuzzyRatio(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }
        if (strlen($a) > 255 || strlen($b) > 255) {
            similar_text($a, $b, $pct);

            return $pct;
        }

        return max(0.0, (1 - levenshtein($a, $b) / max(strlen($a), strlen($b))) * 100);
    }

    private function fuzzyNorm(string $value): string
    {
        $value = preg_replace('/[^a-z0-9]+/u', ' ', mb_strtolower(trim($value))) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
