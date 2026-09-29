<!doctype html><html lang="id"><head><meta charset="utf-8"><title>{{ $tally ? $doc->tally_number : $doc->number }}</title>
<style>
@page { size:210mm 148mm; margin:7mm 7mm 38mm; }
body {font-family:Helvetica,Arial,sans-serif;font-size:8pt;color:#1e293b;line-height:1.3;} table{width:100%;border-collapse:collapse;} td{vertical-align:top;} .logo{width:13mm;max-height:13mm;} .title{font-size:13pt;font-weight:bold;} .small{font-size:6.5pt;color:#475569;} .header{border-bottom:1pt solid #1e293b;margin-bottom:3mm;} .right{text-align:right;} .grid th{background:#1e293b;color:white;padding:2mm;} .grid td{border:0.5pt solid #cbd5e1;padding:2mm;} .grid tr:nth-child(even){background:#f8fafc;} .grid thead{display:table-header-group;} tr{page-break-inside:avoid;} .footer{position:fixed;bottom:-32mm;height:30mm;left:0;right:0;} .sign{text-align:center;table-layout:fixed;} .signature{height:12mm;max-width:32mm;} .signature-box{height:13mm;} .ref{border-top:.5pt solid #cbd5e1;margin-top:2mm;padding-top:1mm;font-size:6pt;}
</style></head><body>
@php
$title = $tally ? 'TALLY PRODUKSI' : (['warehouse'=>'FORM GUDANG','material_issue'=>'SERAH TERIMA BAHAN','material_return'=>'PENGEMBALIAN BAHAN'][$doc->type] ?? 'DOKUMEN');
// Form Gudang & Tally: PIC / Kepala Ops / Staf QC saja (tanpa hand-pallet).
$roles = ['pic'=>'SPV Produksi / PIC','ops'=>'Kepala Operasional','qc'=>'Staf QC Gudang'];
$unitNames = \App\Models\Unit::pluck('unit_name', 'unit_id');
$jamGudang = $doc->warehouse_at ?: 'Menunggu approval lengkap';
@endphp
<div class="footer"><table class="sign"><tr>
@foreach($roles as $key=>$label)
@php($sign = $doc->signatures[$key] ?? null)
<td><strong>{{ $label }}</strong><div class="signature-box">@if(!empty($sign['signature']))<img class="signature" src="{{ $sign['signature'] }}">@endif</div>{{ $sign['name'] ?? '................................' }}</td>
@endforeach
</tr></table><div class="ref">Kode produksi / WO: {{ $wo->wo_number }} | SPKP: {{ $pp->spkp_number ?? $pp->pp_number }} | PP: {{ $pp->pp_number }} | {{ $doc->number }} @if($doc->tally_number) | {{ $doc->tally_number }} @endif</div></div>
<table class="header"><tr><td style="width:16mm">@if($logo)<img src="{{ $logo }}" class="logo">@endif</td><td><strong>{{ $company['company_name'] ?? 'Pegasus Hikari Group' }}</strong><div class="small">{{ $company['company_address'] ?? '' }}</div></td><td class="right"><div class="small">{{ $tally ? 'FORM-OPS-19' : '' }}</div><div class="title">{{ $title }}</div>{{ $tally ? $doc->tally_number : $doc->number }}</td></tr></table>
<table style="margin-bottom:3mm"><tr><td>PIC Produksi: <strong>{{ $doc->signatures['pic']['name'] ?? '-' }}</strong><br>SPKP/PP: {{ $wo->production_line ?? ($pp->spkp_number ?? $pp->pp_number ?? '-') }}</td><td class="right">Konfirmasi PIC: {{ $doc->confirmed_at }}<br>Tanggal / jam gudang: <strong>{{ $jamGudang }}</strong></td></tr></table>
<table class="grid"><thead><tr><th>No</th><th>Nama Produk / Bahan</th><th>Qty Request</th><th>Qty Terima</th><th>Satuan</th><th>Hasil</th></tr></thead><tbody>
@foreach($doc->items as $row)<tr><td>{{ $loop->iteration }}</td><td>{{ $row['product_name'] }}</td><td>{{ $row['requested_qty'] }}</td><td>{{ $row['received_qty'] ?? '' }}</td><td>{{ $row['unit_label'] ?? $unitNames[$row['unit_id']] ?? '' }}</td><td>@if($doc->type === 'warehouse')OK @if(($row['excess_qty'] ?? 0)>0)<br>Kelebihan {{ $row['excess_qty'] }}@endif @endif</td></tr>@endforeach
</tbody></table>
@if($doc->type === 'warehouse')
<h4 style="margin-bottom:1mm">Rincian Konfirmasi Produksi / Pallet</h4>
<table class="grid"><thead><tr><th>Produk</th><th>Tanggal / Jam</th><th>Hasil Input</th><th>Isi 1 Pallet</th><th>No. Pallet</th><th>PIC</th></tr></thead><tbody>
@foreach($reports as $report) @foreach($report->items as $row)<tr><td>{{ $row['product_name'] }}</td><td>{{ $report->confirmed_at }}</td><td>{{ $row['input_qty'] }} {{ $row['input_unit'] === 'pallet' ? 'pallet' : ($unitNames[$row['unit_id']] ?? '') }}</td><td>{{ $row['qty_per_pallet'] ? $row['qty_per_pallet'].' '.($unitNames[$row['unit_id']] ?? '') : '-' }}</td><td>{{ $row['pallet_number'] ?: '-' }}</td><td>{{ $report->actor_snapshot['name'] }}</td></tr>@endforeach @endforeach
</tbody></table>
@endif
</body></html>
