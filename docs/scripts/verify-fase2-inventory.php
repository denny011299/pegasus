<?php

/**
 * Verify fase-2 critical inventory is still present (anti-lost-on-merge).
 *
 * Usage (from repo root):
 *   php docs/scripts/verify-fase2-inventory.php
 *
 * Exit 0 = OK, 1 = missing paths/markers.
 * See docs/fase2-merge-inventory.md
 */

$root = dirname(__DIR__, 2);
$fail = [];
$ok = 0;

function rel(string $root, string $path): string
{
    return str_replace('\\', '/', substr($path, strlen($root) + 1));
}

function mustExist(string $root, string $relative, array &$fail, int &$ok): void
{
    $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!file_exists($full)) {
        $fail[] = "MISSING FILE: {$relative}";
        return;
    }
    $ok++;
}

function mustContain(string $root, string $relative, string $needle, array &$fail, int &$ok): void
{
    $full = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!file_exists($full)) {
        $fail[] = "MISSING FILE (marker check skipped): {$relative}";
        return;
    }
    $content = file_get_contents($full);
    if ($content === false || strpos($content, $needle) === false) {
        $fail[] = "MISSING MARKER in {$relative}: {$needle}";
        return;
    }
    $ok++;
}

$requiredFiles = [
    // Core BE â€” controllers (semua yang fase-2 sentuh vs main)
    'app/Support/ProductUnitStock.php',
    'app/Support/RetailStockCleanup.php',
    'app/Support/SalesOrderStock.php',
    'app/Http/Controllers/StockTransferController.php',
    'app/Http/Controllers/WarehouseController.php',
    'app/Http/Controllers/CustomerProductReturnController.php',
    'app/Http/Controllers/CustomerSupplyReturnController.php',
    'app/Http/Controllers/ProductionController.php',
    'app/Http/Controllers/StockController.php',
    'app/Http/Controllers/ProductController.php',
    'app/Http/Controllers/CustomerController.php',
    'app/Http/Controllers/AutocompleteController.php',
    'app/Http/Controllers/ReportController.php',
    'app/Http/Controllers/UserController.php',
    'app/Http/Controllers/SettingController.php',
    'app/Http/Controllers/ExternalApi/V1/MasterDataController.php',
    'app/Models/Warehouse.php',
    'app/Models/WarehouseType.php',
    'app/Models/StockTransfer.php',
    'app/Models/StockTransferDetail.php',
    'app/Models/StaffWarehouse.php',
    'app/Models/CustomerProductReturn.php',
    'app/Models/CustomerProductReturnDetail.php',
    'app/Models/CustomerSupplyReturn.php',
    'app/Models/CustomerSupplyReturnDetail.php',
    'app/Models/LogStock.php',
    'app/Models/ProductVariant.php',
    'app/Models/Production.php',
    // FE
    'public/Custom_js/Backoffice/Inventory/Stock_Transfer.js',
    'public/Custom_js/Backoffice/Warehouse/Warehouse.js',
    'public/Custom_js/Backoffice/Warehouse/WarehouseType.js',
    'public/Custom_js/Backoffice/Production/Production.js',
    'public/Custom_js/Backoffice/Customers/Sales_Order.js',
    'public/Custom_js/Backoffice/Customers/Customer_Supply_Return.js',
    'public/Custom_js/Backoffice/Customers/Customer_Product_Return.js',
    'public/Custom_js/Backoffice/Reports/ReportStockTransfer.js',
    'resources/views/Backoffice/Inventory/Stock_Transfer.blade.php',
    'resources/views/Backoffice/Warehouse/Warehouse.blade.php',
    'resources/views/Backoffice/Warehouse/WarehouseType.blade.php',
    'resources/views/layout/partials/pg-modal-styles.blade.php',
    'resources/views/components/modal-popup.blade.php',
    // ST modals
    'resources/views/components/modals/stock-transfer/add-stock-transfer.blade.php',
    'resources/views/components/modals/stock-transfer/accept-stock-transfer.blade.php',
    'resources/views/components/modals/stock-transfer/view-stock-transfer.blade.php',
    'resources/views/components/modals/stock-transfer/reject-production-transfer.blade.php',
    // Themed modals
    'resources/views/components/modals/production/add-production.blade.php',
    'resources/views/components/modals/sales-order/add-sales-order.blade.php',
    'resources/views/components/modals/warehouse/add-warehouse.blade.php',
    'resources/views/components/modals/warehouse-type/add-warehouse-type.blade.php',
    'resources/views/components/modals/shared/modal-konfirmasi.blade.php',
    'resources/views/components/modals/shared/modal-photo.blade.php',
    'resources/views/components/modals/shared/modal-delete.blade.php',
    'resources/views/components/modals/shared/modal-danger.blade.php',
    // Docs + rules
    'docs/fase2-merge-inventory.md',
    'docs/backlog-stock-multi-gudang.md',
    'docs/production-acc-stock-safety.md',
    'docs/production-pallet-shortcut.md',
    '.cursor/rules/modal-structure.mdc',
    '.cursor/rules/modal-footer-actions.mdc',
    // Tests
    'tests/Workflow/StockTransferWorkflowTest.php',
    'tests/Unit/ProductUnitStockPackingTest.php',
    'tests/Unit/ProductUnitStockSplitTest.php',
];

foreach ($requiredFiles as $file) {
    mustExist($root, $file, $fail, $ok);
}

$markers = [
    ['app/Http/Controllers/StockTransferController.php', 'function shipStockTransfer'],
    ['app/Http/Controllers/StockTransferController.php', 'function accStockTransfer'],
    ['app/Http/Controllers/StockTransferController.php', 'function restoreSourceStock'],
    ['app/Http/Controllers/StockTransferController.php', 'function resolveTransferUnits'],
    ['app/Http/Controllers/StockTransferController.php', 'getTransferRetailUnitSetup'],
    ['app/Http/Controllers/StockTransferController.php', 'saveTransferRetailUnit'],
    ['app/Support/ProductUnitStock.php', 'function warehouseIsMain'],
    ['app/Support/ProductUnitStock.php', 'function deductPackedQty'],
    ['app/Support/RetailStockCleanup.php', 'class RetailStockCleanup'],
    ['public/Custom_js/Backoffice/Inventory/Stock_Transfer.js', 'promptRetailUnitForTransfer'],
    ['public/Custom_js/Backoffice/Inventory/Stock_Transfer.js', 'saveRetailUnitForTransfer'],
    ['public/Custom_js/Backoffice/Inventory/Stock_Transfer.js', 'setTransferModalLoading'],
    ['public/Custom_js/Backoffice/Inventory/Stock_Transfer.js', 'setTransferModalMode'],
    ['public/Custom_js/Backoffice/Inventory/Stock_Transfer.js', 'loadTransferDetailForEdit'],
    ['public/Custom_js/Backoffice/Production/Production.js', 'setProductionModalMode'],
    ['public/Custom_js/Backoffice/Production/Production.js', 'fe-check-circle'],
    ['resources/views/layout/partials/pg-modal-styles.blade.php', '.pg-btn-accept'],
    ['resources/views/layout/partials/pg-modal-styles.blade.php', '.pg-btn-decline'],
    ['resources/views/layout/partials/pg-modal-styles.blade.php', '.pg-modal--confirm'],
    ['resources/views/layout/partials/pg-modal-styles.blade.php', 'f87171'],
    ['resources/views/components/modal-popup.blade.php', "components.modals.stock-transfer.add-stock-transfer"],
    ['resources/views/components/modals/stock-transfer/add-stock-transfer.blade.php', 'pg-modal-loading'],
    ['resources/views/components/modals/stock-transfer/accept-stock-transfer.blade.php', 'fe-check-circle'],
    ['routes/web.php', 'getTransferRetailUnitSetup'],
    ['routes/web.php', 'saveTransferRetailUnit'],
    ['routes/web.php', 'setActiveWarehouse'],
    ['routes/web.php', 'customerProductReturns'],
    ['routes/web.php', 'customerSupplyReturns'],
    ['tests/Workflow/StockTransferWorkflowTest.php', 'test_ship_refuses_to_unpack_dos_when_sent_unit_stock_is_short'],
    ['tests/Workflow/StockTransferWorkflowTest.php', 'test_receiving_into_retail_converts_to_retail_unit'],
    ['tests/Workflow/StockTransferWorkflowTest.php', 'test_receiving_into_a_main_warehouse_keeps_sent_unit_without_conversion'],
];

foreach ($markers as [$file, $needle]) {
    mustContain($root, $file, $needle, $fail, $ok);
}

// Modal folder count
$modalsDir = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views'
    . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'modals';
$modalCount = 0;
if (is_dir($modalsDir)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($modalsDir));
    foreach ($it as $file) {
        if ($file->isFile() && str_ends_with(strtolower($file->getFilename()), '.blade.php')) {
            $modalCount++;
        }
    }
}
if ($modalCount < 45) {
    $fail[] = "MODAL COUNT TOO LOW: found {$modalCount} under resources/views/components/modals/ (expect >= 45)";
} else {
    $ok++;
}

// modal-popup must stay slim (include-only)
$popup = $root . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views'
    . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'modal-popup.blade.php';
if (file_exists($popup)) {
    $lines = count(file($popup));
    if ($lines > 800) {
        $fail[] = "modal-popup.blade.php looks like monolith again ({$lines} lines; expect ~200 include-only)";
    } else {
        $ok++;
    }
}

echo "fase-2 inventory check\n";
echo "OK checks: {$ok}\n";
echo "Modal blade files: {$modalCount}\n";

if ($fail) {
    echo "\nFAIL (" . count($fail) . "):\n";
    foreach ($fail as $msg) {
        echo " - {$msg}\n";
    }
    echo "\nSee docs/fase2-merge-inventory.md\n";
    exit(1);
}

echo "\nAll critical fase-2 inventory markers present.\n";
exit(0);
