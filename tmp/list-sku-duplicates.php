<?php

/**
 * List case-insensitive SKU duplicates + PMO vs lokal from DEV(5) dump.
 */
$path = 'c:/Users/Ruben/Downloads/u906028329_dev (5).sql';
$outMd = 'c:/Users/Ruben/Downloads/sku-duplicates-casefold-dev5.md';
$outRepo = __DIR__.'/../database/scripts/migrate-units-ipm-to-pmo/../sku-duplicates-casefold-dev5.md';
$outRepo = dirname(__DIR__).'/database/scripts/sku-duplicates-casefold-dev5.md';

function loadInsert(string $path, string $table): string
{
    $fh = fopen($path, 'r');
    $out = '';
    $cap = false;
    while (($line = fgets($fh)) !== false) {
        if (preg_match('/^INSERT INTO `'.preg_quote($table, '/').'`/', $line)) {
            $cap = true;
        }
        if ($cap) {
            $out .= $line;
            if (str_ends_with(rtrim($line), ';')) {
                $cap = false;
            }
        }
    }
    fclose($fh);

    return $out;
}

function parseTuples(string $sql): array
{
    // Split on "),(" carefully for quoted strings — use regex for known column order
    return [];
}

$productsSql = loadInsert($path, 'products');
$variantsSql = loadInsert($path, 'product_variants');
$stocksSql = loadInsert($path, 'product_stocks');

// products: (product_id, ref_product_id|NULL, 'name', 'kind', category_id, 'unit_json', alert, unit_id, status, ...)
$products = [];
preg_match_all(
    "/\\((\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*(\\d+),\\s*(\\d+)/",
    $productsSql,
    $prows,
    PREG_SET_ORDER
);
foreach ($prows as $r) {
    $products[(int) $r[1]] = [
        'product_id' => (int) $r[1],
        'ref_product_id' => $r[2] === 'NULL' ? null : (int) $r[2],
        'product_name' => stripcslashes($r[3]),
        'status' => (int) $r[9],
    ];
}

// variants: (pv_id, product_id, 'name', 'sku', price, 'barcode'|NULL, stock|NULL, retail_unit|NULL, alert|NULL, lead, safety, safety_unit|NULL, unit_id|NULL, qty_pallet|NULL, status, ...)
$variants = [];
preg_match_all(
    "/\\((\\d+),\\s*(\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*(NULL|'((?:\\\\'|[^'])*)'),\\s*(NULL|\\d+),\\s*(NULL|\\d+),\\s*(NULL|\\d+),\\s*(\\d+),\\s*(\\d+),\\s*(NULL|\\d+),\\s*(NULL|\\d+),\\s*(NULL|\\d+),\\s*(\\d+)/",
    $variantsSql,
    $vrows,
    PREG_SET_ORDER
);
foreach ($vrows as $r) {
    $vid = (int) $r[1];
    $pid = (int) $r[2];
    $sku = stripcslashes($r[4]);
    $p = $products[$pid] ?? null;
    $variants[$vid] = [
        'product_variant_id' => $vid,
        'product_id' => $pid,
        'name' => stripcslashes($r[3]),
        'sku' => $sku,
        'unit_id' => $r[14] === 'NULL' ? null : (int) $r[14],
        'status' => (int) $r[16],
        'product_name' => $p['product_name'] ?? '?',
        'ref_product_id' => $p['ref_product_id'] ?? null,
        'product_status' => $p['status'] ?? null,
        'is_pmo' => ($p['ref_product_id'] ?? null) !== null,
    ];
}

echo 'products parsed: '.count($products)."\n";
echo 'variants parsed: '.count($variants)."\n";

// stock qty by variant
$stockByVariant = [];
preg_match_all(
    '/\((\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*(\d+),\s*([0-9.]+)/',
    $stocksSql,
    $srows,
    PREG_SET_ORDER
);
foreach ($srows as $r) {
    $vid = (int) $r[2];
    $stockByVariant[$vid] = ($stockByVariant[$vid] ?? 0) + (float) $r[6];
}

// Group by lower(sku)
$bySku = [];
foreach ($variants as $v) {
    $key = mb_strtolower(trim($v['sku']));
    if ($key === '' || $key === '—' || $key === '-') {
        continue;
    }
    $bySku[$key][] = $v;
}

$dups = array_filter($bySku, fn ($arr) => count($arr) > 1);
echo 'casefold SKU groups with >1 variant: '.count($dups)."\n";

// Also: same casefold name across variants with different SKUs (fuzzy product twins) — optional top
$nameGroups = [];
foreach ($variants as $v) {
    if ((int) $v['status'] !== 1) {
        continue;
    }
    $nk = mb_strtolower(preg_replace('/\s+/', ' ', trim($v['name'])));
    $nameGroups[$nk][] = $v;
}
$nameDups = array_filter($nameGroups, function ($arr) {
    if (count($arr) < 2) {
        return false;
    }
    $skus = array_unique(array_map(fn ($v) => mb_strtolower($v['sku']), $arr));

    return count($skus) > 1;
});

$lines = [];
$lines[] = '# SKU kembar (case-insensitive) — DEV dump (5)';
$lines[] = '';
$lines[] = 'Sumber: `u906028329_dev (5).sql`';
$lines[] = 'Aturan: `LOWER(TRIM(sku))` sama → dianggap kembar (abaikan huruf besar/kecil).';
$lines[] = '**PMO** = product induk punya `ref_product_id`. **Lokal** = `ref_product_id` NULL.';
$lines[] = '';
$lines[] = '## Ringkas';
$lines[] = '';
$lines[] = '- Total varian ter-parse: **'.count($variants).'**';
$lines[] = '- Grup SKU casefold kembar: **'.count($dups).'**';
$lines[] = '- Nama sama (aktif) + SKU beda: **'.count($nameDups).'** (bukan case SKU — beda kode)';
$lines[] = '';

$lines[] = '## 1. SKU casefold kembar';
$lines[] = '';
$lines[] = '| # | sku_key | variants | saran |';
$lines[] = '|---|---------|----------|-------|';

$i = 0;
ksort($dups);
foreach ($dups as $key => $arr) {
    $i++;
    usort($arr, fn ($a, $b) => $a['product_variant_id'] <=> $b['product_variant_id']);
    $parts = [];
    $hasPmo = false;
    $hasLocal = false;
    $stockKeep = [];
    foreach ($arr as $v) {
        $st = $v['status'] === 1 ? 'aktif' : 'nonaktif';
        $src = $v['is_pmo'] ? 'PMO' : 'LOKAL';
        if ($v['is_pmo']) {
            $hasPmo = true;
        } else {
            $hasLocal = true;
        }
        $qty = $stockByVariant[$v['product_variant_id']] ?? 0;
        $stockKeep[$v['product_variant_id']] = $qty;
        $parts[] = '`'.$v['product_variant_id'].'` **'.$v['sku'].'** / '.$v['name'].' ('.$src.', '.$st.', stok='.$qty.', product_id='.$v['product_id'].', ref='.($v['ref_product_id'] ?? 'NULL').')';
    }
    if ($hasPmo && $hasLocal) {
        $saran = '**EDIT LOKAL**: pakai SKU PMO + isi `ref_product_id` di product lokal; nonaktifkan varian PMO kosong; pindah SO yang sudah nempel ke varian PMO → lokal';
    } elseif ($hasPmo && ! $hasLocal) {
        $saran = 'Kembar antar PMO — review manual (duplikat sync?)';
    } else {
        $saran = 'Kembar lokal — gabungkan manual';
    }
    $lines[] = '| '.$i.' | `'.$key.'` | '.implode('<br>', $parts).' | '.$saran.' |';
}

$lines[] = '';
$lines[] = '## 2. Contoh nama sama / SKU beda (aktif) — seperti AIR AKI';
$lines[] = '';
$lines[] = 'Ini **bukan** case SKU. Sync PMO bikin SKU baru → stok tetap di SKU lama.';
$lines[] = '';
$lines[] = '| nama_key | variants |';
$lines[] = '|----------|----------|';

$j = 0;
ksort($nameDups);
foreach ($nameDups as $nk => $arr) {
    $j++;
    if ($j > 80) {
        $lines[] = '| … | (+'.(count($nameDups) - 80).' lagi) |';
        break;
    }
    usort($arr, fn ($a, $b) => $a['product_variant_id'] <=> $b['product_variant_id']);
    $parts = [];
    foreach ($arr as $v) {
        $src = $v['is_pmo'] ? 'PMO' : 'LOKAL';
        $qty = $stockByVariant[$v['product_variant_id']] ?? 0;
        $parts[] = '`'.$v['product_variant_id'].'` SKU=`'.$v['sku'].'` ('.$src.', stok='.$qty.', ref='.($v['ref_product_id'] ?? 'NULL').')';
    }
    $lines[] = '| '.htmlspecialchars($nk).' | '.implode('<br>', $parts).' |';
}

$lines[] = '';
$lines[] = '## Rekomendasi strategi';
$lines[] = '';
$lines[] = '1. **SKU cuma beda huruf besar/kecil** → **jangan remap stok seperti satuan**. Lebih mudah:';
$lines[] = '   - Tahan varian yang **punya stok / history** (biasanya lokal / id lebih kecil)';
$lines[] = '   - Update SKU-nya ke ejaan PMO (kanonik)';
$lines[] = '   - Isi `products.ref_product_id` dari produk PMO';
$lines[] = '   - Nonaktifkan produk/varian PMO duplikat yang stoknya 0';
$lines[] = '   - Remap baris SO yang sudah menunjuk varian PMO kosong → varian yang di-keep (sedikit UPDATE `sales_order_details.product_variant_id`)';
$lines[] = '2. **Nama mirip / SKU beda total** (contoh DOSAAH1500ML vs Aahk1500ml) → sama pola di atas, tapi matching manual/fuzzy; **wajib** remap SO + nonaktifkan duplikat.';
$lines[] = '3. Remap massal semua FK seperti satuan **hanya** kalau mau hapus product_variant_id lama — lebih berat, biasanya tidak perlu.';
$lines[] = '';

@mkdir(dirname($outRepo), 0777, true);
file_put_contents($outMd, implode("\n", $lines));
file_put_contents($outRepo, implode("\n", $lines));
echo "Wrote $outMd\n";
echo "Wrote $outRepo\n";

// Print AIR AKI related for console
echo "\n--- AIR AKI related variants ---\n";
foreach ($variants as $v) {
    if (stripos($v['name'], 'AIR AKI') !== false || stripos($v['sku'], 'aah') !== false || stripos($v['sku'], 'aaah') !== false) {
        if (stripos($v['name'], 'HIKARI') !== false || stripos($v['sku'], 'aahk') !== false || stripos($v['sku'], 'dosaah') !== false) {
            $qty = $stockByVariant[$v['product_variant_id']] ?? 0;
            echo sprintf(
                "pv=%d sku=%s name=%s pmo=%s ref=%s stok=%s status=%d\n",
                $v['product_variant_id'],
                $v['sku'],
                $v['name'],
                $v['is_pmo'] ? 'Y' : 'N',
                $v['ref_product_id'] ?? 'NULL',
                $qty,
                $v['status']
            );
        }
    }
}
