<?php

$dev = file_get_contents('c:/Users/Ruben/Downloads/u906028329_dev (4).sql');
preg_match('/INSERT INTO `units`[^;]+;/s', $dev, $m);
preg_match_all(
    "/\\((\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+)/",
    $m[0],
    $rows,
    PREG_SET_ORDER
);

$aktif = $ipmAktif = $ipmAll = 0;
$key = [];
foreach ($rows as $r) {
    $id = (int) $r[1];
    $st = (int) $r[5];
    $n = stripcslashes($r[3]);
    if ($st === 1) {
        $aktif++;
    }
    if (stripos($n, 'IPMTEST') !== false) {
        $ipmAll++;
        if ($st === 1) {
            $ipmAktif++;
        }
    }
    if (in_array($id, [7, 9, 126, 127], true) || preg_match('/dos|dus|piece|^pcs$/i', $n.' '.$r[4])) {
        $key[] = sprintf(
            '%d | %s | short=%s | %s | ref=%s',
            $id,
            $n,
            stripcslashes($r[4]),
            $st === 1 ? 'AKTIF' : 'nonaktif',
            $r[2]
        );
    }
}

echo "DEV units total: ".count($rows)."\n";
echo "aktif: $aktif\n";
echo "IPMTEST total: $ipmAll\n";
echo "IPMTEST masih AKTIF: $ipmAktif\n\n";
echo "KEY UNITS:\n".implode("\n", $key)."\n";
