<?php

$mappingPath = dirname(__DIR__).'/database/scripts/sync-sku-duplicates/mapping.md';
$outCsv = dirname(__DIR__).'/database/scripts/sync-sku-duplicates/35-pasangan-PMO-vs-IPM.csv';
$outXlsxFriendly = 'c:/Users/Ruben/Downloads/35-pasangan-PMO-vs-IPM.csv';

$lines = file($mappingPath, FILE_IGNORE_NEW_LINES);
$rows = [];
foreach ($lines as $line) {
    if (! preg_match('/^\| `([^`]+)` \| \*\*(\d+)\*\* \(`([^`]+)`\) \| ([^|]+) \| (\d+) \(`([^`]+)`\) \| ([^|]+) \| (\d+) \| (\d+) \| ([^|]+) \| `([^`]+)` \|$/', trim($line), $m)) {
        continue;
    }
    $rows[] = [
        'sku_key' => $m[1],
        'ipm_variant_id' => $m[2],
        'ipm_sku' => $m[3],
        'ipm_stok' => trim($m[4]),
        'pmo_variant_id' => $m[5],
        'pmo_sku' => $m[6],
        'pmo_stok' => trim($m[7]),
        'pmo_so_count' => trim($m[8]),
        'pmo_product_id' => trim($m[9]),
        'pmo_ref_product_id' => trim($m[10]),
        'sku_kanonik_pmo' => $m[11],
    ];
}

$fh = fopen($outCsv, 'w');
// UTF-8 BOM supaya Excel Windows baca benar
fwrite($fh, "\xEF\xBB\xBF");
fputcsv($fh, [
    'No',
    'SKU key (casefold)',
    'IPM — variant_id (KEEP)',
    'IPM — SKU lama',
    'IPM — stok',
    'PMO — variant_id (DROP/nonaktif)',
    'PMO — SKU',
    'PMO — stok',
    'PMO — jml baris SO',
    'PMO — product_id',
    'PMO — ref_product_id',
    'SKU kanonik setelah sync',
    'Aksi',
], ';');

$n = 0;
foreach ($rows as $r) {
    $n++;
    fputcsv($fh, [
        $n,
        $r['sku_key'],
        $r['ipm_variant_id'],
        $r['ipm_sku'],
        $r['ipm_stok'],
        $r['pmo_variant_id'],
        $r['pmo_sku'],
        $r['pmo_stok'],
        $r['pmo_so_count'],
        $r['pmo_product_id'],
        $r['pmo_ref_product_id'],
        $r['sku_kanonik_pmo'],
        'Keep IPM → pindah ke produk PMO; nonaktifkan varian PMO; remap SO PMO→IPM',
    ], ';');
}
fclose($fh);

copy($outCsv, $outXlsxFriendly);
echo "rows=$n\n$outCsv\n$outXlsxFriendly\n";
