<?php
/**
 * Bangun 1 file SQL upgrade in-place (tanpa import dump ulang).
 *
 * Gabungan:
 *   1. pegasuso_production_multi_warehouse_upgrade.sql
 *   2. pegasuso_production_fase2_schema_gap.sql
 *   3. record migrations warehouse
 *
 * Usage:
 *   php docs/scripts/build_production_upgrade_in_place_sql.php
 */

declare(strict_types=1);

$repoRoot = dirname(__DIR__, 2);
$upgradePath = $repoRoot . '/database/sql/pegasuso_production_multi_warehouse_upgrade.sql';
$gapPath = $repoRoot . '/database/sql/pegasuso_production_fase2_schema_gap.sql';
$outputPath = $repoRoot . '/database/sql/pegasuso_production_upgrade_in_place.sql';

foreach ([$upgradePath, $gapPath] as $path) {
    if (!is_file($path)) {
        fwrite(STDERR, "File tidak ditemukan: {$path}\n");
        exit(1);
    }
}

$migrations = [
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

$migrationUnion = implode("\n  UNION ALL\n  ", array_map(
    fn (string $m) => "SELECT '" . addslashes($m) . "' AS `migration`",
    $migrations
));

$footer = <<<SQL

-- =============================================================================
-- Record migrations warehouse (idempotent)
-- =============================================================================
SET @batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT m.`migration`, @batch
FROM (
  {$migrationUnion}
) m
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` x WHERE x.`migration` = m.`migration`
);

SELECT
  'pegasuso_production_upgrade_in_place OK' AS result,
  @batch AS migration_batch;

SQL;

$header = <<<'SQL'
-- =============================================================================
-- PEGASUS — Upgrade in-place production → fase2 multi-gudang (1 file)
--
-- Jalankan pada DB production yang SUDAH berisi data live (tanpa import dump ulang).
-- Setara dengan: php artisan pegasus:production-upgrade --sql
--
-- ATURAN: TIDAK ADA DELETE / TRUNCATE. Idempotent — aman dijalankan ulang.
--
-- Setelah SQL ini, jalankan role access (hanya bisa via PHP):
--   php artisan db:seed --class=RoleWarehouseAccessSeeder
-- =============================================================================

SQL;

$content = $header
    . "\n-- ----- multi-warehouse upgrade -----\n\n"
    . file_get_contents($upgradePath)
    . "\n\n-- ----- fase2 schema gap -----\n\n"
    . file_get_contents($gapPath)
    . $footer;

file_put_contents($outputPath, $content);

$sizeKb = round(filesize($outputPath) / 1024, 1);
echo "Selesai: {$outputPath} ({$sizeKb} KB)\n";
