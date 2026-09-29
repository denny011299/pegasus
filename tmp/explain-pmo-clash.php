<?php

$pmoPath = 'd:/Ruben Data/Kerja Git/OKEJOB/PEGASUS/PMO by Okejob/PMO/pegasusm_pmo.sql';
$ipmPath = 'c:/Users/Ruben/Downloads/u906028329_dev (5).sql';
$out = dirname(__DIR__).'/database/scripts/sync-sku-duplicates/ kenapa-nabrak-pmo-vs-ipm.md';
$out = dirname(__DIR__).'/database/scripts/sync-sku-duplicates/kenapa-nabrak-pmo-vs-ipm.md';

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

// PMO: ('id', 'cat', 'title', 'kode', harga, 'satuan', 'id_product_unit', ...
$pmoSql = loadInsert($pmoPath, 'oms_product');
$pmo = [];
preg_match_all(
    "/\\('([^']*)',\\s*'([^']*)',\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*([0-9]+),\\s*'([^']*)',\\s*'([^']*)'/",
    $pmoSql,
    $rows,
    PREG_SET_ORDER
);
foreach ($rows as $r) {
    $sku = stripcslashes($r[4]);
    $key = mb_strtolower(trim($sku));
    if ($key === '') {
        continue;
    }
    $pmo[$key][] = [
        'id' => $r[1],
        'title' => stripcslashes($r[3]),
        'kode' => $sku,
        'satuan' => $r[6],
        'id_product_unit' => $r[7],
    ];
}

// IPM variants
$productsSql = loadInsert($ipmPath, 'products');
$variantsSql = loadInsert($ipmPath, 'product_variants');
$products = [];
preg_match_all(
    "/\\((\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)'/",
    $productsSql,
    $prows,
    PREG_SET_ORDER
);
// too loose - use earlier pattern
$products = [];
preg_match_all(
    "/\\((\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+),\\s*(\\d+),\\s*(\\d+)/",
    $productsSql,
    $prows,
    PREG_SET_ORDER
);
foreach ($prows as $r) {
    $products[(int) $r[1]] = [
        'ref' => $r[2] === 'NULL' ? null : $r[2],
        'name' => stripcslashes($r[3]),
    ];
}

$ipmBySku = [];
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
    $key = mb_strtolower(trim($sku));
    if ($key === '') {
        continue;
    }
    $p = $products[$pid] ?? null;
    $ipmBySku[$key][] = [
        'pv' => $vid,
        'product_id' => $pid,
        'sku' => $sku,
        'name' => stripcslashes($r[3]),
        'ref' => $p['ref'] ?? null,
        'product_name' => $p['name'] ?? '?',
        'is_pmo' => ($p['ref'] ?? null) !== null,
        'status' => (int) $r[16],
    ];
}

$hit = 0;
$caseClash = 0; // PMO sku matches IPM lokal (no ref) AND also has PMO-linked IPM row OR multiple
$onlyLocal = 0;
$onlyPmoLinked = 0;
$multiIpm = 0;
$examples = [];

foreach ($pmo as $key => $plist) {
    $ipm = $ipmBySku[$key] ?? [];
    if ($ipm === []) {
        continue;
    }
    $hit++;
    $locals = array_filter($ipm, fn ($v) => ! $v['is_pmo']);
    $linked = array_filter($ipm, fn ($v) => $v['is_pmo']);
    if (count($ipm) > 1) {
        $multiIpm++;
    }
    if ($locals && $linked) {
        $caseClash++;
        if (count($examples) < 12) {
            $examples[] = [
                'sku' => $key,
                'pmo_title' => $plist[0]['title'],
                'pmo_kode' => $plist[0]['kode'],
                'pmo_satuan' => $plist[0]['satuan'],
                'ipm_local' => array_values($locals)[0],
                'ipm_pmo' => array_values($linked)[0],
            ];
        }
    } elseif ($locals && ! $linked) {
        $onlyLocal++;
    } elseif ($linked && ! $locals) {
        $onlyPmoLinked++;
    }
}

// Name mismatch example: same sku family
$airAki = [];
foreach ($pmo as $key => $plist) {
    if (str_contains($key, 'aahk') || str_contains($key, 'aahk')) {
        $airAki[] = $plist[0];
    }
}

$md = [];
$md[] = '# Kenapa produk PMO “nabrak” IPM?';
$md[] = '';
$md[] = 'Sumber PMO: `PMO by Okejob/PMO/pegasusm_pmo.sql` (`oms_product`)';
$md[] = 'Sumber IPM: `u906028329_dev (5).sql`';
$md[] = '';
$md[] = '## Model data beda';
$md[] = '';
$md[] = '| | **PMO** (`oms_product`) | **IPM** (`products` + `product_variants`) |';
$md[] = '|--|--|--|';
$md[] = '| Bentuk | **1 baris = 1 SKU** (title sudah termasuk size) | Sering **1 produk induk + banyak varian** |';
$md[] = '| Kunci | `id` → `ref_product_id`, `kode` → SKU | `ref_product_id` di **produk**, SKU di **varian** |';
$md[] = '| Satuan | per baris (`dus/pcs/pack/...`) | `unit_id` di varian + default di produk |';
$md[] = '';
$md[] = 'Contoh PMO: title=`AIR AKI HIKARI 12 X 1500 ml`, kode=`Aahk1500ml`';
$md[] = 'Contoh IPM lama: produk=`AIR AKI HIKARI`, varian name=`12 x 1500 ml`, sku=`AAHK1500ML`';
$md[] = '';
$md[] = '## Cara sync IPM sekarang (akar nabrak)';
$md[] = '';
$md[] = '1. **Sync produk** cocokkan lewat `ref_product_id`, atau **adopsi nama produk** (bukan SKU).';
$md[] = '2. Nama PMO ≠ nama induk IPM → **tidak ketemu** → **INSERT produk baru**.';
$md[] = '3. Sync varian hanya cari SKU **di dalam product_id itu**. Varian lokal di produk lain **tidak kelihatan**.';
$md[] = '4. Hasil: SKU yang sama (beda huruf) hidup **2×** — lokal berstok + PMO stok 0.';
$md[] = '';
$md[] = '## Angka (SKU casefold PMO ∩ IPM)';
$md[] = '';
$md[] = '- Baris PMO aktif ter-parse (sku unik casefold): **'.count($pmo).'**';
$md[] = '- SKU PMO yang juga ada di IPM: **'.$hit.'**';
$md[] = '- Di IPM sudah **ganda** (lokal + sudah ber-ref PMO) untuk SKU yang sama: **'.$caseClash.'** ← ini “nabrak”';
$md[] = '- Hanya lokal (belum ref): **'.$onlyLocal.'**';
$md[] = '- Hanya baris IPM yang sudah ber-ref: **'.$onlyPmoLinked.'**';
$md[] = '- SKU dengan >1 varian IPM: **'.$multiIpm.'**';
$md[] = '';
$md[] = '## Contoh nabrak';
$md[] = '';
foreach ($examples as $e) {
    $L = $e['ipm_local'];
    $P = $e['ipm_pmo'];
    $md[] = '### `'.$e['sku'].'`';
    $md[] = '- **PMO:** '.$e['pmo_title'].' · kode=`'.$e['pmo_kode'].'` · satuan=`'.$e['pmo_satuan'].'`';
    $md[] = '- **IPM lokal:** pv='.$L['pv'].' sku=`'.$L['sku'].'` di produk "'.$L['product_name'].'" (ref NULL)';
    $md[] = '- **IPM dari sync:** pv='.$P['pv'].' sku=`'.$P['sku'].'` di produk "'.$P['product_name'].'" (ref='.$P['ref'].')';
    $md[] = '';
}

file_put_contents($out, implode("\n", $md));
echo "PMO skus: ".count($pmo)."\n";
echo "hit: $hit clash_local+pmo: $caseClash onlyLocal: $onlyLocal onlyLinked: $onlyPmoLinked multi: $multiIpm\n";
echo "Wrote $out\n";
