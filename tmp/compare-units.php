<?php

$pmoPath = 'c:/Users/Ruben/Downloads/u906028329_pmo_pegasus.sql';
$devPath = 'c:/Users/Ruben/Downloads/u906028329_dev (4).sql';
$outPath = 'c:/Users/Ruben/Downloads/unit-compare-pmo-vs-dev4.md';

$pmo = file_get_contents($pmoPath);
$dev = file_get_contents($devPath);

// --- PMO oms_product_unit ---
$pmoUnits = [];
if (preg_match('/INSERT INTO `oms_product_unit`[^;]+;/s', $pmo, $m)) {
    preg_match_all(
        "/\\('([^']*)',\\s*'((?:\\\\'|[^'])*)',\\s*'([^']*)',\\s*'([^']*)'/",
        $m[0],
        $rows,
        PREG_SET_ORDER
    );
    foreach ($rows as $r) {
        $pmoUnits[] = [
            'id' => $r[1],
            'title' => stripcslashes($r[2]),
            'published' => $r[3],
        ];
    }
}

// Distinct satuan strings on oms_product
$satuanCounts = [];
if (preg_match_all('/INSERT INTO `oms_product`[^;]+;/s', $pmo, $blocks)) {
    foreach ($blocks[0] as $block) {
        // VALUES tuples: id, id_product_cat, title, kode, harga, satuan, id_product_unit, ...
        preg_match_all(
            "/\\('([^']*)',\\s*'([^']*)',\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*([0-9.]+),\\s*'((?:\\\\'|[^'])*)',\\s*'([^']*)'/",
            $block,
            $prows,
            PREG_SET_ORDER
        );
        foreach ($prows as $pr) {
            $sat = stripcslashes($pr[6]);
            $satuanCounts[$sat] = ($satuanCounts[$sat] ?? 0) + 1;
        }
    }
}

// --- DEV IPM units ---
$devUnits = [];
if (preg_match('/INSERT INTO `units`[^;]+;/s', $dev, $d)) {
    preg_match_all(
        "/\\((\\d+),\\s*(NULL|\\d+),\\s*'((?:\\\\'|[^'])*)',\\s*'((?:\\\\'|[^'])*)',\\s*(\\d+)/",
        $d[0],
        $rows,
        PREG_SET_ORDER
    );
    foreach ($rows as $r) {
        $devUnits[] = [
            'unit_id' => (int) $r[1],
            'ref_unit_id' => $r[2] === 'NULL' ? null : (int) $r[2],
            'unit_name' => stripcslashes($r[3]),
            'unit_short_name' => stripcslashes($r[4]),
            'status' => (int) $r[5],
        ];
    }
}

$norm = static function (string $s): string {
    $s = mb_strtolower(trim($s));
    $s = str_replace([' ', '-', '_'], '', $s);

    return $s;
};

// Build compare
$lines = [];
$lines[] = '# Perbandingan satuan: PMO (`oms_product_unit`) vs IPM DEV (`units`)';
$lines[] = '';
$lines[] = '- **Sumber PMO:** `u906028329_pmo_pegasus.sql`';
$lines[] = '- **IPM STAGING:** `u906028329_dev (4).sql` (26 Sep 2026) — dump utuh';
$lines[] = '- **Catatan:** dump DEV (3) sebelumnya **rusak** (permission denied) — jangan dipakai';
$lines[] = '- **Aturan:** PMO = sumber utama untuk sync/merge';
$lines[] = '';

$lines[] = '## 1. Semua satuan PMO (`oms_product_unit`)';
$lines[] = '';
$lines[] = '| # | PMO id | title | published |';
$lines[] = '|---|--------|-------|-----------|';
$i = 1;
foreach ($pmoUnits as $u) {
    $lines[] = '| '.$i.' | `'.$u['id'].'` | **'.$u['title'].'** | '.$u['published'].' |';
    $i++;
}
$lines[] = '';
$lines[] = '**Total PMO:** '.count($pmoUnits);
$lines[] = '';

$lines[] = '## 2. Semua satuan IPM DEV (`units`)';
$lines[] = '';
$lines[] = '| # | unit_id | unit_name | unit_short_name | status | ref_unit_id |';
$lines[] = '|---|---------|-----------|-----------------|--------|-------------|';
$i = 1;
foreach ($devUnits as $u) {
    $st = $u['status'] === 1 ? 'aktif' : 'nonaktif';
    $ref = $u['ref_unit_id'] === null ? '—' : (string) $u['ref_unit_id'];
    $lines[] = '| '.$i.' | '.$u['unit_id'].' | **'.$u['unit_name'].'** | '.$u['unit_short_name'].' | '.$st.' | '.$ref.' |';
    $i++;
}
$lines[] = '';
$lines[] = '**Total DEV:** '.count($devUnits);
$lines[] = '';

$lines[] = '## 3. String `satuan` yang dipakai di produk PMO (`oms_product.satuan`)';
$lines[] = '';
$lines[] = '| satuan (teks) | jumlah produk |';
$lines[] = '|---------------|---------------|';
ksort($satuanCounts, SORT_NATURAL | SORT_FLAG_CASE);
foreach ($satuanCounts as $sat => $cnt) {
    $lines[] = '| **'.$sat.'** | '.$cnt.' |';
}
$lines[] = '';

// Match suggestions
$lines[] = '## 4. Calon merge (normalisasi nama, PMO → DEV)';
$lines[] = '';
$lines[] = '| PMO title | Match DEV? | unit_id DEV | catatan |';
$lines[] = '|-----------|------------|-------------|---------|';

$devByNorm = [];
foreach ($devUnits as $u) {
    foreach ([$u['unit_name'], $u['unit_short_name']] as $label) {
        $k = $norm($label);
        if ($k === '') {
            continue;
        }
        $devByNorm[$k][] = $u;
    }
}

$aliases = [
    'dos' => 'dus',
    'dus' => 'dos',
    'pc' => 'pcs',
    'pcs' => 'pcs',
    'piece' => 'pcs',
    'pieces' => 'pcs',
    'jerigen' => 'jerigen',
    'jerrycan' => 'jerigen',
];

foreach ($pmoUnits as $pu) {
    $k = $norm($pu['title']);
    $hits = $devByNorm[$k] ?? [];
    if ($hits === [] && isset($aliases[$k])) {
        $hits = $devByNorm[$norm($aliases[$k])] ?? [];
    }
    // fuzzy: contains
    if ($hits === []) {
        foreach ($devByNorm as $dk => $arr) {
            if ($k !== '' && (str_contains($dk, $k) || str_contains($k, $dk))) {
                $hits = array_merge($hits, $arr);
            }
        }
    }
    // unique by unit_id
    $uniq = [];
    foreach ($hits as $h) {
        $uniq[$h['unit_id']] = $h;
    }
    $hits = array_values($uniq);

    if ($hits === []) {
        $lines[] = '| **'.$pu['title'].'** | ❌ tidak ada | — | kandidat **buat baru** di IPM dari PMO |';
    } else {
        $ids = implode(', ', array_map(fn ($h) => (string) $h['unit_id'], $hits));
        $names = implode(' / ', array_map(fn ($h) => $h['unit_name'].' ('.$h['unit_short_name'].')', $hits));
        $note = count($hits) > 1 ? '⚠️ beberapa kandidat — pilih 1' : '✅ cocok';
        if (in_array($k, ['dos', 'dus'], true) || preg_match('/dos|dus/i', $pu['title'].$names)) {
            $note .= ' — **cek DOS vs Dus**';
        }
        $lines[] = '| **'.$pu['title'].'** | '.$names.' | '.$ids.' | '.$note.' |';
    }
}
$lines[] = '';

$lines[] = '## 5. Satuan di DEV yang tidak ada di PMO (mungkin lokal / legacy)';
$lines[] = '';
$pmoNorms = [];
foreach ($pmoUnits as $pu) {
    $pmoNorms[$norm($pu['title'])] = true;
}
$lines[] = '| unit_id | unit_name | short | status |';
$lines[] = '|---------|-----------|-------|--------|';
foreach ($devUnits as $u) {
    $k1 = $norm($u['unit_name']);
    $k2 = $norm($u['unit_short_name']);
    $inPmo = isset($pmoNorms[$k1]) || isset($pmoNorms[$k2]);
    // alias dos/dus
    if (! $inPmo && (isset($aliases[$k1]) && isset($pmoNorms[$norm($aliases[$k1])]))) {
        $inPmo = true;
    }
    if (! $inPmo && (isset($aliases[$k2]) && isset($pmoNorms[$norm($aliases[$k2])]))) {
        $inPmo = true;
    }
    if (! $inPmo) {
        $st = $u['status'] === 1 ? 'aktif' : 'nonaktif';
        $lines[] = '| '.$u['unit_id'].' | **'.$u['unit_name'].'** | '.$u['unit_short_name'].' | '.$st.' |';
    }
}
$lines[] = '';

$lines[] = '## 6. Highlight DOS / Dus / Dos';
$lines[] = '';
$lines[] = '### PMO';
foreach ($pmoUnits as $u) {
    if (preg_match('/dos|dus/i', $u['title'])) {
        $lines[] = '- `'.$u['id'].'` → **'.$u['title'].'** ('.$u['published'].')';
    }
}
$lines[] = '';
$lines[] = '### DEV';
foreach ($devUnits as $u) {
    if (preg_match('/dos|dus/i', $u['unit_name'].' '.$u['unit_short_name'])) {
        $lines[] = '- unit_id **'.$u['unit_id'].'** → name=`'.$u['unit_name'].'` short=`'.$u['unit_short_name'].'` status='.$u['status'];
    }
}
$lines[] = '';
$lines[] = '> Saran merge: pakai ejaan **PMO** (`Dus`) sebagai kanonik; alias IPM `DOS`/`Dos` diarahkan ke unit yang sama (jangan biarkan 2 unit_id hidup untuk arti yang sama).';
$lines[] = '';

file_put_contents($outPath, implode("\n", $lines));
echo "Wrote $outPath\n";
echo 'PMO units: '.count($pmoUnits)."\n";
echo 'DEV units: '.count($devUnits)."\n";
echo 'PMO product satuan strings: '.count($satuanCounts)."\n";
