<?php
$sql = file_get_contents('c:/Users/Ruben/Downloads/u906028329_pegasus (6).sql');

function extractInsert($sql, $table) {
  $rows = [];
  $pos = 0;
  while (($p = strpos($sql, 'INSERT INTO `' . $table . '`', $pos)) !== false) {
    $semi = strpos($sql, ";\n", $p);
    if ($semi === false) break;
    $chunk = substr($sql, $p, $semi - $p);
    $pos = $semi + 1;
    // split value tuples roughly by ),(
    if (!preg_match('/VALUES\s*(.*)$/s', $chunk, $m)) continue;
    $vals = $m[1];
    // naive: match each ( ... ) at start of lines or after ),
    preg_match_all('/\(([^()]*(?:\([^()]*\)[^()]*)*)\)/', $vals, $mm);
    foreach ($mm[1] as $raw) $rows[] = $raw;
  }
  return $rows;
}

function parseCsvLike($raw) {
  $out = []; $cur = ''; $inQ = false; $len = strlen($raw);
  for ($i = 0; $i < $len; $i++) {
    $c = $raw[$i];
    if ($c === "'" && ($i === 0 || $raw[$i-1] !== '\\\\')) {
      // handle escaped quotes '' 
      if ($inQ && $i+1 < $len && $raw[$i+1] === "'") { $cur .= "'"; $i++; continue; }
      $inQ = !$inQ; continue;
    }
    if ($c === ',' && !$inQ) { $out[] = $cur; $cur = ''; continue; }
    $cur .= $c;
  }
  $out[] = $cur;
  return $out;
}

// Simpler: use rg-style line parsing for purchase_orders and details from known formats

$headers = []; // po_id => [number, supplier, status, created]
$fh = fopen('c:/Users/Ruben/Downloads/u906028329_pegasus (6).sql', 'r');
$mode = null;
while (($line = fgets($fh)) !== false) {
  if (str_contains($line, 'INSERT INTO `purchase_orders`')) { $mode = 'po'; continue; }
  if (str_contains($line, 'INSERT INTO `purchase_orders_details`')) { $mode = 'pod'; continue; }
  if (str_contains($line, 'INSERT INTO `supplies_variants`')) { $mode = 'sv'; continue; }
  if (str_contains($line, 'INSERT INTO `') && !str_contains($line, 'purchase_orders') && !str_contains($line, 'supplies_variants')) {
    if ($mode === 'po' || $mode === 'pod' || $mode === 'sv') {
      // only reset if new insert of other table
      if (!str_contains($line, 'purchase_orders') && !str_contains($line, 'supplies_variants')) $mode = null;
    }
  }
  if ($mode === 'po' && preg_match('/^\((\d+), \'([^\']+)\', \'[^\']+\', (\d+),/', $line, $m)) {
    // status is near end before created_at: ..., status, 'created', 'updated', created_by, acc_by)
    if (preg_match('/, (-?\d+), \'(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\', \'[^\']*\',/', $line, $m2)) {
      // ambiguous - find status by counting from known schema with warehouse
      // Schema with wh: po_id, number, date, supplier, warehouse_id, total, jenis, disc, ppn, cost, desc, img, pembayaran, tt_id, status, created, updated, created_by, acc_by
      if (preg_match('/^\((\d+), \'([^\']+)\', \'[^\']+\', (\d+), (\d+|NULL), (-?\d+), \'[^\']*\', (-?\d+), (-?\d+), (-?\d+), (?:NULL|\'[^\']*\'), \'[^\']*\', (-?\d+), (?:NULL|\d+), (-?\d+), \'([^\']+)\',/', $line, $h)) {
        $headers[(int)$h[1]] = ['num'=>$h[2], 'supplier'=>(int)$h[3], 'status'=>(int)$h[10], 'created'=>$h[11]];
      } elseif (preg_match('/^\((\d+), \'([^\']+)\', \'[^\']+\', (\d+), (-?\d+), \'[^\']*\', (-?\d+), (-?\d+), (-?\d+), (?:NULL|\'[^\']*\'), \'[^\']*\', (-?\d+), (?:NULL|\d+), (-?\d+), \'([^\']+)\',/', $line, $h)) {
        // without warehouse
        $headers[(int)$h[1]] = ['num'=>$h[2], 'supplier'=>(int)$h[3], 'status'=>(int)$h[9], 'created'=>$h[10]];
      }
    }
  }
}
rewind($fh); // won't work well - reopen
fclose($fh);

$fh = fopen('c:/Users/Ruben/Downloads/u906028329_pegasus (6).sql', 'r');
$mode = null;
$details = [];
$variants = []; // id => supplier_id
while (($line = fgets($fh)) !== false) {
  if (str_contains($line, 'INSERT INTO `purchase_orders` (')) { $mode = 'po'; continue; }
  if (str_contains($line, 'INSERT INTO `purchase_orders_details`')) { $mode = 'pod'; continue; }
  if (str_contains($line, 'INSERT INTO `supplies_variants`')) { $mode = 'sv'; continue; }
  if (str_starts_with(trim($line), 'INSERT INTO') && !str_contains($line, 'purchase_orders_details') && !str_contains($line, 'purchase_orders') && !str_contains($line, 'supplies_variants')) {
    $mode = null;
  }
  if ($mode === 'po') {
    // with warehouse_id
    if (preg_match('/^\((\d+), \'([^\']+)\', \'[^\']+\', (\d+), (\d+), (-?\d+), \'[^\']*\', (-?\d+), (-?\d+), (-?\d+),/', $line)) {
      if (preg_match('/^\((\d+), \'([^\']+)\', \'[^\']+\', (\d+), \d+, -?\d+, \'[^\']*\', -?\d+, -?\d+, -?\d+, (?:NULL|\'(?:\\\\\'|[^\'])*\')?, \'[^\']*\', -?\d+, (?:NULL|\d+), (-?\d+), \'([^\']+)\'/', $line, $h)) {
        $headers[(int)$h[1]] = ['num'=>$h[2],'supplier'=>(int)$h[3],'status'=>(int)$h[4],'created'=>$h[5]];
      }
    }
  }
  if ($mode === 'pod') {
    // (pod_id, po_id, sv_id, nama, variant, unit_id, sku, harga, qty, subtotal, status, created, updated)
    if (preg_match('/^\((\d+), (\d+), (\d+), \'((?:\\\\\'|[^\'])*)\', \'((?:\\\\\'|[^\'])*)\', (\d+), \'([^\']+)\', (-?\d+), (-?\d+), (-?\d+), (-?\d+), \'([^\']+)\'/', $line, $d)) {
      $details[] = ['pod'=>(int)$d[1],'po'=>(int)$d[2],'sv'=>(int)$d[3],'sku'=>$d[7],'status'=>(int)$d[11],'created'=>$d[12],'sub'=>(int)$d[10]];
    }
  }
  if ($mode === 'sv') {
    // (id, supplier_id, supplies_id, name, sku, ...
    if (preg_match('/^\((\d+), (\d+),/', $line, $v)) {
      $variants[(int)$v[1]] = (int)$v[2];
    }
  }
}
fclose($fh);

echo "headers=".count($headers)." details=".count($details)." variants=".count($variants)."\n";

// 1 orphan: status=1 detail without header
$orphan = 0;
foreach ($details as $d) {
  if ($d['status'] !== 1) continue;
  if (!isset($headers[$d['po']])) { $orphan++; echo "ORPHAN pod={c:\Users\Ruben\Downloads\u906028329_pegasus (6).sql['pod']} po={c:\Users\Ruben\Downloads\u906028329_pegasus (6).sql['po']} sku={c:\Users\Ruben\Downloads\u906028329_pegasus (6).sql['sku']}\n"; }
}
echo "orphan_count=$orphan\n";

// 3 mismatch supplier
$mismatch = 0;
$older = [];
foreach ($details as $d) {
  if ($d['status'] !== 1) continue;
  if (!isset($headers[$d['po']])) continue;
  $h = $headers[$d['po']];
  if ($h['status'] === 0) continue;
  $vs = $variants[$d['sv']] ?? null;
  if ($vs === null) continue;
  if ($vs !== $h['supplier']) {
    $mismatch++;
    if ($mismatch <= 30) {
      echo "MISMATCH po={['num']}({c:\Users\Ruben\Downloads\u906028329_pegasus (6).sql['po']}) header_sup={['supplier']} var_sup=$vs pod={c:\Users\Ruben\Downloads\u906028329_pegasus (6).sql['pod']} sku={c:\Users\Ruben\Downloads\u906028329_pegasus (6).sql['sku']} time=".(($d['created'] < $h['created'])?'OLDER':'NEWER')."\n";
    }
  }
  if ($d['created'] < $h['created']) {
    $older[$d['po']] = ($older[$d['po']] ?? 0) + 1;
  }
}
echo "mismatch_count=$mismatch\n";
echo "pos_with_older_details=".count($older)."\n";
arsort($older);
$i=0; foreach ($older as $po=>$c) { if ($i++>=15) break; $n=$headers[$po]['num']??'?'; echo "OLDER po=$n ($po) count=$c header={[$po]['created']}\n"; }
