<?php

namespace App\Console\Commands;

use App\Services\AiAssistant\AssistantGuide;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/** Tulis referensi developer Asisten AI (tool, parameter, bobot penyebab) ke Markdown — bukan untuk halaman web user. */
class AiDevDocsCommand extends Command
{
    protected $signature = 'ai:dev-docs {--path=docs/AI_ASSISTANT_TOOLS.md : Lokasi file output (relatif ke root project)}';

    protected $description = 'Generate referensi developer Asisten AI (Markdown) dari AssistantGuide';

    public function handle(AssistantGuide $guide): int
    {
        $path = base_path($this->option('path'));
        File::put($path, $guide->developerMarkdown());
        $this->info('Ditulis: '.$path);

        return self::SUCCESS;
    }
}
