<?php

namespace App\Console\Commands;

use App\Support\SnapshotRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Derives a named snapshot from an existing one (default: "default") with the
 * product catalog and everything that only exists to describe or move product
 * stock wiped out, while every other table — roles/staff/users/permissions,
 * customers/suppliers, supplies/purchasing, warehouses, cash ledgers, etc. —
 * is carried over unchanged. Restoring it (`snapshot:restore empty-products`)
 * gives a login-capable environment with a genuinely empty product catalog,
 * so a PMO product sync run has something to actually create instead of
 * matching everything that is already there.
 *
 * Two tables are mixed product+supplies and are filtered row-by-row instead
 * of wiped outright: `log_stocks` (log_type 1=Produk/2=Bahan) and
 * `product_issues` (+ its `product_issues_details` children, tipe_return
 * 1=Bahan/2=Produk) — only the Bahan side survives.
 *
 * Not regenerated automatically: re-run this command after `seed:dump`
 * refreshes the source snapshot with new non-product data you want carried
 * over (new staff, new roles, etc.), then commit the JSON diff same as any
 * other snapshot.
 */
class SnapshotDeriveEmptyProductsCommand extends Command
{
    protected $signature = 'snapshot:derive-empty-products
        {--from=default : Source snapshot name, see: php artisan snapshot:list}
        {--name=empty-products : Name for the derived snapshot}
        {--force : Overwrite the target snapshot directory if it already exists}';

    protected $description = 'Derive a named snapshot with the product catalog (and its dependents) emptied, everything else kept';

    /**
     * Wiped outright: the product catalog itself, plus every table whose
     * rows only make sense in reference to it (order/production/BOM/stock
     * headers+details that carry product_id/product_variant_id, or would be
     * left as empty-shell headers with no line items once their details are
     * gone).
     */
    private const EMPTY_TABLES = [
        // Catalog
        'categories', 'units', 'variants',
        'products', 'product_variants', 'product_stocks', 'product_relations',
        // BOM
        'boms', 'bom_details',
        // Stock opname (product side; stock_opname_bahans is the separate
        // supplies-side table and is left untouched)
        'stock_opnames', 'stock_opname_details',
        // Stock movement/transfer
        'manage_stocks', 'stock_transfers', 'stock_transfer_details', 'stock_alerts',
        // Production
        'productions', 'production_details', 'production_photos',
        // Sales (product-only pipeline in this app; purchasing is supplies-only
        // and is left untouched)
        'sales_orders', 'sales_order_details', 'sales_order_detail_invoices',
        'sales_order_deliveries', 'sales_order_delivery_details',
        'sales_delivery_orders', 'sales_delivery_orders_details',
        'customer_product_returns', 'customer_product_return_details',
        'shipment_shortage_documents',
    ];

    public function handle(): int
    {
        $from = $this->option('from');
        $name = $this->option('name');

        $sourceDir = SnapshotRegistry::path($from);
        if ($sourceDir === null) {
            $this->error("Unknown source snapshot \"{$from}\".");
            $this->call('snapshot:list');
            return self::FAILURE;
        }

        $index = json_decode(File::get($sourceDir.'/_snapshot.json'), true);
        if (! $index) {
            $this->error("Source snapshot \"{$from}\" has no readable _snapshot.json.");
            return self::FAILURE;
        }

        $targetDir = SnapshotRegistry::snapshotsDir().'/'.$name;
        if (File::exists($targetDir)) {
            if (! $this->option('force')) {
                $this->error("Snapshot \"{$name}\" already exists at {$targetDir}. Pass --force to overwrite.");
                return self::FAILURE;
            }
            File::deleteDirectory($targetDir);
        }
        File::ensureDirectoryExists($targetDir);

        $order = $index['order'] ?? [];
        $sourceTables = $index['tables'] ?? [];
        $tablesMeta = [];

        // product_issues is filtered before product_issues_details so the
        // kept pi_ids are known regardless of $order's iteration sequence.
        $keptIssueIds = $this->filterProductIssues($sourceDir, $targetDir, $tablesMeta);

        foreach ($order as $table) {
            if (isset($tablesMeta[$table])) {
                continue; // already handled above
            }

            $path = $sourceDir.'/'.$table.'.json';
            if (! File::exists($path)) {
                continue;
            }

            $rows = json_decode(File::get($path), true) ?: [];

            if (in_array($table, self::EMPTY_TABLES, true)) {
                $rows = [];
            } elseif ($table === 'log_stocks') {
                $rows = array_values(array_filter($rows, fn ($r) => ($r['log_type'] ?? null) === 2));
            } elseif ($table === 'product_issues_details') {
                $rows = array_values(array_filter($rows, fn ($r) => in_array($r['pi_id'] ?? null, $keptIssueIds, true)));
            }

            File::put($targetDir.'/'.$table.'.json', json_encode(
                $rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            )."\n");

            $tablesMeta[$table] = [
                'rows' => count($rows),
                'columns' => $sourceTables[$table]['columns'] ?? [],
                'capped' => $sourceTables[$table]['capped'] ?? null,
            ];

            $this->line(sprintf('  %-42s %6d rows', $table, count($rows)));
        }

        File::put($targetDir.'/_snapshot.json', json_encode([
            'generated_at' => now()->toIso8601String(),
            'database' => $index['database'] ?? null,
            'last_migration' => $index['last_migration'] ?? null,
            'label' => 'Empty product catalog',
            'description' => 'Derived from "'.$from.'" via snapshot:derive-empty-products: product catalog '
                .'(products/product_variants/product_stocks/product_relations/units/variants/categories) and '
                .'its order/production/BOM/stock dependents wiped, everything else (roles, staff, users, '
                .'customers, suppliers, supplies, purchasing, warehouses, cash) kept as-is.',
            'source_snapshot' => $from,
            'order' => $order,
            'tables' => $tablesMeta,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        $this->newLine();
        $this->info("Derived snapshot \"{$name}\" written to {$targetDir}.");
        $this->line('Restore it with: php artisan snapshot:restore '.$name);

        return self::SUCCESS;
    }

    /**
     * Filters product_issues down to tipe_return=1 (Bahan Mentah) rows and
     * writes both product_issues.json and product_issues_details.json to the
     * target (the details file is filtered by the kept pi_ids, since
     * tipe_return only exists on the header).
     *
     * @return array<int, int> kept pi_ids
     */
    private function filterProductIssues(string $sourceDir, string $targetDir, array &$tablesMeta): array
    {
        $issues = json_decode(File::get($sourceDir.'/product_issues.json'), true) ?: [];
        $kept = array_values(array_filter($issues, fn ($r) => ($r['tipe_return'] ?? null) === 1));
        $keptIds = array_column($kept, 'pi_id');

        File::put($targetDir.'/product_issues.json', json_encode(
            $kept, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        )."\n");

        $index = json_decode(File::get($sourceDir.'/_snapshot.json'), true) ?: [];
        $sourceTables = $index['tables'] ?? [];

        $tablesMeta['product_issues'] = [
            'rows' => count($kept),
            'columns' => $sourceTables['product_issues']['columns'] ?? [],
            'capped' => $sourceTables['product_issues']['capped'] ?? null,
        ];

        $this->line(sprintf('  %-42s %6d rows', 'product_issues', count($kept)));

        return $keptIds;
    }
}
