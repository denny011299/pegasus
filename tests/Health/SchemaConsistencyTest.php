<?php

namespace Tests\Health;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

/**
 * Guards against model/table drift: a model whose $table (or primary key
 * column) doesn't exist because a migration was never written, was renamed,
 * or the model points at the wrong table. This app has zero real foreign
 * keys, so nothing else catches this class of bug automatically.
 *
 * See cdocs/testing/ for the currently-known offenders this test documents.
 */
class SchemaConsistencyTest extends TestCase
{
    /**
     * Pre-existing model/schema drift, confirmed against a fresh
     * migrate+seed of pegasus_testing on 2026-08-01. Kept as an explicit,
     * reasoned allow-list (skipped, not silently passed) so this test stays
     * green for everything else and only turns red on a *new* regression.
     * Remove an entry here the day its model/migration is actually fixed.
     */
    private const KNOWN_BROKEN = [
        'App\Models\InwardOutward' => 'points at table `inward_outwards`, which has no migration at all.',
        'App\Models\Pengaturan' => 'points at table `pengaturans` (default naming, no explicit $table), which has no migration.',
        'App\Models\PettyCashDetail' => 'declares primary key `id`, but `petty_cash_details` has no such column.',
        'App\Models\PurchaseOrderReceipt' => 'points at table `purchase_order_receipts`, which has no migration (the real receiving table is `purchase_delivery_orders`).',
        'App\Models\ReportLoss' => 'points at table `losses`; the migration created `report_losses` instead — table name mismatch, not a missing migration.',
        'App\Models\ReportProfit' => 'points at table `profits`; the migration created `report_profits` instead — table name mismatch, not a missing migration.',
        'App\Models\Stock' => 'points at table `stocks`, which has no migration at all.',
        'App\Models\StockAlert' => 'declares primary key `stal_id`, but `stock_alerts` has no such column.',
        'App\Models\StockAlertSupplies' => 'shares table `stock_alerts` with StockAlert and the same `stal_id` primary-key mismatch.',
        'App\Models\SuppliesSupplier' => 'points at table `supplies_suppliers` (default naming), which has no migration.',
    ];

    #[DataProvider('modelProvider')]
    public function test_model_table_and_primary_key_exist(string $class): void
    {
        if (isset(self::KNOWN_BROKEN[$class])) {
            $this->markTestSkipped("{$class}: known issue — ".self::KNOWN_BROKEN[$class]);
        }

        /** @var Model $model */
        $model = new $class();
        $table = $model->getTable();

        $this->assertTrue(
            Schema::hasTable($table),
            "{$class} points at table `{$table}`, which does not exist in the schema."
        );

        $primaryKey = $model->getKeyName();
        if (is_string($primaryKey) && Schema::hasTable($table)) {
            $this->assertTrue(
                Schema::hasColumn($table, $primaryKey),
                "{$class} declares primary key `{$primaryKey}` on table `{$table}`, but that column does not exist."
            );
        }
    }

    public static function modelProvider(): array
    {
        $cases = [];

        // Data providers run before Laravel's container/app_path() is available.
        foreach (glob(__DIR__.'/../../app/Models/*.php') as $file) {
            $class = 'App\\Models\\'.basename($file, '.php');

            if (!class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract() || !$reflection->isSubclassOf(Model::class)) {
                continue;
            }

            $cases[$class] = [$class];
        }

        ksort($cases);

        return $cases;
    }
}
