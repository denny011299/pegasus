<?php

/**
 * Clone critical fase-2 files into docs/fase2-snapshots/<short-sha>/.
 *
 * Usage (repo root):
 *   php docs/scripts/snapshot-fase2-files.php
 *   php docs/scripts/snapshot-fase2-files.php --with-zip   # also git archive (large, gitignored)
 */

$root = dirname(__DIR__, 2);
chdir($root);

$short = trim(shell_exec('git rev-parse --short HEAD') ?? '');
$full = trim(shell_exec('git rev-parse HEAD') ?? '');
$branch = trim(shell_exec('git branch --show-current') ?? '');
if ($short === '' || $full === '') {
    fwrite(STDERR, "Unable to read git HEAD\n");
    exit(1);
}

$withZip = in_array('--with-zip', $argv, true);
$dest = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'fase2-snapshots' . DIRECTORY_SEPARATOR . $short;

// Static must-have FE/docs/tests (selalu ikut)
$files = [
    'public/Custom_js/Backoffice/Inventory/Stock_Transfer.js',
    'public/Custom_js/Backoffice/Warehouse/Warehouse.js',
    'public/Custom_js/Backoffice/Warehouse/WarehouseType.js',
    'public/Custom_js/Backoffice/Production/Production.js',
    'public/Custom_js/Backoffice/Customers/Sales_Order.js',
    'public/Custom_js/Backoffice/Customers/Customer_Supply_Return.js',
    'public/Custom_js/Backoffice/Customers/Customer_Product_Return.js',
    'public/Custom_js/Backoffice/Customers/Customer_Return.js',
    'public/Custom_js/Backoffice/Reports/ReportStockTransfer.js',
    'resources/views/Backoffice/Inventory/Stock_Transfer.blade.php',
    'resources/views/Backoffice/Warehouse/Warehouse.blade.php',
    'resources/views/Backoffice/Warehouse/WarehouseType.blade.php',
    'resources/views/Backoffice/Customers/Sales_Order.blade.php',
    'resources/views/layout/partials/pg-modal-styles.blade.php',
    'resources/views/components/modal-popup.blade.php',
    'routes/web.php',
    'tests/Workflow/StockTransferWorkflowTest.php',
    'tests/Unit/ProductUnitStockPackingTest.php',
    'tests/Unit/ProductUnitStockSplitTest.php',
    'docs/backlog-stock-multi-gudang.md',
    'docs/production-acc-stock-safety.md',
    'docs/production-pallet-shortcut.md',
    'docs/fase2-merge-inventory.md',
    '.cursor/rules/modal-structure.mdc',
    '.cursor/rules/modal-footer-actions.mdc',
    '.cursor/rules/fase2-merge-inventory.mdc',
];

// WAJIB: semua Controllers / Models / Support / Console / routes yang beda vs origin/main
// (supaya StockTransferController, ProductionController, dll tidak kelewat)
$diffScopes = [
    'app/Http/Controllers/',
    'app/Models/',
    'app/Support/',
    'app/Console/',
    'routes/',
];
foreach ($diffScopes as $scope) {
    $out = shell_exec('git diff --name-only origin/main...HEAD -- ' . escapeshellarg($scope)) ?? '';
    foreach (preg_split('/\r\n|\r|\n/', trim($out)) as $line) {
        $line = trim($line);
        if ($line !== '') {
            $files[] = str_replace('\\', '/', $line);
        }
    }
}
$files = array_values(array_unique($files));

function ensureDir(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
        throw new RuntimeException("Cannot mkdir: {$path}");
    }
}

function copyFile(string $root, string $destRoot, string $relative, array &$missing, int &$copied): void
{
    $src = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_file($src)) {
        $missing[] = $relative;
        return;
    }
    $dst = $destRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    ensureDir(dirname($dst));
    if (!copy($src, $dst)) {
        throw new RuntimeException("Copy failed: {$relative}");
    }
    $copied++;
}

if (is_dir($dest)) {
    // wipe previous snapshot for this short sha
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dest, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }
}
ensureDir($dest);

$copied = 0;
$missing = [];
foreach ($files as $relative) {
    copyFile($root, $dest, $relative, $missing, $copied);
}

// entire modals tree
$modalsSrc = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views'
    . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'modals';
$modalsDst = $dest . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views'
    . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'modals';
$modalCount = 0;
if (is_dir($modalsSrc)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($modalsSrc, FilesystemIterator::SKIP_DOTS)
    );
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $rel = substr($file->getPathname(), strlen($modalsSrc) + 1);
        $target = $modalsDst . DIRECTORY_SEPARATOR . $rel;
        ensureDir(dirname($target));
        copy($file->getPathname(), $target);
        if (str_ends_with(strtolower($file->getFilename()), '.blade.php')) {
            $modalCount++;
        }
    }
}

// SQL helpers
$sqlDir = $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'sql';
$sqlCopied = 0;
if (is_dir($sqlDir)) {
    foreach (scandir($sqlDir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        if (!preg_match('/warehouse|stock_transfer|retail/i', $name)) {
            continue;
        }
        $src = $sqlDir . DIRECTORY_SEPARATOR . $name;
        if (!is_file($src)) {
            continue;
        }
        $dst = $dest . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . $name;
        ensureDir(dirname($dst));
        copy($src, $dst);
        $sqlCopied++;
    }
}

$ctrlDir = $dest . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers';
$modelDir = $dest . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Models';
$supportDir = $dest . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Support';
$ctrlCount = 0;
$modelCount = 0;
$supportCount = 0;
if (is_dir($ctrlDir)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ctrlDir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->isFile()) {
            $ctrlCount++;
        }
    }
}
if (is_dir($modelDir)) {
    foreach (scandir($modelDir) ?: [] as $n) {
        if (str_ends_with($n, '.php')) {
            $modelCount++;
        }
    }
}
if (is_dir($supportDir)) {
    foreach (scandir($supportDir) ?: [] as $n) {
        if (str_ends_with($n, '.php')) {
            $supportCount++;
        }
    }
}

$manifest = "fase-2 file snapshot\n"
    . "branch: {$branch}\n"
    . "commit: {$full}\n"
    . "short: {$short}\n"
    . "date: " . date('Y-m-d H:i:s') . "\n"
    . "copied_listed_files: {$copied}\n"
    . "controllers: {$ctrlCount}\n"
    . "models: {$modelCount}\n"
    . "support: {$supportCount}\n"
    . "modal_blade_files: {$modalCount}\n"
    . "sql_files: {$sqlCopied}\n"
    . "missing: " . implode(', ', $missing) . "\n"
    . "restore_controller: Copy-Item -Force docs/fase2-snapshots/{$short}/app/Http/Controllers/StockTransferController.php app/Http/Controllers/StockTransferController.php\n"
    . "restore_support: Copy-Item -Force docs/fase2-snapshots/{$short}/app/Support/ProductUnitStock.php app/Support/ProductUnitStock.php\n";

file_put_contents($dest . DIRECTORY_SEPARATOR . 'MANIFEST.txt', $manifest);

echo $manifest;

if ($withZip) {
    $zip = $root . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'fase2-snapshots'
        . DIRECTORY_SEPARATOR . "fase2-{$short}-FULL.zip";
    $cmd = 'git archive --format=zip -o ' . escapeshellarg($zip) . ' HEAD';
    passthru($cmd, $code);
    if ($code !== 0) {
        fwrite(STDERR, "git archive failed\n");
        exit($code);
    }
    echo "full_zip: {$zip}\n";
}

if ($missing) {
    exit(1);
}

echo "Snapshot ready: docs/fase2-snapshots/{$short}/\n";
exit(0);
