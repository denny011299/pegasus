<?php
/**
 * Export DB lokal (.env) ke SQL dump untuk deploy live.
 *
 * Usage:
 *   php docs/scripts/export_local_db.php
 *   php docs/scripts/export_local_db.php database/sql/pegasus_live_fase2_export_6.sql
 */

require __DIR__ . '/../../vendor/autoload.php';

$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$outFile = $argv[1] ?? __DIR__ . '/../../database/sql/pegasus_live_fase2_export_6.sql';
$outFile = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $outFile);

if (! is_dir(dirname($outFile))) {
    fwrite(STDERR, "Output directory missing: " . dirname($outFile) . PHP_EOL);
    exit(1);
}

$c = config('database.connections.' . config('database.default'));
$mysqldump = 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe';

if (! is_file($mysqldump)) {
    fwrite(STDERR, "mysqldump not found: {$mysqldump}" . PHP_EOL);
    exit(1);
}

putenv('MYSQL_PWD=' . ($c['password'] ?? ''));

$cmd = sprintf(
    '"%s" -h %s -P %s -u %s --single-transaction --routines --triggers --set-gtid-purged=OFF --default-character-set=utf8mb4 --result-file="%s" %s',
    $mysqldump,
    $c['host'],
    $c['port'],
    $c['username'],
    $outFile,
    $c['database']
);

passthru($cmd, $code);

if ($code !== 0 || ! is_file($outFile) || filesize($outFile) < 1024) {
    fwrite(STDERR, "Export failed (exit {$code})." . PHP_EOL);
    exit(1);
}

$sizeMb = round(filesize($outFile) / 1024 / 1024, 2);
echo "OK: {$outFile} ({$sizeMb} MB)" . PHP_EOL;
echo "DB: {$c['database']}" . PHP_EOL;
