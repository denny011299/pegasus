<?php
/**
 * Bangun 1 file SQL import dari dump production + warehouse_id di data real.
 *
 * Usage (dari root repo):
 *   php docs/scripts/build_production_warehouse_sql.php
 *   php docs/scripts/build_production_warehouse_sql.php "C:\path\to\dump.sql" "database/sql/out.sql"
 *
 * Output: database/sql/pegasuso_production_import_with_warehouses.sql
 */

declare(strict_types=1);

$repoRoot = dirname(__DIR__, 2);
$defaultInput = 'C:\\Users\\Ruben\\Downloads\\pegasuso_pegasus_production 31-08-26.sql';
$defaultOutput = $repoRoot . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'pegasuso_production_import_with_warehouses.sql';

$inputPath = $argv[1] ?? $defaultInput;
$outputPath = $argv[2] ?? $defaultOutput;

if (!is_file($inputPath)) {
    fwrite(STDERR, "Input tidak ditemukan: {$inputPath}\n");
    exit(1);
}

/** ID tetap — semua backfill pakai warehouse_id = 1 (Gudang Hikari Pegasus Sidoarjo) */
const MAIN_WAREHOUSE_ID = 1;
const RETAIL_WAREHOUSE_ID = 2;

/**
 * @var array<string, array{
 *   create_anchor: string,
 *   create_column: string,
 *   insert_anchor: string,
 *   insert_column: string,
 *   value_after_field: int
 * }>
 */
const TABLE_RULES = [
    'product_stocks' => [
        'create_anchor' => '`unit_id`',
        'create_column' => "  `warehouse_id` bigint(20) UNSIGNED NULL DEFAULT NULL,",
        'insert_anchor' => '`unit_id`',
        'insert_column' => '`warehouse_id`',
        'value_after_field' => 4,
    ],
    'supplies_stocks' => [
        'create_anchor' => '`unit_id`',
        'create_column' => "  `warehouse_id` bigint(20) UNSIGNED NULL DEFAULT NULL,",
        'insert_anchor' => '`unit_id`',
        'insert_column' => '`warehouse_id`',
        'value_after_field' => 3,
    ],
    'stock_opnames' => [
        'create_anchor' => '`category_id`',
        'create_column' => "  `warehouse_id` bigint(20) UNSIGNED NULL DEFAULT NULL,",
        'insert_anchor' => '`category_id`',
        'insert_column' => '`warehouse_id`',
        'value_after_field' => 6,
    ],
    'stock_opname_bahans' => [
        'create_anchor' => '`staff_id`',
        'create_column' => "  `warehouse_id` bigint(20) UNSIGNED NULL DEFAULT NULL,",
        'insert_anchor' => '`staff_id`',
        'insert_column' => '`warehouse_id`',
        'value_after_field' => 4,
    ],
    'log_stocks' => [
        'create_anchor' => '`staff_id`',
        'create_column' => "  `warehouse_id` bigint(20) UNSIGNED NULL DEFAULT NULL,",
        'insert_anchor' => '`staff_id`',
        'insert_column' => '`warehouse_id`',
        'value_after_field' => 11,
    ],
    'staffs' => [
        'create_anchor' => '`role_id`',
        'create_column' => "  `last_active_warehouse_id` bigint(20) UNSIGNED NULL DEFAULT " . MAIN_WAREHOUSE_ID . ',',
        'insert_anchor' => '`role_id`',
        'insert_column' => '`last_active_warehouse_id`',
        'value_after_field' => 11,
    ],
];

function preamble(): string
{
    $main = MAIN_WAREHOUSE_ID;
    $retail = RETAIL_WAREHOUSE_ID;

    return <<<SQL
-- =============================================================================
-- PEGASUS — Preamble multi-gudang (jalankan sebelum data production)
-- Gudang ID {$main} = Gudang Hikari Pegasus Sidoarjo (utama)
-- Gudang ID {$retail} = Gudang Eceran Sidoarjo
-- =============================================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

CREATE TABLE IF NOT EXISTS `warehouse_types` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_type_name` varchar(250) NOT NULL,
  `is_main_warehouse` tinyint(4) NOT NULL DEFAULT 0,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `warehouses` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `warehouse_name` varchar(250) NOT NULL,
  `warehouse_type_id` bigint(20) UNSIGNED NOT NULL,
  `warehouse_address` text DEFAULT NULL,
  `sidebar_menus` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sidebar_menus`)),
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `staff_warehouses` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `is_kepala_cabang` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_warehouses_staff_id_warehouse_id_unique` (`staff_id`,`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `warehouse_types` (`id`, `warehouse_type_name`, `is_main_warehouse`, `status`, `created_at`, `updated_at`) VALUES
({$main}, 'Gudang Besar', 1, 1, NOW(), NOW()),
({$retail}, 'Gudang Eceran', 0, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `warehouse_type_name` = VALUES(`warehouse_type_name`),
  `is_main_warehouse` = VALUES(`is_main_warehouse`),
  `status` = VALUES(`status`),
  `updated_at` = NOW();

INSERT INTO `warehouses` (`id`, `warehouse_name`, `warehouse_type_id`, `warehouse_address`, `sidebar_menus`, `status`, `created_at`, `updated_at`) VALUES
({$main}, 'Gudang Hikari Pegasus Sidoarjo', {$main}, 'Pergudangan Meiko Abadi 2, Blok A8 - 9, Jalan Industri, Buduran, Sidoarjo', NULL, 1, NOW(), NOW()),
({$retail}, 'Gudang Eceran Sidoarjo', {$retail}, 'Pergudangan Meiko Abadi 2, Blok A9, Jalan Industri, Buduran, Sidoarjo', NULL, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `warehouse_name` = VALUES(`warehouse_name`),
  `warehouse_type_id` = VALUES(`warehouse_type_id`),
  `warehouse_address` = VALUES(`warehouse_address`),
  `status` = VALUES(`status`),
  `updated_at` = NOW();

CREATE TABLE IF NOT EXISTS `stock_transfers` (
  `st_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `transfer_code` varchar(30) NOT NULL,
  `transfer_date` date NOT NULL,
  `sender_id` int(10) UNSIGNED NOT NULL,
  `receiver_id` int(10) UNSIGNED DEFAULT NULL,
  `from_warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `to_warehouse_id` bigint(20) UNSIGNED NOT NULL,
  `note` longtext DEFAULT NULL,
  `accept_note` longtext DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `acc_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`st_id`),
  UNIQUE KEY `stock_transfers_transfer_code_unique` (`transfer_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_transfer_details` (
  `std_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `st_id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `product_variant_id` int(10) UNSIGNED NOT NULL,
  `unit_id` int(10) UNSIGNED NOT NULL,
  `qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `qty_received` decimal(18,4) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`std_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----- Data production (dump asli + warehouse_id di stok/opname) -----

SQL;
}

function postfix(): string
{
    $main = MAIN_WAREHOUSE_ID;
    $retail = RETAIL_WAREHOUSE_ID;

    return <<<SQL

-- =============================================================================
-- Postfix: staff ↔ gudang + stok 0 gudang eceran (tanpa hapus data)
-- =============================================================================

INSERT INTO `staff_warehouses` (`staff_id`, `warehouse_id`, `is_kepala_cabang`, `created_at`, `updated_at`)
SELECT s.`staff_id`, {$main}, 0, NOW(), NOW()
FROM `staffs` s
WHERE s.`status` = 1
  AND NOT EXISTS (
    SELECT 1 FROM `staff_warehouses` sw
    WHERE sw.`staff_id` = s.`staff_id` AND sw.`warehouse_id` = {$main}
  );

INSERT INTO `staff_warehouses` (`staff_id`, `warehouse_id`, `is_kepala_cabang`, `created_at`, `updated_at`)
SELECT s.`staff_id`, {$retail}, 0, NOW(), NOW()
FROM `staffs` s
WHERE s.`status` = 1
  AND NOT EXISTS (
    SELECT 1 FROM `staff_warehouses` sw
    WHERE sw.`staff_id` = s.`staff_id` AND sw.`warehouse_id` = {$retail}
  );

UPDATE `staffs`
SET `last_active_warehouse_id` = {$main}, `updated_at` = NOW()
WHERE `status` = 1
  AND (`last_active_warehouse_id` IS NULL OR `last_active_warehouse_id` = 0);

INSERT INTO `product_stocks` (
  `product_variant_id`, `product_id`, `unit_id`, `warehouse_id`,
  `ps_stock`, `status`, `created_at`, `updated_at`, `created_by`
)
SELECT
  ps.`product_variant_id`, ps.`product_id`, ps.`unit_id`, {$retail},
  0, 1, NOW(), NOW(), ps.`created_by`
FROM `product_stocks` ps
WHERE ps.`warehouse_id` = {$main}
  AND ps.`status` = 1
  AND NOT EXISTS (
    SELECT 1 FROM `product_stocks` x
    WHERE x.`warehouse_id` = {$retail}
      AND x.`product_variant_id` = ps.`product_variant_id`
      AND x.`unit_id` = ps.`unit_id`
  );

INSERT INTO `supplies_stocks` (
  `supplies_id`, `unit_id`, `warehouse_id`,
  `ss_stock`, `status`, `created_at`, `updated_at`, `created_by`
)
SELECT
  ss.`supplies_id`, ss.`unit_id`, {$retail},
  0, 1, NOW(), NOW(), ss.`created_by`
FROM `supplies_stocks` ss
WHERE ss.`warehouse_id` = {$main}
  AND ss.`status` = 1
  AND NOT EXISTS (
    SELECT 1 FROM `supplies_stocks` x
    WHERE x.`warehouse_id` = {$retail}
      AND x.`supplies_id` = ss.`supplies_id`
      AND x.`unit_id` = ss.`unit_id`
  );

SET FOREIGN_KEY_CHECKS = 1;

SELECT
  {$main} AS main_warehouse_id,
  (SELECT COUNT(*) FROM `product_stocks` WHERE `warehouse_id` = {$main}) AS product_stocks_main,
  (SELECT COUNT(*) FROM `product_stocks` WHERE `warehouse_id` = {$retail}) AS product_stocks_retail,
  (SELECT COUNT(*) FROM `supplies_stocks` WHERE `warehouse_id` = {$main}) AS supplies_stocks_main,
  (SELECT COUNT(*) FROM `stock_opnames` WHERE `warehouse_id` = {$main}) AS stock_opnames_main,
  'pegasuso_production_import_with_warehouses OK' AS result;

SQL;
}

/**
 * @return list<string>
 */
function splitSqlRowFields(string $row): array
{
    $row = trim($row);
    if (str_ends_with($row, ',')) {
        $row = substr($row, 0, -1);
    }
    if (str_ends_with($row, ';')) {
        $row = substr($row, 0, -1);
    }
    if (!str_starts_with($row, '(')) {
        return [];
    }
    $row = substr($row, 1);
    if (str_ends_with($row, ')')) {
        $row = substr($row, 0, -1);
    }

    $fields = [];
    $buf = '';
    $inString = false;
    $len = strlen($row);

    for ($i = 0; $i < $len; $i++) {
        $c = $row[$i];

        if ($inString) {
            $buf .= $c;
            if ($c === "'") {
                if ($i + 1 < $len && $row[$i + 1] === "'") {
                    $buf .= $row[++$i];
                    continue;
                }
                $inString = false;
            }
            continue;
        }

        if ($c === "'") {
            $inString = true;
            $buf .= $c;
            continue;
        }

        if ($c === ',') {
            $fields[] = trim($buf);
            $buf = '';
            continue;
        }

        $buf .= $c;
    }

    if ($buf !== '') {
        $fields[] = trim($buf);
    }

    return $fields;
}

function injectWarehouseIntoRow(string $line, int $afterFieldCount, int $warehouseId): string
{
    $suffix = '';
    $trimmed = rtrim($line);
    if (str_ends_with($trimmed, ',')) {
        $suffix = ',';
        $trimmed = rtrim(substr($trimmed, 0, -1));
    } elseif (str_ends_with($trimmed, ';')) {
        $suffix = ';';
        $trimmed = rtrim(substr($trimmed, 0, -1));
    }

    $fields = splitSqlRowFields($trimmed);
    if ($fields === [] || count($fields) < $afterFieldCount) {
        return $line;
    }

    array_splice($fields, $afterFieldCount, 0, [(string) $warehouseId]);
    $rebuilt = '(' . implode(', ', $fields) . ')' . $suffix;

    return $rebuilt . (str_ends_with($line, "\n") ? "\n" : '');
}

function injectColumnIntoInsert(string $line, string $anchorColumn, string $newColumn): string
{
    $pattern = '/(`' . preg_quote(trim($anchorColumn, '`'), '/') . '`)(\s*,)/';
    if (preg_match($pattern, $line) !== 1) {
        return $line;
    }

    return (string) preg_replace($pattern, '$1, `' . trim($newColumn, '`') . '`$2', $line, 1);
}

function injectCreateColumn(string $line, string $anchor, string $columnLine): string
{
    if (!str_contains($line, $anchor)) {
        return $line;
    }

    return rtrim($line, "\r\n") . "\n" . rtrim($columnLine, "\r\n") . "\n";
}

// --- main ---
$in = fopen($inputPath, 'rb');
if ($in === false) {
    fwrite(STDERR, "Gagal buka input.\n");
    exit(1);
}

$outDir = dirname($outputPath);
if (!is_dir($outDir) && !mkdir($outDir, 0777, true) && !is_dir($outDir)) {
    fwrite(STDERR, "Gagal buat folder output.\n");
    exit(1);
}

$out = fopen($outputPath, 'wb');
if ($out === false) {
    fwrite(STDERR, "Gagal buka output.\n");
    exit(1);
}

fwrite($out, preamble());

$currentTable = null;
$inCreateTable = false;
$createDoneForTable = false;
$activeInsertTable = null;
$lineNo = 0;
$stats = [
    'insert_lines' => 0,
    'create_patched' => 0,
];

while (($line = fgets($in)) !== false) {
    $lineNo++;

    if (preg_match('/^CREATE TABLE `([^`]+)`/i', $line, $m) === 1) {
        $currentTable = $m[1];
        $inCreateTable = true;
        $createDoneForTable = false;
        $activeInsertTable = null;
    }

    if ($inCreateTable && $currentTable !== null && isset(TABLE_RULES[$currentTable]) && !$createDoneForTable) {
        $rule = TABLE_RULES[$currentTable];
        if (str_contains($line, $rule['create_anchor'])) {
            $line = injectCreateColumn($line, $rule['create_anchor'], $rule['create_column']);
            $createDoneForTable = true;
            $stats['create_patched']++;
        }
    }

    if ($inCreateTable && str_starts_with(trim($line), ') ENGINE=')) {
        $inCreateTable = false;
    }

    if (preg_match('/^INSERT INTO `([^`]+)`/i', $line, $m) === 1) {
        $activeInsertTable = $m[1];
        if (isset(TABLE_RULES[$activeInsertTable])) {
            $rule = TABLE_RULES[$activeInsertTable];
            $line = injectColumnIntoInsert($line, $rule['insert_anchor'], $rule['insert_column']);
        }
    } elseif ($activeInsertTable !== null && isset(TABLE_RULES[$activeInsertTable])) {
        $trim = ltrim($line);
        if (str_starts_with($trim, '(')) {
            $rule = TABLE_RULES[$activeInsertTable];
            $warehouseValue = $activeInsertTable === 'staffs'
                ? (string) MAIN_WAREHOUSE_ID
                : (string) MAIN_WAREHOUSE_ID;
            $line = injectWarehouseIntoRow($line, $rule['value_after_field'], (int) $warehouseValue);
            $stats['insert_lines']++;
        } elseif ($trim !== '' && !str_starts_with($trim, '--')) {
            $activeInsertTable = null;
        }
    }

    fwrite($out, $line);

    if ($lineNo % 50000 === 0) {
        fwrite(STDERR, "Processed {$lineNo} lines...\n");
    }
}

fwrite($out, postfix());
fclose($in);
fclose($out);

$sizeMb = round(filesize($outputPath) / 1024 / 1024, 2);
echo "Selesai.\n";
echo "Input : {$inputPath}\n";
echo "Output: {$outputPath} ({$sizeMb} MB)\n";
echo "CREATE patched: {$stats['create_patched']}\n";
echo "INSERT rows patched: {$stats['insert_lines']}\n";
