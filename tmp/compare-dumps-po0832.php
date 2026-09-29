<?php

$dumps = [
    'pegasus (2).sql' => 'c:/Users/Ruben/Downloads/u906028329_pegasus (2).sql',
    'pegasus (3).sql' => 'c:/Users/Ruben/Downloads/u906028329_pegasus (3).sql',
    'pegasus (4).sql' => 'c:/Users/Ruben/Downloads/u906028329_pegasus (4).sql',
    'pegasus (5).sql' => 'c:/Users/Ruben/Downloads/u906028329_pegasus (5).sql',
    'pegasus-29-09.sql' => 'c:/Users/Ruben/Downloads/u906028329_pegasus-29-09.sql',
];

foreach ($dumps as $label => $path) {
    if (! is_file($path)) {
        echo "=== $label === MISSING\n\n";
        continue;
    }
    $mtime = date('Y-m-d H:i:s', filemtime($path));
    $size = filesize($path);
    $has832 = false;
    $maxPo = null;
    $details832 = [];
    $po832line = null;
    $fh = fopen($path, 'rb');
    while (($line = fgets($fh)) !== false) {
        if (str_contains($line, "'PO0832'") || str_contains($line, ',PO0832,')) {
            $has832 = true;
            if (preg_match("/\((\d+),\s*'PO0832',\s*'([^']*)',\s*(\d+)/", $line, $m)) {
                $po832line = "po_id={$m[1]} date={$m[2]} supplier={$m[3]}";
            } elseif (preg_match("/\((\d+), 'PO0832', '([^']*)', (\d+)/", $line, $m)) {
                $po832line = "po_id={$m[1]} date={$m[2]} supplier={$m[3]}";
            }
        }
        // detail rows referencing po_id 832 with SKUs we care about — scan purchase_orders_details inserts
        if (str_contains($line, 'BTLMREM1LTRSUJS') && preg_match_all("/\((\d+),\s*(\d+),\s*(\d+),[^)]*'BTLMREM1LTRSUJS'[^)]*\)/", $line, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $hit) {
                if ((int) $hit[2] === 832) {
                    $details832[] = "pod={$hit[1]} variant={$hit[3]} sku=BTLMREM1LTRSUJS";
                }
            }
        }
        if (str_contains($line, 'DOSHKAA400MLMGM') && preg_match_all("/\((\d+),\s*(\d+),\s*(\d+),[^)]*'DOSHKAA400MLMGM'[^)]*\)/", $line, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $hit) {
                if ((int) $hit[2] === 832) {
                    $details832[] = "pod={$hit[1]} variant={$hit[3]} sku=DOSHKAA400MLMGM";
                }
            }
        }
        // crude max PO08xx
        if (preg_match_all("/'PO(08\d{2})'/", $line, $pm)) {
            foreach ($pm[1] as $n) {
                if ($maxPo === null || $n > $maxPo) {
                    $maxPo = $n;
                }
            }
        }
    }
    fclose($fh);

    // better parse details for po 832 from details table using simpler rg-style second pass for lines with ', 832,'
    $detailsAll = [];
    $fh = fopen($path, 'rb');
    while (($line = fgets($fh)) !== false) {
        if (! str_contains($line, 'INSERT INTO `purchase_orders_details`')) {
            continue;
        }
        // may be multi-line; keep reading until ;
        $buf = $line;
        while (! str_ends_with(rtrim($buf), ';') && ($more = fgets($fh)) !== false) {
            $buf .= $more;
        }
        if (preg_match_all("/\((\d+),\s*832,\s*(\d+),\s*'([^']*)',\s*'([^']*)',\s*(\d+),\s*'([^']*)',\s*([^,]+),\s*([^,]+)/", $buf, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $hit) {
                $detailsAll[] = sprintf(
                    "pod=%s variant=%s name=%s sku=%s qty=%s",
                    $hit[1],
                    $hit[2],
                    $hit[3],
                    $hit[6],
                    $hit[8]
                );
            }
        }
        break;
    }
    fclose($fh);

    echo "=== $label ===\n";
    echo "file_mtime=$mtime size_mb=".round($size / 1048576, 1)."\n";
    echo "max_PO08=$maxPo has_PO0832=".($has832 ? 'YES' : 'NO')."\n";
    if ($po832line) {
        echo "$po832line\n";
    }
    if ($detailsAll) {
        echo "details_on_PO0832:\n";
        foreach ($detailsAll as $d) {
            echo "  $d\n";
        }
    } elseif ($has832) {
        echo "details_on_PO0832: (parse miss — check manually)\n";
    }
    echo "\n";
}
