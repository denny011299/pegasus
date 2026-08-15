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
     * Internal dev-only: tidak ada di sidebar, tidak dibatasi role_access,
     * cukup dilindungi checkLogin seperti /system/changelog.
     */
    public function index()
    {
        $manifestPath = base_path('deploy/manifest.json');

        if (!file_exists($manifestPath)) {
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

            if (!is_file($full)) {
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

        return view('Backoffice.System.DeploymentCheck', [
            'manifestMissing' => false,
            'manifest' => $manifest,
            'missing' => $missing,
            'modified' => $modified,
            'ok' => $ok,
            'total' => count($files),
        ]);
    }
}
