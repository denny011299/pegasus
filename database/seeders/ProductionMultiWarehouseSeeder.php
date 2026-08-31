<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Upgrade DB production (pegasuso) ke multi-gudang Fase 2 tanpa menghapus data.
 *
 * Jalankan setelah import dump production ke DB lokal:
 *   php artisan pegasus:production-upgrade
 *   php artisan db:seed --class=ProductionMultiWarehouseSeeder
 *
 * Opsi SQL (tanpa seeder PHP):
 *   php docs/scripts/build_production_upgrade_in_place_sql.php
 *   php artisan pegasus:production-upgrade --sql
 *   mysql -u root db_name < database/sql/pegasuso_production_upgrade_in_place.sql
 *   php artisan db:seed --class=RoleWarehouseAccessSeeder
 */
class ProductionMultiWarehouseSeeder extends Seeder
{
  private const SQL_FILE = 'database/sql/pegasuso_production_multi_warehouse_upgrade.sql';

  private const SCHEMA_GAP_SQL = 'database/sql/pegasuso_production_fase2_schema_gap.sql';

  private const CONFIG_FILE = 'database/seeders/data/production_default_warehouse.json';

  /** @var list<string> */
  private const WAREHOUSE_MIGRATIONS = [
    '2026_07_22_013000_create_warehouse_types_table',
    '2026_07_22_013100_create_warehouses_table',
    '2026_07_22_120000_make_warehouse_address_nullable',
    '2026_07_22_121000_create_staff_warehouses_table',
    '2026_07_22_122200_add_last_active_warehouse_id_to_staffs_table',
    '2026_07_23_100000_add_is_main_warehouse_to_warehouse_types_table',
    '2026_07_23_190000_add_warehouse_id_to_product_stocks_table',
    '2026_07_24_150000_add_warehouse_id_to_supplies_stocks_table',
    '2026_07_24_170000_add_sidebar_menus_to_warehouses_table',
    '2026_07_27_000100_add_warehouse_id_to_log_stocks_table',
    '2026_07_27_002805_add_retail_warehouse_id_to_sales_orders_table',
    '2026_07_29_140000_add_warehouse_id_to_sales_order_details_table',
    '2026_08_15_154000_add_is_kepala_cabang_to_staff_warehouses_table',
    '2026_08_20_180000_add_warehouse_id_to_stock_opnames_tables',
  ];

  public function run(): void
  {
    $config = $this->loadConfig();
    $before = $this->snapshotCounts();

    // Tanpa DB::transaction(): DDL MySQL (CREATE/ALTER) implicit commit
    // dan akan memicu "There is no active transaction" saat Laravel commit.
    $this->runUpgradeSql();
    $this->runSchemaGapSql();
    $this->ensureWarehousesFromConfig($config);
    $this->backfillWarehouseIds();
    $this->assignStaffToSeedWarehouses($config);
    $this->seedZeroStocksForRetailWarehouse($config);
    $this->recordWarehouseMigrations();

    $this->call(RoleWarehouseAccessSeeder::class);

    $after = $this->snapshotCounts();
    $warehouseId = $this->resolveDefaultWarehouseId($config);
    $retailWarehouseId = $this->resolveRetailWarehouseId($config);

    $this->command?->info('ProductionMultiWarehouseSeeder selesai.');
    $this->command?->info('Gudang utama ID: ' . ($warehouseId ?: '-'));
    $this->command?->info('Gudang eceran ID: ' . ($retailWarehouseId ?: '-'));
    $this->command?->table(
      ['Metrik', 'Sebelum', 'Sesudah'],
      [
        ['product_stocks rows', $before['product_stocks'], $after['product_stocks']],
        ['supplies_stocks rows', $before['supplies_stocks'], $after['supplies_stocks']],
        ['stock_opnames rows', $before['stock_opnames'], $after['stock_opnames']],
        ['log_stocks rows', $before['log_stocks'], $after['log_stocks']],
        ['staffs rows', $before['staffs'], $after['staffs']],
      ]
    );

    if ($warehouseId) {
      $this->command?->info(sprintf(
        'Gudang utama: product_stocks=%d, supplies_stocks=%d, stock_opnames=%d',
        DB::table('product_stocks')->where('warehouse_id', $warehouseId)->count(),
        DB::table('supplies_stocks')->where('warehouse_id', $warehouseId)->count(),
        DB::table('stock_opnames')->where('warehouse_id', $warehouseId)->count()
      ));
    }
    if ($retailWarehouseId) {
      $this->command?->info(sprintf(
        'Gudang eceran: product_stocks=%d, supplies_stocks=%d',
        DB::table('product_stocks')->where('warehouse_id', $retailWarehouseId)->count(),
        DB::table('supplies_stocks')->where('warehouse_id', $retailWarehouseId)->count()
      ));
    }

    $dupPs = $this->duplicateGroupCount('product_stocks', ['warehouse_id', 'product_variant_id', 'unit_id']);
    $dupSs = $this->duplicateGroupCount('supplies_stocks', ['warehouse_id', 'supplies_id', 'unit_id']);
    if ($dupPs > 0 || $dupSs > 0) {
      $this->command?->warn("Duplikat ditemukan (unique index dilewati): product_stocks={$dupPs}, supplies_stocks={$dupSs}");
    }
  }

  private function loadConfig(): array
  {
    $path = base_path(self::CONFIG_FILE);
    if (!is_file($path)) {
      return [];
    }

    $decoded = json_decode(File::get($path), true);

    return is_array($decoded) ? $decoded : [];
  }

  private function runUpgradeSql(): void
  {
    $path = base_path(self::SQL_FILE);
    if (!is_file($path)) {
      throw new \RuntimeException('SQL upgrade tidak ditemukan: ' . self::SQL_FILE);
    }

    DB::unprepared(File::get($path));
  }

  private function runSchemaGapSql(): void
  {
    $path = base_path(self::SCHEMA_GAP_SQL);
    if (!is_file($path)) {
      $this->command?->warn('Schema gap SQL tidak ditemukan: ' . self::SCHEMA_GAP_SQL);

      return;
    }

    DB::unprepared(File::get($path));
  }

  private function ensureWarehousesFromConfig(array $config): void
  {
    if (!Schema::hasTable('warehouse_types') || !Schema::hasTable('warehouses')) {
      return;
    }

    $pairs = [
      [
        'type' => $config['warehouse_type'] ?? [],
        'warehouse' => $config['warehouse'] ?? [],
      ],
      [
        'type' => $config['retail_warehouse_type'] ?? [],
        'warehouse' => $config['retail_warehouse'] ?? [],
      ],
    ];

    foreach ($pairs as $pair) {
      $this->ensureWarehousePair($pair['type'], $pair['warehouse']);
    }
  }

  private function ensureWarehousePair(array $typeConfig, array $warehouseConfig): void
  {
    $typeName = trim((string) ($typeConfig['warehouse_type_name'] ?? ''));
    $whName = trim((string) ($warehouseConfig['warehouse_name'] ?? ''));
    if ($typeName === '' || $whName === '') {
      return;
    }

    $isMain = (int) ($typeConfig['is_main_warehouse'] ?? 0);
    $address = $warehouseConfig['warehouse_address'] ?? null;
    $status = (int) ($warehouseConfig['status'] ?? 1);

    $typeId = DB::table('warehouse_types')
      ->where('warehouse_type_name', $typeName)
      ->where('status', 1)
      ->value('id');

    if (!$typeId) {
      $typeId = DB::table('warehouse_types')->insertGetId([
        'warehouse_type_name' => $typeName,
        'is_main_warehouse' => $isMain,
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    } elseif ($isMain === 1) {
      DB::table('warehouse_types')->where('id', $typeId)->update([
        'is_main_warehouse' => 1,
        'updated_at' => now(),
      ]);
    }

    $warehouseId = DB::table('warehouses')
      ->where('warehouse_name', $whName)
      ->whereIn('status', [1, 2])
      ->value('id');

    if (!$warehouseId) {
      DB::table('warehouses')->insert([
        'warehouse_name' => $whName,
        'warehouse_type_id' => $typeId,
        'warehouse_address' => $address,
        'sidebar_menus' => null,
        'status' => $status,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }
  }

  private function backfillWarehouseIds(): void
  {
    $warehouseId = $this->resolveDefaultWarehouseId($this->loadConfig());
    if (!$warehouseId) {
      return;
    }

    $maps = [
      'product_stocks' => 'ps_id',
      'supplies_stocks' => 'ss_id',
      'stock_opnames' => 'sto_id',
      'stock_opname_bahans' => 'stob_id',
      'log_stocks' => 'log_id',
    ];

    foreach ($maps as $table => $pk) {
      if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'warehouse_id')) {
        continue;
      }

      DB::table($table)
        ->whereNull('warehouse_id')
        ->update([
          'warehouse_id' => $warehouseId,
          'updated_at' => now(),
        ]);
    }
  }

  private function assignStaffToSeedWarehouses(array $config): void
  {
    if (!Schema::hasTable('staff_warehouses') || !Schema::hasTable('staffs')) {
      return;
    }

    $mainWarehouseId = $this->resolveDefaultWarehouseId($config);
    $retailWarehouseId = $this->resolveRetailWarehouseId($config);
    $warehouseIds = array_values(array_filter([$mainWarehouseId, $retailWarehouseId]));

    if ($warehouseIds === []) {
      return;
    }

    $staffIds = DB::table('staffs')->where('status', 1)->pluck('staff_id');

    foreach ($warehouseIds as $warehouseId) {
      foreach ($staffIds as $staffId) {
        $exists = DB::table('staff_warehouses')
          ->where('staff_id', $staffId)
          ->where('warehouse_id', $warehouseId)
          ->exists();

        if (!$exists) {
          DB::table('staff_warehouses')->insert([
            'staff_id' => $staffId,
            'warehouse_id' => $warehouseId,
            'is_kepala_cabang' => 0,
            'created_at' => now(),
            'updated_at' => now(),
          ]);
        }
      }
    }

    if ($mainWarehouseId && Schema::hasColumn('staffs', 'last_active_warehouse_id')) {
      DB::table('staffs')
        ->where('status', 1)
        ->where(function ($q) {
          $q->whereNull('last_active_warehouse_id')->orWhere('last_active_warehouse_id', 0);
        })
        ->update([
          'last_active_warehouse_id' => $mainWarehouseId,
          'updated_at' => now(),
        ]);
    }
  }

  private function seedZeroStocksForRetailWarehouse(array $config): void
  {
    $mainWarehouseId = $this->resolveDefaultWarehouseId($config);
    $retailWarehouseId = $this->resolveRetailWarehouseId($config);

    if (!$mainWarehouseId || !$retailWarehouseId) {
      return;
    }

    if (Schema::hasTable('product_stocks') && Schema::hasColumn('product_stocks', 'warehouse_id')) {
      $mainStocks = DB::table('product_stocks')
        ->where('status', 1)
        ->where('warehouse_id', $mainWarehouseId)
        ->get(['product_variant_id', 'product_id', 'unit_id']);

      foreach ($mainStocks as $row) {
        $exists = DB::table('product_stocks')
          ->where('warehouse_id', $retailWarehouseId)
          ->where('product_variant_id', $row->product_variant_id)
          ->where('unit_id', $row->unit_id)
          ->exists();

        if (!$exists) {
          DB::table('product_stocks')->insert([
            'product_variant_id' => $row->product_variant_id,
            'product_id' => $row->product_id,
            'unit_id' => $row->unit_id,
            'warehouse_id' => $retailWarehouseId,
            'ps_stock' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => null,
          ]);
        }
      }
    }

    if (Schema::hasTable('supplies_stocks') && Schema::hasColumn('supplies_stocks', 'warehouse_id')) {
      $mainStocks = DB::table('supplies_stocks')
        ->where('status', 1)
        ->where('warehouse_id', $mainWarehouseId)
        ->get(['supplies_id', 'unit_id']);

      foreach ($mainStocks as $row) {
        $exists = DB::table('supplies_stocks')
          ->where('warehouse_id', $retailWarehouseId)
          ->where('supplies_id', $row->supplies_id)
          ->where('unit_id', $row->unit_id)
          ->exists();

        if (!$exists) {
          DB::table('supplies_stocks')->insert([
            'supplies_id' => $row->supplies_id,
            'unit_id' => $row->unit_id,
            'warehouse_id' => $retailWarehouseId,
            'ss_stock' => 0,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => null,
          ]);
        }
      }
    }
  }

  private function resolveDefaultWarehouseId(array $config): ?int
  {
    if (!Schema::hasTable('warehouses')) {
      return null;
    }

    $preferredName = (string) ($config['warehouse']['warehouse_name'] ?? 'Gudang Hikari Pegasus Sidoarjo');

    $id = DB::table('warehouses')
      ->where('warehouse_name', $preferredName)
      ->whereIn('status', [1, 2])
      ->value('id');

    if ($id) {
      return (int) $id;
    }

    $mainId = DB::table('warehouses as w')
      ->join('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
      ->where('w.status', 1)
      ->where('wt.status', 1)
      ->where('wt.is_main_warehouse', 1)
      ->orderBy('w.id')
      ->value('w.id');

    if ($mainId) {
      return (int) $mainId;
    }

    $fallback = DB::table('warehouses')->where('status', 1)->orderBy('id')->value('id');

    return $fallback ? (int) $fallback : null;
  }

  private function resolveRetailWarehouseId(array $config): ?int
  {
    if (!Schema::hasTable('warehouses')) {
      return null;
    }

    $preferredName = (string) ($config['retail_warehouse']['warehouse_name'] ?? 'Gudang Eceran Sidoarjo');

    $id = DB::table('warehouses')
      ->where('warehouse_name', $preferredName)
      ->whereIn('status', [1, 2])
      ->value('id');

    return $id ? (int) $id : null;
  }

  private function recordWarehouseMigrations(): void
  {
    if (!Schema::hasTable('migrations')) {
      return;
    }

    $batch = (int) DB::table('migrations')->max('batch') + 1;

    foreach (self::WAREHOUSE_MIGRATIONS as $migration) {
      $exists = DB::table('migrations')->where('migration', $migration)->exists();
      if (!$exists) {
        DB::table('migrations')->insert([
          'migration' => $migration,
          'batch' => $batch,
        ]);
      }
    }
  }

  /** @return array<string, int> */
  private function snapshotCounts(): array
  {
    return [
      'product_stocks' => Schema::hasTable('product_stocks') ? (int) DB::table('product_stocks')->count() : 0,
      'supplies_stocks' => Schema::hasTable('supplies_stocks') ? (int) DB::table('supplies_stocks')->count() : 0,
      'stock_opnames' => Schema::hasTable('stock_opnames') ? (int) DB::table('stock_opnames')->count() : 0,
      'log_stocks' => Schema::hasTable('log_stocks') ? (int) DB::table('log_stocks')->count() : 0,
      'staffs' => Schema::hasTable('staffs') ? (int) DB::table('staffs')->count() : 0,
    ];
  }

  /** @param list<string> $columns */
  private function duplicateGroupCount(string $table, array $columns): int
  {
    if (!Schema::hasTable($table)) {
      return 0;
    }

    foreach ($columns as $col) {
      if (!Schema::hasColumn($table, $col)) {
        return 0;
      }
    }

    $groupCols = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

    $row = DB::selectOne("
      SELECT COUNT(*) AS c FROM (
        SELECT {$groupCols}, COUNT(*) AS cnt
        FROM `{$table}`
        WHERE `warehouse_id` IS NOT NULL
        GROUP BY {$groupCols}
        HAVING cnt > 1
      ) d
    ");

    return (int) ($row->c ?? 0);
  }
}
