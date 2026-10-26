<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Serah Terima Bahan — {{ $wo->wo_number }}</title>
<style>
@page { size:210mm 148mm; margin:7mm 7mm 28mm; }
body { font-family: Helvetica, Arial, sans-serif; font-size: 8pt; color: #1e293b; line-height: 1.3; }
table { width: 100%; border-collapse: collapse; }
td { vertical-align: top; }
.logo { width: 13mm; max-height: 13mm; }
.title { font-size: 12pt; font-weight: bold; }
.small { font-size: 6.5pt; color: #475569; }
.meta { font-size: 7pt; }
.header { border-bottom: 1pt solid #1e293b; margin-bottom: 3mm; padding-bottom: 2mm; }
.right { text-align: right; }
.grid th { background: #1e293b; color: white; padding: 1.5mm; font-size: 7pt; }
.grid td { border: 0.5pt solid #cbd5e1; padding: 1.5mm; font-size: 7.5pt; }
.grid tr:nth-child(even) { background: #f8fafc; }
.footer { position: fixed; bottom: -22mm; height: 20mm; left: 0; right: 0; }
.sign { text-align: center; table-layout: fixed; }
.sign td { font-size: 7pt; }
.box { height: 12mm; }
</style>
</head>
<body>
@php
    $dept = $wo->production_line ?: ($pp->spkp_number ?? $pp->pp_number ?? '—');
    $tgl = $wo->production_completed_at
        ? \Carbon\Carbon::parse($wo->production_completed_at)->format('d/m/Y')
        : now()->format('d/m/Y');
@endphp
<div class="footer">
    <table class="sign">
        <tr>
            <td><strong>Diterima Oleh</strong><div class="box"></div>{{ $pic_name ?? '................................' }}</td>
            <td><strong>Diperiksa Oleh</strong><div class="box"></div>................................</td>
        </tr>
    </table>
    <div class="small" style="margin-top:1mm;">WO: {{ $wo->wo_number }} · PP: {{ $pp->pp_number ?? '—' }} · FORM-OPS-08</div>
</div>

<table class="header">
    <tr>
        <td style="width:16mm">@if($logo)<img src="{{ $logo }}" class="logo">@endif</td>
        <td>
            <strong>{{ $company['company_name'] ?? 'Pegasus Hikari Group' }}</strong>
            <div class="small">{{ $company['company_address'] ?? '' }}</div>
        </td>
        <td class="right">
            <div class="small">FORM-OPS-08</div>
            <div class="title">LAPORAN SERAH TERIMA BAHAN MENTAH</div>
        </td>
    </tr>
</table>

<table class="meta" style="margin-bottom:3mm;">
    <tr>
        <td>Departemen / Lini: <strong>{{ $dept }}</strong></td>
        <td>Tgl: <strong>{{ $tgl }}</strong></td>
        <td>Nama Penerima: <strong>{{ $pic_name ?? '—' }}</strong></td>
    </tr>
</table>

<table class="grid">
    <thead>
        <tr>
            <th style="width:8mm;">No</th>
            <th>Nama Bahan Mentah</th>
            <th style="width:28mm;">QTY Terima</th>
            <th style="width:22mm;">Total</th>
            <th style="width:28mm;">QTY Kembali</th>
            <th style="width:20mm;">Satuan</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $row)
            <tr>
                <td style="text-align:center;">{{ $row['no'] }}</td>
                <td>{{ $row['name'] }}</td>
                <td style="text-align:right;">{{ $row['qty_terima'] }}</td>
                <td style="text-align:right;">{{ rtrim(rtrim(number_format((float) $row['total'], 2, '.', ''), '0'), '.') }}</td>
                <td style="text-align:right;">{{ rtrim(rtrim(number_format((float) $row['qty_kembali'], 2, '.', ''), '0'), '.') }}</td>
                <td style="text-align:center;">{{ $row['unit'] }}</td>
            </tr>
        @empty
            <tr><td colspan="6" style="text-align:center;">Belum ada bahan yang di-ACC ambil.</td></tr>
        @endforelse
    </tbody>
</table>
</body>
</html>
