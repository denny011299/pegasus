<?php

namespace App\Http\Controllers;

class DeploymentCheckController extends Controller
{
    /**
     * "Apakah upload manual ke server ini lengkap?" checker.
     *
     * Membandingkan deploy/manifest.json (dibuat lewat `php artisan
     * deploy:manifest` dari repo git sebelum rilis) terhadap file yang
     * benar-benar ada di disk sekarang, lalu melaporkan mana yang hilang
     * atau isinya beda (kemungkinan ke-skip saat upload manual).
     *
     * Juga menampilkan file baru / berubah di rilis ini (delta vs
     * manifest sebelumnya, tersimpan di field added/changed/removed).
     *
     * Internal dev-only: tidak ada di sidebar, tidak dibatasi role_access,
     * cukup dilindungi checkLogin seperti /system/changelog.
     */
    public function index()
    {
        $manifestPath = base_path('deploy/manifest.json');

        if (! file_exists($manifestPath)) {
            return view('Backoffice.System.DeploymentCheck', [
                'manifestMissing' => true,
            ]);
        }

        $manifest = json_decode(file_get_contents($manifestPath), true) ?: [];
        $files = $manifest['files'] ?? [];

        $missing = [];
        $modified = [];
        $ok = 0;

        foreach ($files as $relative => $expectedHash) {
            $full = base_path($relative);

            if (! is_file($full)) {
                $missing[] = $relative;
                continue;
            }

            if (hash_file('sha1', $full) !== $expectedHash) {
                $modified[] = $relative;
                continue;
            }

            $ok++;
        }

        sort($missing);
        sort($modified);

        $added = array_values(array_filter(
            $manifest['added'] ?? [],
            fn ($p) => is_string($p) && $p !== ''
        ));
        $changed = array_values(array_filter(
            $manifest['changed'] ?? [],
            fn ($p) => is_string($p) && $p !== ''
        ));
        $removed = array_values(array_filter(
            $manifest['removed'] ?? [],
            fn ($p) => is_string($p) && $p !== ''
        ));
        sort($added);
        sort($changed);
        sort($removed);

        $missingSet = array_fill_keys($missing, true);
        $modifiedSet = array_fill_keys($modified, true);

        $addedStatus = $this->classifyReleaseFiles($added, $missingSet, $modifiedSet, $files);
        $changedStatus = $this->classifyReleaseFiles($changed, $missingSet, $modifiedSet, $files);

        return view('Backoffice.System.DeploymentCheck', [
            'manifestMissing' => false,
            'manifest' => $manifest,
            'missing' => $missing,
            'modified' => $modified,
            'ok' => $ok,
            'total' => count($files),
            'added' => $added,
            'changedRelease' => $changed,
            'removedRelease' => $removed,
            'addedStatus' => $addedStatus,
            'changedStatus' => $changedStatus,
        ]);
    }

    /**
     * @param  string[]  $paths
     * @param  array<string,bool>  $missingSet
     * @param  array<string,bool>  $modifiedSet
     * @param  array<string,string>  $files
     * @return array{ok:string[],missing:string[],modified:string[],unknown:string[]}
     */
    private function classifyReleaseFiles(array $paths, array $missingSet, array $modifiedSet, array $files): array
    {
        $out = [
            'ok' => [],
            'missing' => [],
            'modified' => [],
            'unknown' => [],
        ];

        foreach ($paths as $path) {
            if (! array_key_exists($path, $files)) {
                $out['unknown'][] = $path;
            } elseif (isset($missingSet[$path])) {
                $out['missing'][] = $path;
            } elseif (isset($modifiedSet[$path])) {
                $out['modified'][] = $path;
            } else {
                $out['ok'][] = $path;
            }
        }

        return $out;
    }
}
