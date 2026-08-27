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
        if (! is_dir(base_path('.git'))) {
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
            if (! $this->isIncluded($relative)) {
                continue;
            }
            $full = base_path($relative);
            if (! is_file($full)) {
                // Tracked in git but not present locally (e.g. submodule quirk) -- skip.
                continue;
            }
            $hashes[$relative] = hash_file('sha1', $full);
        }
        ksort($hashes);

        $previous = $this->loadPreviousManifest();
        $diff = $this->diffAgainstPrevious($hashes, $previous['files'] ?? []);

        $manifest = [
            'generated_at' => now()->toIso8601String(),
            'commit' => $commit,
            'branch' => $branch,
            'file_count' => count($hashes),
            'previous_commit' => $previous['commit'] ?? null,
            'previous_generated_at' => $previous['generated_at'] ?? null,
            'added' => $diff['added'],
            'removed' => $diff['removed'],
            'changed' => $diff['changed'],
            'files' => $hashes,
        ];

        File::ensureDirectoryExists(base_path('deploy'));
        File::put(
            base_path('deploy/manifest.json'),
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n"
        );

        $this->info(sprintf(
            'Manifest dibuat: deploy/manifest.json (%d file, commit %s, branch %s).',
            $manifest['file_count'],
            substr($commit, 0, 8) ?: '-',
            $branch ?: '-'
        ));
        $this->line(sprintf(
            'Delta vs manifest sebelumnya: +%d baru, ~%d berubah, -%d dihapus.',
            count($manifest['added']),
            count($manifest['changed']),
            count($manifest['removed'])
        ));
        $this->line('Jangan lupa: deploy/manifest.json ini WAJIB ikut di-upload bareng rilis ini ke server.');

        return self::SUCCESS;
    }

    /**
     * @return array{commit:?string,generated_at:?string,files:array<string,string>}
     */
    private function loadPreviousManifest(): array
    {
        $path = base_path('deploy/manifest.json');
        if (! is_file($path)) {
            return ['commit' => null, 'generated_at' => null, 'files' => []];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            return ['commit' => null, 'generated_at' => null, 'files' => []];
        }

        $files = $decoded['files'] ?? [];
        if (! is_array($files)) {
            $files = [];
        }

        return [
            'commit' => isset($decoded['commit']) ? (string) $decoded['commit'] : null,
            'generated_at' => isset($decoded['generated_at']) ? (string) $decoded['generated_at'] : null,
            'files' => $files,
        ];
    }

    /**
     * @param  array<string,string>  $current
     * @param  array<string,string>  $previous
     * @return array{added:string[],removed:string[],changed:string[]}
     */
    private function diffAgainstPrevious(array $current, array $previous): array
    {
        $added = [];
        $changed = [];
        $removed = [];

        foreach ($current as $path => $hash) {
            if (! array_key_exists($path, $previous)) {
                $added[] = $path;
            } elseif ($previous[$path] !== $hash) {
                $changed[] = $path;
            }
        }

        foreach ($previous as $path => $_hash) {
            if (! array_key_exists($path, $current)) {
                $removed[] = $path;
            }
        }

        sort($added);
        sort($changed);
        sort($removed);

        return [
            'added' => $added,
            'removed' => $removed,
            'changed' => $changed,
        ];
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

        if (! $process->isSuccessful()) {
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
