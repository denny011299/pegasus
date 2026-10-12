<?php

namespace App\Services\AiAssistant\Tools\Concerns;

use InvalidArgumentException;

/**
 * Resolves what the user calls a "barang" (nama, SKU, barcode, sebagian kata,
 * atau salah ketik) to product variants and bahan mentah.
 *
 * Order: SKU/barcode persis → semua kata terkandung → mirip (saran). The
 * fuzzy step only ever yields suggestions; tools must not act on them until
 * the user confirms which item they meant.
 *
 * Requires $this->db (ReadOnlyDb) and $this->vocab (Vocabulary).
 */
trait ItemMatcher
{
    /** Minimal kemiripan (0–100) supaya kandidat layak disarankan. */
    private int $suggestMinScore = 55;

    /** @var array<string, list<array<string, mixed>>> */
    private array $itemCandidateCache = [];

    /**
     * @return array{status: 'persis'|'cocok'|'saran'|'kosong', total: int, items: list<array<string, mixed>>}
     */
    protected function matchItems(string $keyword, string $jenis = 'semua', int $max = 10): array
    {
        $kw = $this->normText($keyword);
        $compact = str_replace(' ', '', $kw);
        if ($compact === '') {
            throw new InvalidArgumentException('Nama atau SKU barang wajib diisi.');
        }

        $candidates = $this->itemCandidates($jenis);

        $exact = array_values(array_filter($candidates, fn ($c) => in_array($compact, $c['_codes'], true)));
        if ($exact !== []) {
            return ['status' => 'persis', 'total' => count($exact), 'items' => $this->rankActiveFirst($exact, $max)];
        }

        $tokens = explode(' ', $kw);
        $contains = array_values(array_filter($candidates, function ($c) use ($tokens, $compact) {
            if (str_contains($c['_compact'], $compact)) {
                return true;
            }
            foreach ($tokens as $t) {
                if (!str_contains($c['_hay'], $t)) {
                    return false;
                }
            }

            return true;
        }));
        if ($contains !== []) {
            // Nama paling pendek = paling dekat dengan kata kunci.
            usort($contains, fn ($a, $b) => [$b['aktif'], strlen($a['_hay'])] <=> [$a['aktif'], strlen($b['_hay'])]);

            return ['status' => 'cocok', 'total' => count($contains), 'items' => array_slice($contains, 0, $max)];
        }

        $scored = [];
        foreach ($candidates as $c) {
            $score = $this->similarity($kw, $compact, $tokens, $c);
            if ($score >= $this->suggestMinScore) {
                $c['_score'] = $score;
                $scored[] = $c;
            }
        }
        usort($scored, fn ($a, $b) => [$b['_score'], $b['aktif'], strlen($a['_hay'])] <=> [$a['_score'], $a['aktif'], strlen($b['_hay'])]);

        return $scored === []
            ? ['status' => 'kosong', 'total' => 0, 'items' => []]
            : ['status' => 'saran', 'total' => count($scored), 'items' => array_slice($scored, 0, 5)];
    }

    /**
     * Standard reply when the keyword does not resolve to usable item(s):
     * suggestions (saran) or a list to choose from (pilihan). Returns null
     * when the match can be used as-is.
     */
    protected function itemNotFound(string $keyword, array $match, int $maxItems = 10): ?array
    {
        if (in_array($match['status'], ['persis', 'cocok'], true) && $match['total'] <= $maxItems) {
            return null;
        }
        $base = ['ditemukan' => false, 'kata_kunci' => $keyword];

        return match (true) {
            $match['status'] === 'saran' => $base + [
                'pesan' => 'Barang "'.$keyword.'" tidak ditemukan persis. Tanyakan ke user apakah maksudnya salah satu saran ini, lalu cari ulang dengan SKU-nya.',
                'saran' => array_map(fn ($c) => $this->presentItem($c), $match['items']),
            ],
            $match['status'] === 'kosong' => $base + [
                'pesan' => 'Barang "'.$keyword.'" tidak ditemukan dan tidak ada nama/SKU yang mirip. Minta user mengecek ejaan atau SKU.',
            ],
            default => $base + [
                'pesan' => $match['total'].' barang cocok dengan "'.$keyword.'". Tanyakan ke user barang yang mana (sebut SKU).',
                'pilihan' => array_map(fn ($c) => $this->presentItem($c), array_slice($match['items'], 0, 10)),
            ],
        };
    }

    /** Candidate in UI wording (no storage keys) for tool output. */
    protected function presentItem(array $c): array
    {
        $out = [
            'Jenis' => $c['jenis'] === 'produk' ? 'Produk' : 'Bahan Mentah',
            'SKU' => $c['sku'] !== '' ? $c['sku'] : '-',
            'Nama' => $c['nama'],
        ];
        if ($c['varian'] !== '') {
            $out['Varian'] = $c['varian'];
        }
        if (!$c['aktif']) {
            $out['Status'] = 'Nonaktif';
        }
        if (isset($c['_score'])) {
            $out['Kemiripan'] = $c['_score'].'%';
        }

        return $out;
    }

    /** @return list<array<string, mixed>> */
    private function itemCandidates(string $jenis): array
    {
        if (isset($this->itemCandidateCache[$jenis])) {
            return $this->itemCandidateCache[$jenis];
        }

        $out = [];
        if ($jenis !== 'bahan') {
            $names = $this->db->table('products')->pluck('product_name', 'product_id')->all();
            $inactive = $this->vocab->inactiveStatuses('product_variants');
            $rows = $this->db->table('product_variants')
                ->select(['product_variant_id', 'product_id', 'product_variant_name', 'product_variant_sku', 'product_variant_barcode', 'status'])
                ->limit(5000)
                ->get();
            foreach ($rows as $v) {
                $out[] = $this->candidate(
                    'produk',
                    (int) $v->product_variant_id,
                    (string) ($names[$v->product_id] ?? ''),
                    (string) $v->product_variant_name,
                    [(string) $v->product_variant_sku, (string) $v->product_variant_barcode],
                    !in_array((int) $v->status, $inactive, true),
                );
            }
        }

        if ($jenis !== 'produk') {
            $skus = [];
            $variants = $this->db->table('supplies_variants')
                ->select(['supplies_id', 'supplies_variant_sku'])
                ->limit(10000)
                ->get();
            foreach ($variants as $v) {
                if ((string) $v->supplies_variant_sku !== '') {
                    $skus[(int) $v->supplies_id][] = (string) $v->supplies_variant_sku;
                }
            }
            $inactive = $this->vocab->inactiveStatuses('supplies');
            foreach ($this->db->table('supplies')->select(['supplies_id', 'supplies_name', 'status'])->limit(5000)->get() as $s) {
                $out[] = $this->candidate(
                    'bahan',
                    (int) $s->supplies_id,
                    (string) $s->supplies_name,
                    '',
                    array_values(array_unique($skus[(int) $s->supplies_id] ?? [])),
                    !in_array((int) $s->status, $inactive, true),
                );
            }
        }

        return $this->itemCandidateCache[$jenis] = $out;
    }

    /** @param list<string> $codes SKU/barcode; the first one is shown as SKU. */
    private function candidate(string $jenis, int $id, string $nama, string $varian, array $codes, bool $aktif): array
    {
        $codes = array_values(array_filter($codes, fn ($c) => trim($c) !== ''));
        $hay = $this->normText($nama.' '.$varian.' '.implode(' ', $codes));

        return [
            'jenis' => $jenis,
            'id' => $id,
            'sku' => implode(', ', array_slice($codes, 0, $jenis === 'produk' ? 1 : 3)),
            'nama' => $nama,
            'varian' => $varian,
            'aktif' => $aktif,
            '_codes' => array_map(fn ($c) => str_replace(' ', '', $this->normText($c)), $codes),
            '_hay' => $hay,
            '_compact' => str_replace(' ', '', $hay),
        ];
    }

    /**
     * 0–100: best of whole-SKU similarity, whole-name similarity, and
     * per-word similarity (tahan salah ketik satu-dua huruf per kata).
     *
     * @param  list<string>  $tokens
     */
    private function similarity(string $kw, string $compact, array $tokens, array $c): int
    {
        $best = 0.0;
        foreach ($c['_codes'] as $code) {
            $best = max($best, $this->ratio($compact, $code));
        }
        $name = $this->normText($c['nama'].' '.$c['varian']);
        $best = max($best, $this->ratio($kw, $name), $this->ratio($kw, $this->normText($c['nama'])));

        $hayTokens = explode(' ', $c['_hay']);
        $sum = 0.0;
        foreach ($tokens as $t) {
            $tokenBest = 0.0;
            foreach ($hayTokens as $h) {
                $tokenBest = max($tokenBest, $this->ratio($t, $h));
            }
            $sum += $tokenBest;
        }
        $best = max($best, $tokens === [] ? 0 : $sum / count($tokens));

        return (int) round($best);
    }

    private function ratio(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }
        if (strlen($a) > 255 || strlen($b) > 255) {
            similar_text($a, $b, $pct);

            return $pct;
        }
        $lev = 1 - levenshtein($a, $b) / max(strlen($a), strlen($b));

        return max(0.0, $lev * 100);
    }

    /** @param list<array<string, mixed>> $items */
    private function rankActiveFirst(array $items, int $max): array
    {
        usort($items, fn ($a, $b) => $b['aktif'] <=> $a['aktif']);

        return array_slice($items, 0, $max);
    }

    private function normText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(',', '.', $value);
        // Volume disamakan ke ml: "1 liter" / "1L" / "1000 ml" → "1000ml".
        $value = preg_replace_callback(
            '/(\d+(?:\.\d+)?)\s*(liter|litre|ltr|lt|l|ml)\b/u',
            fn ($m) => ' '.rtrim(rtrim(number_format((float) $m[1] * ($m[2] === 'ml' ? 1 : 1000), 2, '.', ''), '0'), '.').'ml ',
            $value,
        ) ?? $value;
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
