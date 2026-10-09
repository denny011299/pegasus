<?php

namespace App\Services\AiAssistant;

use Illuminate\Support\Facades\DB;

/**
 * Flow-knowledge retrieval for the prompt. The full pipeline (indexing,
 * stopwords, synonyms, scoring) is documented under the "rag" key in
 * config/ai_assistant.php.
 */
class DocRetriever
{
    public function topChunks(string $question, ?int $limit = null): string
    {
        $limit = max(1, $limit ?? (int) config('ai_assistant.rag.top_k', 6));
        $terms = $this->terms($question);
        if ($terms === []) {
            return '';
        }

        $rows = $this->candidates($terms, $question);
        if ($rows->isEmpty()) {
            return '';
        }

        $scored = $rows->map(fn ($row) => [
            'row' => $row,
            'score' => $this->score($row, $terms),
        ])
            ->filter(fn ($item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->take($limit);

        if ($scored->isEmpty()) {
            return '';
        }

        $parts = [];
        foreach ($scored as $item) {
            $row = $item['row'];
            $title = trim(($row->feature ?? '').' — '.($row->heading ?? ''), ' —');
            $parts[] = ($title !== '' ? "## {$title}\n" : '').$row->content;
        }

        return implode("\n\n---\n\n", $parts);
    }

    /**
     * Question words minus stopwords, each expanded with its synonyms.
     *
     * @return list<string>
     */
    private function terms(string $question): array
    {
        $min = (int) config('ai_assistant.rag.min_term_length', 3);
        $stopwords = array_map('strtolower', config('ai_assistant.rag.stopwords', []));
        $synonyms = config('ai_assistant.rag.synonyms', []);

        $clean = strtolower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $question) ?? $question);
        $words = array_values(array_filter(preg_split('/\s+/', $clean) ?: []));

        $terms = [];
        foreach ($words as $word) {
            if (in_array($word, $stopwords, true) || mb_strlen($word) < $min) {
                continue;
            }
            $terms[] = $word;
            foreach ($synonyms[$word] ?? [] as $synonym) {
                foreach (explode(' ', strtolower($synonym)) as $part) {
                    if (mb_strlen($part) >= $min && !in_array($part, $stopwords, true)) {
                        $terms[] = $part;
                    }
                }
            }
        }

        // Multi-word synonym keys such as "tanda terima".
        foreach ($synonyms as $key => $values) {
            if (str_contains($key, ' ') && str_contains($clean, $key)) {
                foreach ($values as $synonym) {
                    $terms[] = strtolower($synonym);
                }
            }
        }

        return array_values(array_unique($terms));
    }

    /** @param list<string> $terms */
    private function candidates(array $terms, string $question): \Illuminate\Support\Collection
    {
        $take = (int) config('ai_assistant.rag.candidates', 40);
        $boolean = implode(' ', array_map(fn ($t) => $t.'*', $terms));

        try {
            return DB::table('ai_doc_chunks')
                ->select(['feature', 'heading', 'content'])
                ->whereRaw('MATCH(content) AGAINST(? IN BOOLEAN MODE)', [$boolean])
                ->limit($take)
                ->get();
        } catch (\Throwable) {
            // No FULLTEXT index (tests, fresh install): fall back to LIKE.
            $query = DB::table('ai_doc_chunks')->select(['feature', 'heading', 'content']);
            $query->where(function ($sub) use ($terms, $question) {
                $sub->where('content', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], trim($question)).'%');
                foreach ($terms as $term) {
                    $sub->orWhere('content', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%');
                }
            });

            return $query->limit($take)->get();
        }
    }

    /**
     * Heading hits weigh more than body hits, repeated hits add up, and
     * shorter chunks win ties so answers stay focused.
     *
     * @param  list<string>  $terms
     */
    private function score(object $row, array $terms): float
    {
        $heading = strtolower(($row->feature ?? '').' '.($row->heading ?? ''));
        $content = strtolower((string) ($row->content ?? ''));
        $score = 0.0;

        foreach ($terms as $term) {
            // Whole-word counts only, so "acc" does not match "account".
            $pattern = '/\b'.preg_quote($term, '/').'/u';
            $inHeading = preg_match_all($pattern, $heading) ?: 0;
            $inContent = preg_match_all($pattern, $content) ?: 0;
            if ($inHeading > 0) {
                $score += 3 + min($inHeading, 3);
            }
            if ($inContent > 0) {
                $score += 1 + min($inContent, 4) * 0.5;
            }
        }

        $length = max(mb_strlen($content), 1);

        return $score > 0 ? $score + min(500 / $length, 1.5) : 0.0;
    }
}
