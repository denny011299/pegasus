<?php
/**
 * Stream-load ALL INSERT chunks for a table (mysqldump often splits).
 */
function loadAllInserts(string $path, string $table): string
{
    $fh = fopen($path, 'r');
    $out = '';
    $cap = false;
    $cols = null;
    while (($line = fgets($fh)) !== false) {
        if (preg_match('/^INSERT INTO `'.preg_quote($table, '/').'`/', $line)) {
            if ($cols === null && preg_match('/^INSERT INTO `[^`]+` \(([^)]+)\) VALUES/', $line, $m)) {
                $cols = $m[1];
                $out = "INSERT INTO `{$table}` ({$cols}) VALUES\n";
            } elseif ($cols !== null) {
                // subsequent INSERT — append values only; change INSERT to comma continuation
                if (preg_match('/^INSERT INTO `[^`]+`(?: \([^)]+\))? VALUES\s*/', $line, $m)) {
                    $line = substr($line, strlen($m[0]));
                    if ($out !== '' && !str_ends_with(rtrim($out), ',') && !str_ends_with(rtrim($out), 'VALUES')) {
                        $out = rtrim($out);
                        if (str_ends_with($out, ';')) {
                            $out = substr($out, 0, -1) . ",\n";
                        } else {
                            $out .= ",\n";
                        }
                    }
                    $cap = true;
                    $out .= $line;
                    if (str_ends_with(rtrim($line), ';')) {
                        $cap = false;
                    }
                    continue;
                }
            }
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
    // normalize trailing
    $out = rtrim($out);
    if ($out !== '' && !str_ends_with($out, ';')) {
        $out .= ';';
    }
    // fix "); ," artifacts from merge: replace `;\nINSERT` style already handled
    // If we closed with `;` mid-merge wrongly:
    $out = preg_replace('/\);\s*,\s*\(/', '),(', $out);
    $out = preg_replace('/\);\s*\(/', '),(', $out);

    return $out;
}

function parseRows(string $ins): array
{
    if (!preg_match('/VALUES\s*(.+);\s*$/s', $ins, $m)) {
        return [];
    }
    $body = $m[1];
    $rows = [];
    $len = strlen($body);
    $i = 0;
    while ($i < $len) {
        while ($i < $len && ($body[$i] === ',' || ctype_space($body[$i]))) {
            $i++;
        }
        if ($i >= $len) {
            break;
        }
        if ($body[$i] !== '(') {
            $i++;
            continue;
        }
        $i++;
        $fields = [];
        $cur = '';
        $inStr = false;
        while ($i < $len) {
            $ch = $body[$i];
            if ($inStr) {
                if ($ch === '\\') {
                    $cur .= $ch . $body[$i + 1];
                    $i += 2;
                    continue;
                }
                if ($ch === "'") {
                    if ($i + 1 < $len && $body[$i + 1] === "'") {
                        $cur .= "''";
                        $i += 2;
                        continue;
                    }
                    $inStr = false;
                    $cur .= $ch;
                    $i++;
                    continue;
                }
                $cur .= $ch;
                $i++;
                continue;
            }
            if ($ch === "'") {
                $inStr = true;
                $cur .= $ch;
                $i++;
                continue;
            }
            if ($ch === ',') {
                $fields[] = $cur;
                $cur = '';
                $i++;
                continue;
            }
            if ($ch === ')') {
                $fields[] = $cur;
                $i++;
                break;
            }
            $cur .= $ch;
            $i++;
        }
        $rows[] = $fields;
    }

    return $rows;
}

function unq(?string $v): ?string
{
    if ($v === null) {
        return null;
    }
    $v = trim($v);
    if (strcasecmp($v, 'NULL') === 0) {
        return null;
    }
    if (strlen($v) >= 2 && $v[0] === "'" && substr($v, -1) === "'") {
        return stripcslashes(str_replace("''", "'", substr($v, 1, -1)));
    }

    return $v;
}

foreach ([4, 5] as $n) {
    $path = "c:/Users/Ruben/Downloads/u906028329_dev ({$n}).sql";
    echo "==== DEV{$n} ====\n";
    $prodIns = loadAllInserts($path, 'products');
    $varIns = loadAllInserts($path, 'product_variants');
    preg_match('/INSERT INTO `products` \(([^)]+)\) VALUES/s', $prodIns, $pc);
    preg_match('/INSERT INTO `product_variants` \(([^)]+)\) VALUES/s', $varIns, $vc);
    $pi = array_flip(array_map(fn ($x) => trim($x, " `\n\r\t"), explode(',', $pc[1])));
    $vi = array_flip(array_map(fn ($x) => trim($x, " `\n\r\t"), explode(',', $vc[1])));

    $products = [];
    foreach (parseRows($prodIns) as $f) {
        $id = (int) $f[$pi['product_id']];
        $products[$id] = [
            'ref' => unq($f[$pi['ref_product_id']]),
            'name' => unq($f[$pi['product_name']]),
            'status' => (int) $f[$pi['status']],
        ];
    }
    $withRef = count(array_filter($products, fn ($p) => $p['ref'] !== null));
    echo 'products='.count($products)." withRef={$withRef}\n";

    $bySku = [];
    $active = 0;
    $maxVid = 0;
    foreach (parseRows($varIns) as $f) {
        $vid = (int) $f[$vi['product_variant_id']];
        $maxVid = max($maxVid, $vid);
        if ((int) $f[$vi['status']] !== 1) {
            continue;
        }
        $active++;
        $sku = unq($f[$vi['product_variant_sku']]) ?? '';
        $k = strtolower(trim($sku));
        if ($k === '') {
            continue;
        }
        $pid = (int) $f[$vi['product_id']];
        $bySku[$k][] = [
            'vid' => $vid,
            'pid' => $pid,
            'sku' => $sku,
            'hasRef' => ! empty($products[$pid]['ref']),
        ];
    }
    $dups = array_filter($bySku, fn ($x) => count($x) > 1);
    echo "variants_active={$active} maxVid={$maxVid} dup_skus=".count($dups)."\n";

    $pairs = 0;
    foreach ($dups as $k => $list) {
        $L = array_values(array_filter($list, fn ($x) => ! $x['hasRef']));
        $P = array_values(array_filter($list, fn ($x) => $x['hasRef']));
        if (count($L) === 1 && count($P) === 1) {
            $pairs++;
            if ($pairs <= 3 || $k === 'aahk1500ml') {
                echo "  PAIR {$k}: keep={$L[0]['vid']}@p{$L[0]['pid']} drop={$P[0]['vid']}@p{$P[0]['pid']} skuL={$L[0]['sku']} skuP={$P[0]['sku']}\n";
            }
        }
    }
    echo "clean local↔pmo pairs={$pairs}\n";

    // product name dupes among active
    $byName = [];
    foreach ($products as $id => $p) {
        if ($p['status'] !== 1) {
            continue;
        }
        $nk = strtolower(trim($p['name'] ?? ''));
        if ($nk === '') {
            continue;
        }
        $byName[$nk][] = $id.($p['ref'] ? 'P' : 'L');
    }
    $nameDups = array_filter($byName, fn ($x) => count($x) > 1);
    echo 'active product name dups (casefold)='.count($nameDups)."\n";
}
