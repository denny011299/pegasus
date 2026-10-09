<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class IndexAiDocsCommand extends Command
{
    protected $signature = 'ai:index-docs {--force : Reindex even if some files still have UNCERTAIN tags (not recommended)}';

    protected $description = 'Chunk confirmed Markdown docs from storage/ai_docs into ai_doc_chunks (FULLTEXT)';

    public function handle(): int
    {
        $dir = config('ai_assistant.docs_path', storage_path('ai_docs'));
        if (!is_dir($dir)) {
            $this->error("Docs path not found: {$dir}");

            return self::FAILURE;
        }

        $files = collect(File::files($dir))
            ->filter(fn ($f) => str_ends_with(strtolower($f->getFilename()), '.md'))
            ->filter(fn ($f) => !str_starts_with($f->getFilename(), '_'))
            ->values();

        $skipped = [];
        $chunks = [];
        $size = (int) config('ai_assistant.chunk_size', 800);

        foreach ($files as $file) {
            $body = File::get($file->getPathname());
            if (str_contains($body, '[UNCERTAIN') && !$this->option('force')) {
                $skipped[] = $file->getFilename();
                continue;
            }

            $feature = pathinfo($file->getFilename(), PATHINFO_FILENAME);
            foreach ($this->chunkMarkdown($body, $size) as $chunk) {
                $chunks[] = [
                    'feature' => $feature,
                    'heading' => $chunk['heading'],
                    'content' => $chunk['content'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        if ($skipped !== []) {
            $this->warn('Skipped (still have [UNCERTAIN]): '.implode(', ', $skipped));
        }

        if ($chunks === []) {
            $this->error('No chunks to index.');

            return self::FAILURE;
        }

        DB::table('ai_doc_chunks')->delete();
        foreach (array_chunk($chunks, 100) as $batch) {
            DB::table('ai_doc_chunks')->insert($batch);
        }

        $this->info('Indexed '.count($chunks).' chunks from '.(count($files) - count($skipped)).' files.');

        return self::SUCCESS;
    }

    /** @return list<array{heading: ?string, content: string}> */
    private function chunkMarkdown(string $markdown, int $size): array
    {
        $sections = preg_split('/(?=^#{1,3}\s+)/m', $markdown) ?: [$markdown];
        $out = [];
        foreach ($sections as $section) {
            $section = trim($section);
            if ($section === '') {
                continue;
            }
            $heading = null;
            if (preg_match('/^(#{1,3})\s+(.+)$/m', $section, $m)) {
                $heading = trim($m[2]);
            }
            if (mb_strlen($section) <= $size) {
                $out[] = ['heading' => $heading, 'content' => $section];
                continue;
            }
            $parts = str_split($section, $size);
            foreach ($parts as $i => $part) {
                $out[] = [
                    'heading' => $heading.($i > 0 ? ' (lanjutan)' : ''),
                    'content' => $part,
                ];
            }
        }

        return $out;
    }
}
