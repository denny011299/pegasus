<?php
$f = 'C:\\Users\\Ruben\\.cursor\\projects\\d-Ruben-Data-Kerja-Git-OKEJOB-PEGASUS-PMI-pegasus\\agent-tools\\992bd1f9-d7b7-4bfa-8056-cb004bbbca19.txt';
$lines = file($f) ?: [];
foreach ($lines as $i => $line) {
    $low = strtolower($line);
    $keys = ['purchase_orders','sam oil','mutasi','eceran','gudang besar','active_warehouse','2026_09_29','warehouse_id','pembelian','fase2/live','credited','data-fix','data fix','backfill','alter'];
    $hit = [];
    foreach ($keys as $k) {
        if (str_contains($low, $k)) $hit[] = $k;
    }
    if (!$hit) continue;
    $snippet = $line;
    if (preg_match('/"text":"((?:\\\\.|[^"\\\\])*)"/', $line, $m)) {
        $snippet = stripcslashes($m[1]);
    } elseif (preg_match('/"content":"((?:\\\\.|[^"\\\\]){0,500})/', $line, $m)) {
        $snippet = stripcslashes($m[1]);
    }
    $snippet = preg_replace('/\s+/', ' ', $snippet);
    echo ($i + 1) . ' [' . implode(',', $hit) . '] ' . substr($snippet, 0, 350) . PHP_EOL . PHP_EOL;
}
