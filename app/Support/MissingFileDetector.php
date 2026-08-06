<?php

namespace App\Support;

use Throwable;

/**
 * Best-effort detector for "this crashed because a file wasn't uploaded"
 * shaped exceptions -- the kind of thing a manual (non-git) deployment can
 * produce when someone forgets to copy a controller, a blade view, or a
 * class file to the server.
 *
 * Used by the render() hook in bootstrap/app.php to show a friendlier,
 * more actionable error page instead of a bare "Server Error" -- see
 * resources/views/errors/deploy-issue.blade.php.
 *
 * This is a best-effort heuristic based on matching known PHP/Laravel
 * "not found" message shapes, not a guarantee -- an exception that doesn't
 * match any pattern here just falls through to Laravel's normal handling.
 */
class MissingFileDetector
{
    /**
     * @return array{category: string, identifier: string, expected_path: ?string, explanation: string}|null
     */
    public static function detect(Throwable $e): ?array
    {
        $message = $e->getMessage();

        if (preg_match('/^View \[(.+)\] not found\.?$/', $message, $m)) {
            return self::forMissingView($m[1]);
        }

        if (preg_match('/Class "([^"]+)" not found/', $message, $m)) {
            return self::forMissingClass($m[1]);
        }

        if (preg_match('/(?:require|include)(?:_once)?\(([^)]+)\):\s*Failed to open stream/i', $message, $m)) {
            return self::forMissingInclude(trim($m[1], " '\""));
        }

        if (preg_match('/Call to undefined method ([^:]+)::(\w+)\(\)/', $message, $m)) {
            return self::forUndefinedMethod($m[1], $m[2]);
        }

        return null;
    }

    private static function forMissingView(string $viewName): array
    {
        $relative = 'resources/views/' . str_replace('.', '/', $viewName) . '.blade.php';

        return [
            'category' => 'view_not_found',
            'identifier' => $viewName,
            'expected_path' => $relative,
            'explanation' => sprintf(
                'Halaman mencoba menampilkan view "%s", tapi file-nya tidak ditemukan. Kemungkinan besar file %s belum ter-upload ke server.',
                $viewName,
                $relative
            ),
        ];
    }

    private static function forMissingClass(string $className): array
    {
        $relative = self::classToPath($className);

        if ($relative === null) {
            return [
                'category' => 'class_not_found',
                'identifier' => $className,
                'expected_path' => null,
                'explanation' => sprintf(
                    'Class "%s" tidak ditemukan. Class ini berasal dari dependency pihak ketiga (vendor/) -- kemungkinan "composer install" belum dijalankan / folder vendor belum lengkap di server ini, bukan file milik aplikasi ini yang hilang.',
                    $className
                ),
            ];
        }

        return [
            'category' => 'class_not_found',
            'identifier' => $className,
            'expected_path' => $relative,
            'explanation' => sprintf(
                'Class "%s" tidak ditemukan. Kemungkinan besar file %s belum ter-upload ke server.',
                $className,
                $relative
            ),
        ];
    }

    private static function forMissingInclude(string $path): array
    {
        $relative = str_starts_with($path, base_path())
            ? ltrim(substr($path, strlen(base_path())), '/')
            : $path;

        return [
            'category' => 'include_missing',
            'identifier' => $path,
            'expected_path' => $relative,
            'explanation' => sprintf(
                'Kode mencoba memuat file %s tapi tidak ditemukan. Kemungkinan besar file ini belum ter-upload ke server.',
                $relative
            ),
        ];
    }

    private static function forUndefinedMethod(string $className, string $method): array
    {
        return [
            'category' => 'method_not_found',
            'identifier' => $className . '::' . $method . '()',
            'expected_path' => self::classToPath($className),
            'explanation' => sprintf(
                'Method "%s()" tidak ditemukan di class "%s". Class-nya ada, tapi kemungkinan versinya lebih lama dari yang dipanggil -- indikasi upload rilis ini belum lengkap/menimpa semua file yang seharusnya berubah.',
                $method,
                $className
            ),
        ];
    }

    /**
     * Map a fully-qualified class name to its expected file path using this
     * app's composer.json psr-4 map. Returns null for a namespace we don't
     * recognize (e.g. a vendor package) -- not actionable as "upload this file".
     */
    private static function classToPath(string $className): ?string
    {
        $className = ltrim($className, '\\');
        $map = [
            'App\\' => 'app/',
            'Database\\Factories\\' => 'database/factories/',
            'Database\\Seeders\\' => 'database/seeders/',
        ];

        foreach ($map as $prefix => $baseDir) {
            if (str_starts_with($className, $prefix)) {
                $rest = substr($className, strlen($prefix));
                return $baseDir . str_replace('\\', '/', $rest) . '.php';
            }
        }

        return null;
    }
}
