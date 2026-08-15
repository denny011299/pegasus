<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class GenerateDeployManifestCommand extends Command
{
    protected $signature = 'deploy:manifest';

    protected $description = 'Generate deploy/manifest.json (daftar file + hash) dari git, dipakai halaman /system/deployment-check untuk mendeteksi file yang belum ter-upload ke server';

    /**
     * Hanya folder/berkas yang benar-benar dibutuhkan aplikasi saat runtime --
     * tests/, dokumentasi, dsb sengaja tidak diikutkan supaya tidak bikin
     * false-positive "file hilang" untuk sesuatu yang memang tidak dideploy.
     */
    private const INCLUDE_PREFIXES = [
        'app/', 'bootstrap/', 'config/', 'database/', 'public/', 'resources/', 'routes/',
        'artisan', 'composer.json', 'composer.lock',
    ];

    public function handle(): int
    {
        if (!is_dir(base_path('.git'))) {
            $this->error('Tidak ada folder .git di sini -- jalankan perintah ini dari repo git (bukan dari server production), lalu upload deploy/manifest.json yang dihasilkan.');
            return self::FAILURE;
        }

        $files = $this->gitLsFiles();
        if ($files === null) {
            $this->error('Gagal menjalankan "git ls-files". Pastikan git ter-install dan folder ini adalah repo git.');
            return self::FAILURE;
        }

        $commit = trim($this->git(['rev-parse', 'HEAD']) ?? '');
        $branch = trim($this->git(['rev-parse', '--abbrev-ref', 'HEAD']) ?? '');

        $hashes = [];
        foreach ($files as $relative) {
            if (!$this->isIncluded($relative)) {
                continue;
            }
            $full = base_path($relative);
            if (!is_file($full)) {
                // Tracked in git but not present locally (e.g. submodule quirk) -- skip.
                continue;
            }
            $hashes[$relative] = hash_file('sha1', $full);
        }
        ksort($hashes);

        $manifest = [
            'generated_at' => now()->toIso8601String(),
            'commit' => $commit,
            'branch' => $branch,
            'file_count' => count($hashes),
            'files' => $hashes,
        ];

        File::ensureDirectoryExists(base_path('deploy'));
        File::put(
            base_path('deploy/manifest.json'),
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        $this->info(sprintf(
            'Manifest dibuat: deploy/manifest.json (%d file, commit %s, branch %s).',
            $manifest['file_count'],
            substr($commit, 0, 8) ?: '-',
            $branch ?: '-'
        ));
        $this->line('Jangan lupa: deploy/manifest.json ini WAJIB ikut di-upload bareng rilis ini ke server.');

        return self::SUCCESS;
    }

    /**
     * @return string[]|null
     */
    private function gitLsFiles(): ?array
    {
        $output = $this->git(['ls-files']);
        if ($output === null) {
            return null;
        }

        return array_values(array_filter(array_map('trim', explode("\n", $output)), fn ($l) => $l !== ''));
    }

    private function git(array $args): ?string
    {
        $process = new Process(array_merge(['git'], $args), base_path());
        $process->run();

        if (!$process->isSuccessful()) {
            return null;
        }

        return $process->getOutput();
    }

    private function isIncluded(string $relative): bool
    {
        foreach (self::INCLUDE_PREFIXES as $prefix) {
            if ($relative === $prefix || str_starts_with($relative, $prefix)) {
                return true;
            }
        }
        return false;
    }
}
